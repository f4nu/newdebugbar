<?php

namespace NewDebugBar\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Livewire\LivewireServiceProvider;
use NewDebugBar\NewDebugBarServiceProvider;
use NewDebugBar\Tests\Support\DefinesTestApplication;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use DefinesTestApplication;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            McpServiceProvider::class,
            NewDebugBarServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $workerToken = getenv('UNIQUE_TEST_TOKEN');
        $profileDirectory = 'testing-newdebugbar'.($workerToken === false ? '' : '-'.$workerToken);

        $app['config']->set('newdebugbar.environments', ['testing']);
        $app['config']->set('newdebugbar.storage.path', storage_path('framework/'.$profileDirectory));
        $app['config']->set('newdebugbar.collection.application_path', dirname(__DIR__));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        view()->addLocation(__DIR__.'/Fixtures/views');
        $this->app['files']->deleteDirectory(config('newdebugbar.storage.path'));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory(config('newdebugbar.storage.path'));

        parent::tearDown();
    }
}
