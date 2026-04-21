<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\Countries;
use Tomahock\Moloni\Resources\Currencies;
use Tomahock\Moloni\Tests\TestCase;

/**
 * Tests for global endpoints that do not require a company_id.
 */
class GlobalResourcesTest extends TestCase
{
    private function mockClient(): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        // getCompanyId should never be called for these resources
        $mock->shouldNotReceive('getCompanyId');
        return $mock;
    }

    // ─── Countries ────────────────────────────────────────────────────────────

    public function test_countries_get_all_does_not_send_company_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/countries/getAll/', Mockery::on(fn ($d) => !array_key_exists('company_id', $d)))
            ->andReturn([['country_id' => 1, 'iso' => 'PT']]);

        (new Countries($client))->getAll();
    }

    public function test_countries_get_all_passes_extra_params(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/countries/getAll/', ['search' => 'Portugal'])
            ->andReturn([]);

        (new Countries($client))->getAll(['search' => 'Portugal']);
    }

    // ─── Currencies ───────────────────────────────────────────────────────────

    public function test_currencies_get_all_does_not_send_company_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/currencies/getAll/', Mockery::on(fn ($d) => !array_key_exists('company_id', $d)))
            ->andReturn([['currency_id' => 1, 'iso' => 'EUR']]);

        (new Currencies($client))->getAll();
    }
}
