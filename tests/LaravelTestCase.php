<?php

namespace BugSquid\Tests;

use BugSquid\Laravel\BugSquidServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class LaravelTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BugSquidServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bugsquid.endpoint', 'https://example.com/ingest');
        $app['config']->set('bugsquid.key', 'test-key');
        $app['config']->set('bugsquid.environment', 'testing');
        $app['config']->set('bugsquid.server_name', 'test-server');
    }
}
