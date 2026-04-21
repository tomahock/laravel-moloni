<?php

namespace Tomahock\Moloni;

use Illuminate\Support\ServiceProvider;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;

class MoloniServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moloni.php', 'moloni');

        $this->app->singleton(MoloniAuthenticator::class, function ($app) {
            return new MoloniAuthenticator($app['config']['moloni']);
        });

        $this->app->singleton(MoloniClient::class, function ($app) {
            return new MoloniClient(
                $app->make(MoloniAuthenticator::class),
                $app['config']['moloni']
            );
        });

        $this->app->singleton(Moloni::class, function ($app) {
            return new Moloni(
                $app->make(MoloniClient::class),
                $app->make(MoloniAuthenticator::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/moloni.php' => config_path('moloni.php'),
            ], 'moloni-config');
        }
    }
}
