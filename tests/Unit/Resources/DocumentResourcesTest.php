<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\CreditNotes;
use Tomahock\Moloni\Resources\DebitNotes;
use Tomahock\Moloni\Resources\DeliveryNotes;
use Tomahock\Moloni\Resources\Estimates;
use Tomahock\Moloni\Resources\InvoiceReceipts;
use Tomahock\Moloni\Resources\Invoices;
use Tomahock\Moloni\Resources\PurchaseOrders;
use Tomahock\Moloni\Resources\Receipts;
use Tomahock\Moloni\Resources\SimplifiedInvoices;
use Tomahock\Moloni\Resources\SupplierInvoices;
use Tomahock\Moloni\Resources\Waybills;
use Tomahock\Moloni\Tests\TestCase;

/**
 * Verifies that each document type uses the correct endpoint slug
 * and inherits all AbstractDocument behaviour.
 */
class DocumentResourcesTest extends TestCase
{
    private function mockClient(): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn(1);
        return $mock;
    }

    /**
     * @dataProvider documentResourceProvider
     */
    public function test_get_all_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/getAll/", Mockery::any())
            ->andReturn([]);

        (new $class($client))->getAll();
    }

    /**
     * @dataProvider documentResourceProvider
     */
    public function test_insert_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/insert/", Mockery::any())
            ->andReturn(['document_id' => 1]);

        (new $class($client))->insert(['date' => '2024-01-01']);
    }

    /**
     * @dataProvider documentResourceProvider
     */
    public function test_get_pdf_link_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/getPDFLink/", Mockery::subset(['document_id' => 10]))
            ->andReturn(['url' => 'https://example.com/pdf']);

        (new $class($client))->getPdfLink(10);
    }

    /**
     * @dataProvider documentResourceProvider
     */
    public function test_send_email_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/sendEmail/", Mockery::subset(['document_id' => 10]))
            ->andReturn(['valid' => 1]);

        (new $class($client))->sendEmail(10, ['email' => 'test@example.com']);
    }

    /**
     * @dataProvider documentResourceProvider
     */
    public function test_get_by_customer_uses_correct_endpoint(string $class, string $expectedSlug): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with("/{$expectedSlug}/getAll/", Mockery::subset(['customer_id' => 7]))
            ->andReturn([]);

        (new $class($client))->getByCustomer(7);
    }

    public static function documentResourceProvider(): array
    {
        return [
            'invoices'          => [Invoices::class,          'invoices'],
            'receipts'          => [Receipts::class,          'receipts'],
            'creditNotes'       => [CreditNotes::class,       'creditNotes'],
            'debitNotes'        => [DebitNotes::class,        'debitNotes'],
            'simplifiedInvoices'=> [SimplifiedInvoices::class,'simplifiedInvoices'],
            'invoiceReceipts'   => [InvoiceReceipts::class,   'invoiceReceipts'],
            'estimates'         => [Estimates::class,         'estimates'],
            'purchaseOrders'    => [PurchaseOrders::class,    'purchaseOrders'],
            'deliveryNotes'     => [DeliveryNotes::class,     'deliveryNotes'],
            'waybills'          => [Waybills::class,          'waybills'],
            'supplierInvoices'  => [SupplierInvoices::class,  'supplierInvoices'],
        ];
    }
}
