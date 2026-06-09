<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature-тесты HTTP-маршрутов авторизации.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Корректный набор данных для регистрации. */
    private function validRegisterPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'Johndoe',
            'email' => 'john@example.com',
            'password' => 'Password1!',
            'c_password' => 'Password1!',
            'birthday' => '2000-01-01',
        ], $overrides);
    }

    public function test_register_creates_user_and_returns_201(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validRegisterPayload());

        $response->assertCreated()
            ->assertJsonPath('username', 'Johndoe')
            ->assertJsonMissingPath('password');

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_register_validates_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validRegisterPayload([
            'password' => 'weak',
            'c_password' => 'weak',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_register_rejects_underage_user(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validRegisterPayload([
            'birthday' => now()->subYears(10)->format('Y-m-d'),
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('birthday');
    }

    public function test_login_returns_token_pair(): void
    {
        User::factory()->create([
            'username' => 'Johndoe',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'Johndoe',
            'password' => 'Password1!',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in', 'user' => ['id', 'username']]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'username' => 'Johndoe',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'username' => 'Johndoe',
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_returns_user_with_token(): void
    {
        $token = $this->loginAndGetAccessToken();

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('username', 'Johndoe');
    }

    public function test_out_revokes_current_token(): void
    {
        $token = $this->loginAndGetAccessToken();

        $this->withToken($token)->postJson('/api/auth/out')->assertOk();
        // После выхода токен больше не работает.
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_tokens_list_does_not_expose_raw_tokens(): void
    {
        $token = $this->loginAndGetAccessToken();

        $response = $this->withToken($token)->getJson('/api/auth/tokens')->assertOk();

        $this->assertStringNotContainsString($token, $response->getContent());
    }

    public function test_refresh_returns_new_pair(): void
    {
        User::factory()->create(['username' => 'Johndoe', 'password' => Hash::make('Password1!')]);
        $login = $this->postJson('/api/auth/login', ['username' => 'Johndoe', 'password' => 'Password1!']);
        $refresh = $login->json('refresh_token');

        $this->postJson('/api/auth/refresh', ['refresh_token' => $refresh])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    public function test_register_blocked_for_authenticated_user(): void
    {
        $token = $this->loginAndGetAccessToken();

        $this->withToken($token)
            ->postJson('/api/auth/register', $this->validRegisterPayload(['username' => 'Another', 'email' => 'a@b.com']))
            ->assertStatus(403);
    }

    /**
     * Создаёт пользователя, логинит и отдаёт access-токен.
     */
    private function loginAndGetAccessToken(): string
    {
        User::factory()->create([
            'username' => 'Johndoe',
            'password' => Hash::make('Password1!'),
        ]);

        return $this->postJson('/api/auth/login', [
            'username' => 'Johndoe',
            'password' => 'Password1!',
        ])->json('access_token');
    }
}
