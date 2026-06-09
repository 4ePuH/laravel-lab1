<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\ChangePasswordDTO;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация данных для смены пароля.
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
            'current_password' => ['required', 'string'],
            'password' => ['required', new StrongPassword],
            'c_password' => ['required', 'same:password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'c_password.same' => 'Пароли не совпадают.',
        ];
    }

    public function toDTO(): ChangePasswordDTO
    {
        return new ChangePasswordDTO(
            currentPassword: (string) $this->validated('current_password'),
            newPassword: (string) $this->validated('password'),
        );
    }
}
