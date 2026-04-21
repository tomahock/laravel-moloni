<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\Customers;
use Tomahock\Moloni\Tests\TestCase;

class CustomersTest extends TestCase
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
            ->with('/customers/getAll/', Mockery::subset(['company_id' => 1, 'vat' => '999999990']))
            ->andReturn([]);

        (new Customers($client))->getByVat('999999990');
    }

    public function test_get_by_email_calls_get_all_with_email_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/getAll/', Mockery::subset(['company_id' => 1, 'email' => 'john@doe.com']))
            ->andReturn([]);

        (new Customers($client))->getByEmail('john@doe.com');
    }

    public function test_search_calls_get_all_with_search_param(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/getAll/', Mockery::subset(['company_id' => 1, 'search' => 'Acme']))
            ->andReturn([]);

        (new Customers($client))->search('Acme');
    }

    public function test_get_next_number_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/getNextNumber/', Mockery::subset(['company_id' => 1]))
            ->andReturn(['next_number' => '00042']);

        $result = (new Customers($client))->getNextNumber();

        $this->assertSame(['next_number' => '00042'], $result);
    }

    public function test_get_all_returns_customers_array(): void
    {
        $expected = [
            ['customer_id' => 1, 'name' => 'Empresa A'],
            ['customer_id' => 2, 'name' => 'Empresa B'],
        ];

        $client = $this->mockClient();
        $client->shouldReceive('post')->andReturn($expected);

        $result = (new Customers($client))->getAll();

        $this->assertSame($expected, $result);
    }

    public function test_insert_passes_data_to_client(): void
    {
        $data = ['name' => 'Novo Cliente', 'vat' => '123456789', 'country_id' => 1];
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/insert/', Mockery::subset($data))
            ->andReturn(['customer_id' => 99]);

        $result = (new Customers($client))->insert($data);

        $this->assertSame(['customer_id' => 99], $result);
    }

    public function test_update_passes_data_to_client(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/update/', Mockery::subset(['customer_id' => 5, 'name' => 'Updated']))
            ->andReturn(['valid' => 1]);

        (new Customers($client))->update(['customer_id' => 5, 'name' => 'Updated']);
    }

    public function test_delete_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/customers/delete/', Mockery::subset(['customer_id' => 5]))
            ->andReturn(['valid' => 1]);

        (new Customers($client))->delete(['customer_id' => 5]);
    }
}
