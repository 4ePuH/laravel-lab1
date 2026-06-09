<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class ClientInfoDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {}

    public function toArray(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
