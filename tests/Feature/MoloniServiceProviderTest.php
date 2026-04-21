<?php

namespace Tomahock\Moloni\Tests\Feature;

use Tomahock\Moloni\Facades\Moloni as MoloniFacade;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Moloni;
use Tomahock\Moloni\Tests\TestCase;

class MoloniServiceProviderTest extends TestCase
{
    public function test_moloni_class_is_bound_in_container(): void
    {
        $this->assertInstanceOf(Moloni::class, $this->app->make(Moloni::class));
    }

    public function test_moloni_client_is_singleton(): void
    {
        $a = $this->app->make(MoloniClient::class);
        $b = $this->app->make(MoloniClient::class);

        $this->assertSame($a, $b);
    }

    public function test_moloni_authenticator_is_singleton(): void
    {
        $a = $this->app->make(MoloniAuthenticator::class);
        $b = $this->app->make(MoloniAuthenticator::class);

        $this->assertSame($a, $b);
    }

    public function test_moloni_manager_is_singleton(): void
    {
        $a = $this->app->make(Moloni::class);
        $b = $this->app->make(Moloni::class);

        $this->assertSame($a, $b);
    }

    public function test_config_is_merged_with_correct_defaults(): void
    {
        $config = $this->app['config']['moloni'];

        $this->assertArrayHasKey('client_id', $config);
        $this->assertArrayHasKey('client_secret', $config);
        $this->assertArrayHasKey('grant_type', $config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('token_cache_driver', $config);
        $this->assertArrayHasKey('token_cache_prefix', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('retry', $config);
    }

    public function test_default_base_url_is_moloni_api(): void
    {
        $this->assertSame('https://api.moloni.pt/v1', $this->app['config']['moloni.base_url']);
    }

    public function test_default_grant_type_is_password(): void
    {
        $this->assertSame('password', $this->app['config']['moloni.grant_type']);
    }

    public function test_facade_resolves_to_moloni_instance(): void
    {
        $this->assertInstanceOf(Moloni::class, MoloniFacade::getFacadeRoot());
    }

    public function test_env_credentials_are_read_from_config(): void
    {
        $this->assertSame('test_client_id', $this->app['config']['moloni.client_id']);
        $this->assertSame('test_client_secret', $this->app['config']['moloni.client_secret']);
    }
}
