<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final class ServerInfoDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $phpVersion,
        public readonly string $phpSapi,
        public readonly string $operatingSystem,
        public readonly string $memoryLimit,
        public readonly int $maxExecutionTime,
    ) {}

    public function toArray(): array
    {
        return [
            'php_version' => $this->phpVersion,
            'php_sapi' => $this->phpSapi,
            'operating_system' => $this->operatingSystem,
            'memory_limit' => $this->memoryLimit,
            'max_execution_time' => $this->maxExecutionTime,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
