<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Неизменяемый набор данных для регистрации (после валидации).
 */
final class RegisterDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $birthday,
    ) {}

    /**
     * Данные для создания записи User.
     * Пароль здесь ещё в открытом виде — модель захеширует его
     * автоматически благодаря casts() ('password' => 'hashed').
     *
     * @return array<string, string>
     */
    public function toUserAttributes(): array
    {
        return [
            'username' => $this->username,
            'name' => $this->username,
            'email' => $this->email,
            'birthday' => $this->birthday,
            'password' => $this->password,
        ];
    }
}
