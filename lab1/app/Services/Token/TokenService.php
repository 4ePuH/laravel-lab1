<?php

declare(strict_types=1);

namespace App\Services\Token;

use App\DTO\AuthSuccessDTO;
use App\DTO\TokenListDTO;
use App\DTO\UserDTO;
use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;

/**
 * Самописная реализация механизма токенов.
 *
 * Формат токена (упрощённый JWT):
 *   base64url(payload) . base64url( HMAC-SHA256(payload, secret) )
 *
 * В payload зашиты: jti (id токена), sub (id пользователя),
 * type (access|refresh), iat (выдан), exp (истекает).
 *
 * Сам токен в БД не хранится — только его jti и SHA-256 хеш,
 * что позволяет отслеживать и отзывать токены, но не раскрывает их.
 */
final class TokenService implements TokenServiceInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly int $accessTtl,   // минуты
        private readonly int $refreshTtl,  // минуты
        private readonly int $maxActive,
    ) {}

    public function issuePair(User $user, ?string $ip, ?string $userAgent): AuthSuccessDTO
    {
        // Всё в одной транзакции: либо выдаём пару целиком, либо ничего.
        return DB::transaction(function () use ($user, $ip, $userAgent): AuthSuccessDTO {
            $this->enforceActiveLimit($user);

            $pairId = (string) Str::uuid();
            $accessExpires = now()->addMinutes($this->accessTtl);
            $refreshExpires = now()->addMinutes($this->refreshTtl);

            $access = $this->createToken($user, Token::TYPE_ACCESS, $pairId, $accessExpires, $ip, $userAgent);
            $refresh = $this->createToken($user, Token::TYPE_REFRESH, $pairId, $refreshExpires, $ip, $userAgent);

            return new AuthSuccessDTO(
                accessToken: $access,
                refreshToken: $refresh,
                expiresIn: $this->accessTtl * 60,
                user: UserDTO::fromModel($user),
            );
        });
    }

    public function authenticateAccessToken(string $accessToken): Token
    {
        $payload = $this->decode($accessToken);

        if ($payload['type'] !== Token::TYPE_ACCESS) {
            throw new TokenException('Передан токен неверного типа.');
        }

        $token = $this->findActiveRecord($payload['jti'], $accessToken, Token::TYPE_ACCESS);
        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }

    public function refresh(string $refreshToken, ?string $ip, ?string $userAgent): AuthSuccessDTO
    {
        $payload = $this->decode($refreshToken);

        if ($payload['type'] !== Token::TYPE_REFRESH) {
            throw new TokenException('Передан токен неверного типа.');
        }

        /** @var Token|null $record */
        $record = Token::query()
            ->where('jti', $payload['jti'])
            ->where('type', Token::TYPE_REFRESH)
            ->first();

        if ($record === null) {
            throw new TokenException('Refresh-токен недействителен.');
        }

        // Повторное использование уже отозванного refresh-токена —
        // признак кражи. Отзываем ВСЕ токены пользователя.
        if ($record->revoked_at !== null) {
            $this->revokeAllForUser($record->user);

            throw new TokenException('Refresh-токен уже использован. Все сессии завершены.');
        }

        // Ротация: гасим старую пару и выдаём новую (одноразовость).
        return DB::transaction(function () use ($record, $ip, $userAgent): AuthSuccessDTO {
            $this->revokePair($record);

            return $this->issuePair($record->user, $ip, $userAgent);
        });
    }

    public function revokePair(Token $token): void
    {
        Token::query()
            ->where('pair_id', $token->pair_id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllForUser(User $user): void
    {
        $user->tokens()
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function activeTokens(User $user): TokenListDTO
    {
        $tokens = $user->tokens()
            ->active()
            ->orderByDesc('created_at')
            ->get();

        return TokenListDTO::fromCollection($tokens);
    }

    /**
     * Создаёт запись о токене в БД и возвращает сам токен (строку).
     */
    private function createToken(
        User $user,
        string $type,
        string $pairId,
        Carbon $expiresAt,
        ?string $ip,
        ?string $userAgent,
    ): string {
        $jti = (string) Str::uuid();

        $token = $this->encode([
            'jti' => $jti,
            'sub' => $user->id,
            'type' => $type,
            'iat' => now()->timestamp,
            'exp' => $expiresAt->timestamp,
        ]);

        $user->tokens()->create([
            'pair_id' => $pairId,
            'jti' => $jti,
            'type' => $type,
            'token_hash' => $this->hash($token),
            'expires_at' => $expiresAt,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        return $token;
    }

    /**
     * Если активных access-токенов уже максимум — отзываем самые старые пары,
     * освобождая место под новую.
     */
    private function enforceActiveLimit(User $user): void
    {
        $activeAccess = $user->tokens()
            ->active()
            ->where('type', Token::TYPE_ACCESS)
            ->orderBy('created_at')
            ->get();

        $excess = $activeAccess->count() - ($this->maxActive - 1);

        foreach ($activeAccess->take(max(0, $excess)) as $token) {
            $this->revokePair($token);
        }
    }

    /**
     * Находит активную запись токена по jti, сверяет хеш и тип.
     *
     * @throws TokenException
     */
    private function findActiveRecord(string $jti, string $rawToken, string $type): Token
    {
        /** @var Token|null $token */
        $token = Token::query()
            ->active()
            ->where('jti', $jti)
            ->where('type', $type)
            ->where('token_hash', $this->hash($rawToken))
            ->first();

        if ($token === null) {
            throw new TokenException('Токен недействителен или отозван.');
        }

        return $token;
    }

    /**
     * Кодирует payload в подписанную строку токена.
     *
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        try {
            $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new TokenException('Не удалось сформировать токен.', previous: $e);
        }

        return $body.'.'.$this->sign($body);
    }

    /**
     * Проверяет подпись и срок жизни, возвращает payload.
     *
     * @return array{jti: string, sub: int, type: string, iat: int, exp: int}
     *
     * @throws TokenException
     */
    private function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            throw new TokenException('Некорректный формат токена.');
        }

        [$body, $signature] = $parts;

        // hash_equals — сравнение, устойчивое к атаке по времени.
        if (! hash_equals($this->sign($body), $signature)) {
            throw new TokenException('Подпись токена недействительна.');
        }

        try {
            $payload = json_decode($this->base64UrlDecode($body), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TokenException('Не удалось разобрать токен.', previous: $e);
        }

        if (! $this->isValidPayload($payload)) {
            throw new TokenException('Содержимое токена некорректно.');
        }

        if ($payload['exp'] < now()->timestamp) {
            throw new TokenException('Срок действия токена истёк.');
        }

        return $payload;
    }

    /**
     * @phpstan-assert-if-true array{jti: string, sub: int, type: string, iat: int, exp: int} $payload
     */
    private function isValidPayload(mixed $payload): bool
    {
        return is_array($payload)
            && isset($payload['jti'], $payload['sub'], $payload['type'], $payload['exp'])
            && is_string($payload['jti'])
            && is_string($payload['type']);
    }

    private function sign(string $body): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $body, $this->secret, true));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
