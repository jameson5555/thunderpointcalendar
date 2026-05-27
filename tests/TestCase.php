<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->guardAgainstLiveLocalSqliteDatabase();

        parent::setUp();

        $this->withoutVite();
    }

    protected function guardAgainstLiveLocalSqliteDatabase(): void
    {
        $defaultConnection = $this->environmentValue('DB_CONNECTION');
        $sqliteDatabase = $this->environmentValue('DB_DATABASE');
        $liveLocalDatabase = realpath(__DIR__.'/../database/database.sqlite');

        if (
            $defaultConnection === 'sqlite'
            && is_string($sqliteDatabase)
            && $sqliteDatabase !== ':memory:'
            && $liveLocalDatabase !== false
            && realpath($sqliteDatabase) === $liveLocalDatabase
        ) {
            throw new RuntimeException('Refusing to run tests against the live local sqlite database. Use the default in-memory test database instead.');
        }
    }

    protected function environmentValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) ? $value : null;
    }
}
