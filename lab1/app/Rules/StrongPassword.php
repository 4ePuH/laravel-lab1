<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Правило для пароля:
 *  - минимальная длина 8 символов;
 *  - хотя бы одна цифра;
 *  - хотя бы один специальный символ (не буква и не цифра);
 *  - хотя бы одна буква в верхнем и одна в нижнем регистре.
 */
final class StrongPassword implements ValidationRule
{
    private const int MIN_LENGTH = 8;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < self::MIN_LENGTH) {
            $fail('Поле :attribute должно содержать минимум '.self::MIN_LENGTH.' символов.');

            return;
        }

        if (preg_match('/\d/', $value) !== 1) {
            $fail('Поле :attribute должно содержать хотя бы одну цифру.');
        }

        if (preg_match('/[a-z]/', $value) !== 1) {
            $fail('Поле :attribute должно содержать хотя бы одну строчную букву.');
        }

        if (preg_match('/[A-Z]/', $value) !== 1) {
            $fail('Поле :attribute должно содержать хотя бы одну заглавную букву.');
        }

        // Спецсимвол = всё, что не буква и не цифра.
        if (preg_match('/[^a-zA-Z0-9]/', $value) !== 1) {
            $fail('Поле :attribute должно содержать хотя бы один специальный символ.');
        }
    }
}
