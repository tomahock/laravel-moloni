<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\AbstractResource;
use Tomahock\Moloni\Tests\Support\MocksHttpClient;
use Tomahock\Moloni\Tests\TestCase;

class AbstractResourceTest extends TestCase
{
    use MocksHttpClient;

    private function makeResource(string $endpoint, MoloniClient $client): AbstractResource
    {
        return new class ($client, $endpoint) extends AbstractResource {
            public function __construct(MoloniClient $client, string $ep)
            {
                parent::__construct($client);
                $this->endpoint = $ep;
            }
        };
    }

    private function clientExpecting(string $endpoint, array $body): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with($endpoint, Mockery::on(fn ($d) => ($d['company_id'] ?? null) === 1))
            ->andReturn(['result' => 'ok']);

        return $mock;
    }

    // ─── companyId guard ──────────────────────────────────────────────────────

    public function test_throws_runtime_exception_when_company_id_not_set(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(null);

        $resource = $this->makeResource('customers', $mock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Company ID not set/');

        $resource->getAll();
    }

    // ─── getAll ───────────────────────────────────────────────────────────────

    public function test_get_all_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(5);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/getAll/', Mockery::subset(['company_id' => 5]))
            ->andReturn([]);

        $this->makeResource('customers', $mock)->getAll();
    }

    public function test_get_all_passes_extra_params(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/getAll/', Mockery::subset(['company_id' => 1, 'search' => 'Acme']))
            ->andReturn([]);

        $this->makeResource('customers', $mock)->getAll(['search' => 'Acme']);
    }

    // ─── getOne ───────────────────────────────────────────────────────────────

    public function test_get_one_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/getOne/', Mockery::subset(['company_id' => 1, 'customer_id' => 42]))
            ->andReturn(['customer_id' => 42]);

        $this->makeResource('customers', $mock)->getOne(['customer_id' => 42]);
    }

    // ─── insert ───────────────────────────────────────────────────────────────

    public function test_insert_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/insert/', Mockery::subset(['company_id' => 1, 'name' => 'Acme']))
            ->andReturn(['customer_id' => 10]);

        $this->makeResource('customers', $mock)->insert(['name' => 'Acme']);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_update_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/update/', Mockery::subset(['company_id' => 1, 'customer_id' => 5]))
            ->andReturn(['valid' => 1]);

        $this->makeResource('customers', $mock)->update(['customer_id' => 5]);
    }

    // ─── delete ───────────────────────────────────────────────────────────────

    public function test_delete_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/delete/', Mockery::subset(['company_id' => 1, 'customer_id' => 5]))
            ->andReturn(['valid' => 1]);

        $this->makeResource('customers', $mock)->delete(['customer_id' => 5]);
    }

    // ─── countAll ─────────────────────────────────────────────────────────────

    public function test_count_all_posts_to_correct_endpoint(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->once()
            ->with('/customers/countAll/', Mockery::subset(['company_id' => 1]))
            ->andReturn(['count' => 42]);

        $result = $this->makeResource('customers', $mock)->countAll();

        $this->assertSame(['count' => 42], $result);
    }

    // ─── endpoint builder ─────────────────────────────────────────────────────

    public function test_build_endpoint_constructs_correct_path(): void
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        $mock->shouldReceive('post')
            ->with('/products/getAll/', Mockery::any())
            ->andReturn([]);

        $resource = $this->makeResource('products', $mock);
        $resource->getAll(); // triggers buildEndpoint internally
    }
}
