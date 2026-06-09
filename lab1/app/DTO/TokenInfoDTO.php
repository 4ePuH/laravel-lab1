<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Token;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Метаданные одного активного токена (без самого токена!).
 */
final class TokenInfoDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $createdAt,
        public readonly string $expiresAt,
        public readonly ?string $lastUsedAt,
        public readonly ?string $ipAddress,
    ) {}

    public static function fromModel(Token $token): self
    {
        return new self(
            id: $token->id,
            type: $token->type,
            createdAt: $token->created_at->toIso8601String(),
            expiresAt: $token->expires_at->toIso8601String(),
            lastUsedAt: $token->last_used_at?->toIso8601String(),
            ipAddress: $token->ip_address,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'last_used_at' => $this->lastUsedAt,
            'ip_address' => $this->ipAddress,
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
