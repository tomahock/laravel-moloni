<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\BankAccounts;
use Tomahock\Moloni\Resources\DocumentSets;
use Tomahock\Moloni\Resources\MeasurementUnits;
use Tomahock\Moloni\Resources\PaymentMethods;
use Tomahock\Moloni\Resources\ProductCategories;
use Tomahock\Moloni\Resources\Taxes;
use Tomahock\Moloni\Resources\Warehouses;
use Tomahock\Moloni\Tests\TestCase;

/**
 * Verifies that each settings/configuration resource uses the correct endpoint slug.
 */
class SettingsResourcesTest extends TestCase
{
    private function mockClient(): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        return $mock;
    }

    /**
     * @dataProvider settingsResourceProvider
     */
    public function test_get_all_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/getAll/", Mockery::subset(['company_id' => 1]))
            ->andReturn([]);

        (new $class($client))->getAll();
    }

    /**
     * @dataProvider settingsResourceProvider
     */
    public function test_insert_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/insert/", Mockery::subset(['company_id' => 1]))
            ->andReturn(['id' => 1]);

        (new $class($client))->insert(['name' => 'Test']);
    }

    /**
     * @dataProvider settingsResourceProvider
     */
    public function test_delete_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/delete/", Mockery::subset(['company_id' => 1]))
            ->andReturn(['valid' => 1]);

        (new $class($client))->delete(['id' => 1]);
    }

    public static function settingsResourceProvider(): array
    {
        return [
            'taxes'            => [Taxes::class,            'taxes'],
            'paymentMethods'   => [PaymentMethods::class,   'paymentMethods'],
            'warehouses'       => [Warehouses::class,       'warehouses'],
            'measurementUnits' => [MeasurementUnits::class, 'measurementUnits'],
            'documentSets'     => [DocumentSets::class,     'documentSets'],
            'bankAccounts'     => [BankAccounts::class,     'bankAccounts'],
            'productCategories'=> [ProductCategories::class,'productCategories'],
        ];
    }
}
