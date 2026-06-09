<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Token;
use App\Models\User;
use App\Services\Token\TokenException;
use App\Services\Token\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты самописного сервиса токенов.
 */
class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(int $maxActive = 5): TokenService
    {
        return new TokenService(
            secret: 'test-secret',
            accessTtl: 60,
            refreshTtl: 10080,
            maxActive: $maxActive,
        );
    }

    public function test_issued_access_token_can_be_authenticated(): void
    {
        $user = User::factory()->create();
        $service = $this->service();

        $auth = $service->issuePair($user, '127.0.0.1', 'phpunit');
        $token = $service->authenticateAccessToken($auth->accessToken);

        $this->assertSame($user->id, $token->user_id);
        $this->assertNotNull($token->last_used_at);
    }

    public function test_tampered_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $auth = $service->issuePair($user, null, null);

        $this->expectException(TokenException::class);
        $service->authenticateAccessToken($auth->accessToken.'broken');
    }

    public function test_raw_token_is_not_stored_in_database(): void
    {
        $user = User::factory()->create();
        $auth = $this->service()->issuePair($user, null, null);

        // В БД не должно быть самого токена — только его хеш.
        $this->assertDatabaseMissing('tokens', ['token_hash' => $auth->accessToken]);
        $this->assertDatabaseHas('tokens', ['token_hash' => hash('sha256', $auth->accessToken)]);
    }

    public function test_active_token_limit_revokes_oldest_pair(): void
    {
        $user = User::factory()->create();
        $service = $this->service(maxActive: 2);

        $first = $service->issuePair($user, null, null);
        $service->issuePair($user, null, null);
        $service->issuePair($user, null, null); // третий — лимит превышен

        $activeAccess = $user->tokens()->active()->where('type', Token::TYPE_ACCESS)->count();
        $this->assertSame(2, $activeAccess);

        // Самый старый access уже не работает.
        $this->expectException(TokenException::class);
        $service->authenticateAccessToken($first->accessToken);
    }

    public function test_refresh_rotates_tokens_and_invalidates_old_one(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $auth = $service->issuePair($user, null, null);

        $new = $service->refresh($auth->refreshToken, null, null);
        $this->assertNotSame($auth->accessToken, $new->accessToken);

        // Старый refresh одноразовый — повторное использование отзывает всё.
        $this->expectException(TokenException::class);
        $service->refresh($auth->refreshToken, null, null);
    }

    public function test_reused_refresh_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $service = $this->service();
        $auth = $service->issuePair($user, null, null);
        $service->refresh($auth->refreshToken, null, null);

        try {
            $service->refresh($auth->refreshToken, null, null);
        } catch (TokenException) {
            // ожидаемо
        }

        $this->assertSame(0, $user->tokens()->active()->count());
    }
}
