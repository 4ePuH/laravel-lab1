<?php

namespace App\Http\Controllers;

use App\DTO\ClientInfoDTO;
use App\DTO\DatabaseInfoDTO;
use App\DTO\ServerInfoDTO;
use App\Services\DatabaseInfoService;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    public function serverInfo(): ServerInfoDTO
    {
        return new ServerInfoDTO(
            phpVersion: PHP_VERSION,
            phpSapi: PHP_SAPI,
            operatingSystem: PHP_OS,
            memoryLimit: (string) ini_get('memory_limit'),
            maxExecutionTime: (int) ini_get('max_execution_time'),
        );
    }

    public function clientInfo(Request $request): ClientInfoDTO
    {
        return new ClientInfoDTO(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    public function databaseInfo(DatabaseInfoService $databaseInfo): DatabaseInfoDTO
    {
        return $databaseInfo->getInfo();
    }
}
