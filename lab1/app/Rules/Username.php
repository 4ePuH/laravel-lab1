<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Правило для логина:
 *  - только латинские буквы;
 *  - начинается с заглавной буквы;
 *  - минимальная длина 7 символов.
 */
final class Username implements ValidationRule
{
    private const int MIN_LENGTH = 7;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < self::MIN_LENGTH) {
            $fail('Поле :attribute должно содержать минимум '.self::MIN_LENGTH.' символов.');

            return;
        }

        // ^[A-Z]      — первая буква заглавная латинская;
        // [a-zA-Z]*$  — остальные только латинские буквы.
        if (preg_match('/^[A-Z][a-zA-Z]*$/', $value) !== 1) {
            $fail('Поле :attribute должно содержать только латинские буквы и начинаться с заглавной.');
        }
    }
}
