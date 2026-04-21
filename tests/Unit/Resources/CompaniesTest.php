<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\Companies;
use Tomahock\Moloni\Tests\TestCase;

class CompaniesTest extends TestCase
{
    private function mockClient(int $companyId = 1): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn($companyId);
        return $mock;
    }

    public function test_get_all_does_not_send_company_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/companies/getAll/', Mockery::on(fn ($d) => !array_key_exists('company_id', $d)))
            ->andReturn([['company_id' => 1], ['company_id' => 2]]);

        (new Companies($client))->getAll();
    }

    public function test_get_one_uses_client_company_id_when_not_passed(): void
    {
        $client = $this->mockClient(42);
        $client->shouldReceive('post')
            ->once()
            ->with('/companies/getOne/', ['company_id' => 42])
            ->andReturn(['company_id' => 42, 'name' => 'Empresa']);

        (new Companies($client))->getOne();
    }

    public function test_get_one_uses_passed_company_id_over_client(): void
    {
        $client = $this->mockClient(42);
        $client->shouldReceive('post')
            ->once()
            ->with('/companies/getOne/', ['company_id' => 99])
            ->andReturn(['company_id' => 99]);

        (new Companies($client))->getOne(['company_id' => 99]);
    }

    public function test_update_includes_company_id(): void
    {
        $client = $this->mockClient(1);
        $client->shouldReceive('post')
            ->once()
            ->with('/companies/update/', Mockery::subset(['company_id' => 1, 'name' => 'Nova Empresa']))
            ->andReturn(['valid' => 1]);

        (new Companies($client))->update(['name' => 'Nova Empresa']);
    }
}
