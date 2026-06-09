<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Неизменяемый набор данных для входа (после валидации).
 */
final class LoginDTO
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {}
}
