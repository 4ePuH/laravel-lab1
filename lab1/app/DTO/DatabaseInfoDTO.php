<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class DatabaseInfoDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $driver,
        public readonly string $serverVersion,
        public readonly string $databaseName,
    ) {}

    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'server_version' => $this->serverVersion,
            'database_name' => $this->databaseName,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
