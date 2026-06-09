<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Неизменяемое представление пользователя для JSON-ответов.
 */
final class UserDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $birthday,
    ) {}

    /**
     * Собирает DTO из модели Eloquent.
     */
    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            username: $user->username,
            email: $user->email,
            birthday: $user->birthday?->format('Y-m-d'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'birthday' => $this->birthday,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
