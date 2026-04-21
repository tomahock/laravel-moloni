<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\Suppliers;
use Tomahock\Moloni\Tests\TestCase;

class SuppliersTest extends TestCase
{
    private function mockClient(): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        return $mock;
    }

    public function test_get_by_vat_calls_get_all_with_vat_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/suppliers/getAll/', Mockery::subset(['company_id' => 1, 'vat' => '508025338']))
            ->andReturn([]);

        (new Suppliers($client))->getByVat('508025338');
    }

    public function test_search_calls_get_all_with_search_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/suppliers/getAll/', Mockery::subset(['company_id' => 1, 'search' => 'Distribuidor']))
            ->andReturn([]);

        (new Suppliers($client))->search('Distribuidor');
    }

    public function test_insert_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/suppliers/insert/', Mockery::any())
            ->andReturn(['supplier_id' => 7]);

        $result = (new Suppliers($client))->insert(['name' => 'Fornecedor XYZ']);

        $this->assertSame(['supplier_id' => 7], $result);
    }
}
