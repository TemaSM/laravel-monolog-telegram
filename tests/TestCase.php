<?php

namespace TheCoder\MonologTelegram\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.debug', true);
    }

    protected function getPackageProviders($app): array
    {
        return [];
    }
}
