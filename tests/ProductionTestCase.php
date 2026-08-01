<?php

namespace NewDebugBar\Tests;

use Livewire\LivewireServiceProvider;
use NewDebugBar\NewDebugBarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class ProductionTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            NewDebugBarServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->get('/production-page', fn () => response('<!doctype html><html><body>Production</body></html>'));
    }
}
