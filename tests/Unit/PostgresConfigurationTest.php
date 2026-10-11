<?php

namespace Tests\Unit;

use Illuminate\Database\Connectors\PostgresConnector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PostgresConfigurationTest extends TestCase
{
    public function test_local_postgres_defaults_remain_compatible(): void
    {
        $configuration = $this->readConfiguration([]);

        $this->assertSame('public', $configuration['search_path']);
        $this->assertSame('prefer', $configuration['sslmode']);
        $this->assertNull($configuration['sslrootcert']);
    }

    public function test_external_postgres_schema_and_certificate_are_configurable(): void
    {
        $configuration = $this->readConfiguration([
            'DB_SEARCH_PATH' => 'novaflow',
            'DB_SSLMODE' => 'verify-full',
            'DB_SSLROOTCERT' => '/private/certs/provider-ca.crt',
        ]);

        $this->assertSame('novaflow', $configuration['search_path']);
        $this->assertSame('verify-full', $configuration['sslmode']);
        $this->assertSame('/private/certs/provider-ca.crt', $configuration['sslrootcert']);

        $connector = new class extends PostgresConnector
        {
            /** @param array<string, mixed> $config */
            public function dsn(array $config): string
            {
                return $this->getDsn($config);
            }
        };

        $dsn = $connector->dsn($configuration + [
            'host' => 'database.example.test',
            'port' => '5432',
            'database' => 'preview',
        ]);

        $this->assertStringContainsString(';sslmode=verify-full', $dsn);
        $this->assertStringContainsString(';sslrootcert=/private/certs/provider-ca.crt', $dsn);
        $this->assertStringNotContainsString('password', $dsn);
    }

    /**
     * Load the real config in an isolated process without a database connection
     * or modifying the test runner's environment. Print only non-secret options.
     *
     * @param  array<string, string>  $environment
     * @return array<string, mixed>
     */
    private function readConfiguration(array $environment): array
    {
        $script = <<<'PHP'
        require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $config = require 'config/database.php';
        echo json_encode(array_intersect_key($config['connections']['pgsql'], array_flip([
            'search_path', 'sslmode', 'sslrootcert',
        ])), JSON_THROW_ON_ERROR);
        PHP;

        $process = new Process([PHP_BINARY, '-r', $script], dirname(__DIR__, 2), array_merge([
            'DB_SEARCH_PATH' => false,
            'DB_SSLMODE' => false,
            'DB_SSLROOTCERT' => false,
        ], $environment));
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
