<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация данных для входа (login).
 */
class LoginRequest extends FormRequest
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
        // На входе достаточно проверить, что поля присутствуют и это строки.
        // Полноту правил (заглавная буква, спецсимволы и т.д.) проверять
        // не нужно — это лишь сверка с уже сохранённым пользователем.
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Возвращает DTO с уже провалидированными данными.
     */
    public function toDTO(): LoginDTO
    {
        return new LoginDTO(
            username: (string) $this->validated('username'),
            password: (string) $this->validated('password'),
        );
    }
}
