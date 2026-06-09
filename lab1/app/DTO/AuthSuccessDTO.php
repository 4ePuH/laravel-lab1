<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Неизменяемый ответ при успешной авторизации / обновлении токена:
 * пара токенов + данные пользователя.
 */
final class AuthSuccessDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $expiresIn,   // время жизни access-токена в секундах
        public readonly UserDTO $user,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_in' => $this->expiresIn,
            'user' => $this->user->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
