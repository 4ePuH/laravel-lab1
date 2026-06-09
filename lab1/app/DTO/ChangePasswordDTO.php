<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Неизменяемый набор данных для смены пароля (после валидации).
 */
final class ChangePasswordDTO
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {}
}
