<?php

namespace App\Services;

use App\DTO\DatabaseInfoDTO;
use Illuminate\Support\Facades\DB;
use PDO;

final class DatabaseInfoService
{
    public function getInfo(): DatabaseInfoDTO
    {
        $connection = DB::connection();

        return new DatabaseInfoDTO(
            driver: $connection->getDriverName(),
            serverVersion: (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
            databaseName: $connection->getDatabaseName(),
        );
    }
}
