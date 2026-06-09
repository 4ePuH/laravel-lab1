<?php

namespace Tests\Feature;

use Tests\TestCase;

class InfoRoutesTest extends TestCase
{
    public function test_server_route_returns_expected_json(): void
    {
        $this->getJson('/info/server')
            ->assertOk()
            ->assertJsonStructure([
                'php_version',
                'php_sapi',
                'operating_system',
                'memory_limit',
                'max_execution_time',
            ]);
    }

    public function test_client_route_returns_expected_json(): void
    {
        $this->getJson('/info/client')
            ->assertOk()
            ->assertJsonStructure(['ip_address', 'user_agent']);
    }

    public function test_database_route_returns_expected_json(): void
    {
        $this->getJson('/info/database')
            ->assertOk()
            ->assertJsonStructure(['driver', 'server_version', 'database_name']);
    }
}
