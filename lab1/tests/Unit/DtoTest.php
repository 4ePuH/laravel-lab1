<?php

namespace Tests\Unit;

use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use App\DTO\ServerInfoDTO;
use PHPUnit\Framework\TestCase;

class DtoTest extends TestCase
{
    public function test_server_info_dto_to_array(): void
    {
        $dto = new ServerInfoDTO('8.4.19', 'cli-server', 'WINNT', '128M', 30);

        $this->assertSame([
            'php_version' => '8.4.19',
            'php_sapi' => 'cli-server',
            'operating_system' => 'WINNT',
            'memory_limit' => '128M',
            'max_execution_time' => 30,
        ], $dto->toArray());
        $this->assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    public function test_client_info_dto_to_array(): void
    {
        $dto = new ClientInfoDTO('127.0.0.1', 'Mozilla/5.0');

        $this->assertSame([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ], $dto->toArray());
    }

    public function test_client_info_dto_handles_nulls(): void
    {
        $dto = new ClientInfoDTO(null, null);

        $this->assertSame(['ip_address' => null, 'user_agent' => null], $dto->toArray());
    }

    public function test_database_info_dto_to_array(): void
    {
        $dto = new DatabaseInfoDTO('sqlite', '3.49.2', '/path/database.sqlite');

        $this->assertSame([
            'driver' => 'sqlite',
            'server_version' => '3.49.2',
            'database_name' => '/path/database.sqlite',
        ], $dto->toArray());
    }
}
