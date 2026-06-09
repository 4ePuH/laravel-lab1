<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ChangePasswordDTO;
use App\Models\User;
use App\Rules\StrongPassword;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

/**
 * Валидация данных для смены пароля.
 *
 * Поля запроса:
 *  - current_password          — текущий пароль;
 *  - new_password              — новый пароль;
 *  - new_password_confirmation — подтверждение нового пароля.
 */
class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Текущий пароль проверяем прямо здесь -> неверный даёт 422, а не 500/401.
            'current_password' => ['required', 'string', $this->currentPasswordRule()],
            // 'confirmed' требует поле new_password_confirmation.
            'new_password' => ['required', new StrongPassword, 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'new_password.confirmed' => 'Пароли не совпадают.',
        ];
    }

    public function toDTO(): ChangePasswordDTO
    {
        return new ChangePasswordDTO(
            currentPassword: (string) $this->validated('current_password'),
            newPassword: (string) $this->validated('new_password'),
        );
    }

    /**
     * Сверяет current_password с паролем авторизованного пользователя.
     */
    private function currentPasswordRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var User|null $user */
            $user = $this->user();

            if ($user === null || ! Hash::check((string) $value, $user->password)) {
                $fail('Текущий пароль указан неверно.');
            }
        };
    }
}
