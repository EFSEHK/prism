<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Hook used by RefreshDatabase (no return type — must match the trait signature).
     * Hard stop if tests would hit the real MySQL app DB.
     */
    protected function beforeRefreshingDatabase()
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $safe = $connection === 'sqlite' && ($database === ':memory:' || $database === '');

        if ($safe) {
            return;
        }

        $this->fail(
            "Unsafe test database [{$connection} / {$database}]. "
            .'RefreshDatabase must use sqlite :memory: (phpunit.xml DB_* env force=true). '
            .'Running against MySQL wipes the app DB and causes intermittent Invalid login details.'
        );
    }
}
