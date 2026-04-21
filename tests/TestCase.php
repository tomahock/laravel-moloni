<?php

namespace Tomahock\Moloni\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as Orchestra;
use Tomahock\Moloni\MoloniServiceProvider;

abstract class TestCase extends Orchestra
{
    use MockeryPHPUnitIntegration;

    protected function getPackageProviders($app): array
    {
        return [MoloniServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Moloni' => \Tomahock\Moloni\Facades\Moloni::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('moloni.client_id', 'test_client_id');
        $app['config']->set('moloni.client_secret', 'test_client_secret');
        $app['config']->set('moloni.username', 'test@example.com');
        $app['config']->set('moloni.password', 'test_password');
        $app['config']->set('moloni.grant_type', 'password');
        $app['config']->set('moloni.base_url', 'https://api.moloni.pt/v1');
    }
}
