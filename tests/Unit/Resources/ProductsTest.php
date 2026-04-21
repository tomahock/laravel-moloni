<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\Products;
use Tomahock\Moloni\Tests\TestCase;

class ProductsTest extends TestCase
{
    private function mockClient(): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        return $mock;
    }

    public function test_get_by_reference_calls_get_all_with_reference_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/getAll/', Mockery::subset(['company_id' => 1, 'reference' => 'REF-001']))
            ->andReturn([]);

        (new Products($client))->getByReference('REF-001');
    }

    public function test_search_calls_get_all_with_search_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/getAll/', Mockery::subset(['company_id' => 1, 'search' => 'notebook']))
            ->andReturn([]);

        (new Products($client))->search('notebook');
    }

    public function test_get_by_category_calls_get_all_with_category_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/getAll/', Mockery::subset(['company_id' => 1, 'category_id' => 3]))
            ->andReturn([]);

        (new Products($client))->getByCategory(3);
    }

    public function test_update_stock_without_warehouse_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/updateStock/', Mockery::on(function ($data) {
                return $data['company_id'] === 1
                    && $data['product_id'] === 10
                    && $data['qty'] === 5.0
                    && $data['movement'] === 'add'
                    && !array_key_exists('warehouse_id', $data);
            }))
            ->andReturn(['valid' => 1]);

        (new Products($client))->updateStock(10, 5.0);
    }

    public function test_update_stock_with_warehouse_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/updateStock/', Mockery::subset([
                'product_id' => 10,
                'qty' => 3.0,
                'movement' => 'sub',
                'warehouse_id' => 2,
            ]))
            ->andReturn(['valid' => 1]);

        (new Products($client))->updateStock(10, 3.0, 'sub', 2);
    }

    public function test_update_stock_defaults_movement_to_add(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/updateStock/', Mockery::subset(['movement' => 'add']))
            ->andReturn(['valid' => 1]);

        (new Products($client))->updateStock(10, 1.0);
    }

    public function test_insert_product_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/products/insert/', Mockery::any())
            ->andReturn(['product_id' => 55]);

        $result = (new Products($client))->insert([
            'name' => 'Produto A',
            'reference' => 'PA-001',
            'price' => 9.99,
        ]);

        $this->assertSame(['product_id' => 55], $result);
    }
}
