<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RegisterDTO;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Rules\Username;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация данных для регистрации.
 */
class RegisterRequest extends FormRequest
{
    /** Минимальный возраст пользователя при регистрации (лет). */
    private const int MIN_AGE = 14;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Нормализуем e-mail к нижнему регистру до валидации,
     * чтобы уникальность работала без учёта регистра.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower((string) $this->input('email'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Самая поздняя допустимая дата рождения = сегодня минус 14 лет.
        $maxBirthday = now()->subYears(self::MIN_AGE)->toDateString();

        return [
            'username' => ['required', new Username, $this->uniqueUsernameRule()],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', new StrongPassword],
            'c_password' => ['required', 'same:password'],
            'birthday' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$maxBirthday],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'c_password.same' => 'Пароли не совпадают.',
            'birthday.before_or_equal' => 'Возраст должен быть не менее '.self::MIN_AGE.' лет.',
        ];
    }

    /**
     * Возвращает DTO с уже провалидированными данными.
     */
    public function toDTO(): RegisterDTO
    {
        return new RegisterDTO(
            username: (string) $this->validated('username'),
            email: (string) $this->validated('email'),
            password: (string) $this->validated('password'),
            birthday: (string) $this->validated('birthday'),
        );
    }

    /**
     * Проверка уникальности логина без учёта регистра.
     */
    private function uniqueUsernameRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $exists = User::query()
                ->whereRaw('LOWER(username) = ?', [mb_strtolower((string) $value)])
                ->exists();

            if ($exists) {
                $fail('Такой логин уже занят.');
            }
        };
    }
}
