<?php

namespace Tests\Unit;

use App\DTO\DatabaseInfoDTO;
use App\Services\DatabaseInfoService;
use Illuminate\Support\Facades\DB;
use Mockery;
use PDO;
use Tests\TestCase;

class DatabaseInfoServiceTest extends TestCase
{
    public function test_get_info_builds_dto_from_connection(): void
    {
        $pdo = Mockery::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')
            ->with(PDO::ATTR_SERVER_VERSION)
            ->andReturn('3.49.2');

        $connection = Mockery::mock();
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldReceive('getPdo')->andReturn($pdo);
        $connection->shouldReceive('getDatabaseName')->andReturn(':memory:');

        DB::shouldReceive('connection')->andReturn($connection);

        $dto = (new DatabaseInfoService())->getInfo();

        $this->assertInstanceOf(DatabaseInfoDTO::class, $dto);
        $this->assertSame('sqlite', $dto->driver);
        $this->assertSame('3.49.2', $dto->serverVersion);
        $this->assertSame(':memory:', $dto->databaseName);
    }
}
