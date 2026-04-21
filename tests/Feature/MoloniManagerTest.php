<?php

namespace Tomahock\Moloni\Tests\Feature;

use Mockery;
use Tomahock\Moloni\Facades\Moloni as MoloniFacade;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Moloni;
use Tomahock\Moloni\Resources\BankAccounts;
use Tomahock\Moloni\Resources\Companies;
use Tomahock\Moloni\Resources\Countries;
use Tomahock\Moloni\Resources\CreditNotes;
use Tomahock\Moloni\Resources\Currencies;
use Tomahock\Moloni\Resources\Customers;
use Tomahock\Moloni\Resources\DebitNotes;
use Tomahock\Moloni\Resources\DeliveryNotes;
use Tomahock\Moloni\Resources\DocumentSets;
use Tomahock\Moloni\Resources\Estimates;
use Tomahock\Moloni\Resources\InvoiceReceipts;
use Tomahock\Moloni\Resources\Invoices;
use Tomahock\Moloni\Resources\MeasurementUnits;
use Tomahock\Moloni\Resources\PaymentMethods;
use Tomahock\Moloni\Resources\ProductCategories;
use Tomahock\Moloni\Resources\Products;
use Tomahock\Moloni\Resources\PurchaseOrders;
use Tomahock\Moloni\Resources\Receipts;
use Tomahock\Moloni\Resources\SimplifiedInvoices;
use Tomahock\Moloni\Resources\SupplierInvoices;
use Tomahock\Moloni\Resources\Suppliers;
use Tomahock\Moloni\Resources\Taxes;
use Tomahock\Moloni\Resources\Warehouses;
use Tomahock\Moloni\Resources\Waybills;
use Tomahock\Moloni\Tests\TestCase;

class MoloniManagerTest extends TestCase
{
    private Moloni $moloni;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Mockery::mock(MoloniClient::class);
        $client->shouldReceive('setCompanyId')->andReturnSelf();
        $client->shouldReceive('getCompanyId')->andReturn(1);

        $auth = Mockery::mock(MoloniAuthenticator::class);

        $this->moloni = new Moloni($client, $auth);
    }

    // ─── company() ────────────────────────────────────────────────────────────

    public function test_company_sets_id_and_returns_self(): void
    {
        $result = $this->moloni->company(42);

        $this->assertSame($this->moloni, $result);
    }

    // ─── Auth delegation ──────────────────────────────────────────────────────

    public function test_get_authorization_url_delegates_to_authenticator(): void
    {
        $client = Mockery::mock(MoloniClient::class);
        $auth   = Mockery::mock(MoloniAuthenticator::class);
        $auth->shouldReceive('getAuthorizationUrl')->once()->andReturn('https://example.com/oauth');

        $moloni = new Moloni($client, $auth);

        $this->assertSame('https://example.com/oauth', $moloni->getAuthorizationUrl());
    }

    public function test_handle_authorization_callback_delegates_to_authenticator(): void
    {
        $tokens = ['access_token' => 'at', 'refresh_token' => 'rt'];

        $client = Mockery::mock(MoloniClient::class);
        $auth   = Mockery::mock(MoloniAuthenticator::class);
        $auth->shouldReceive('handleAuthorizationCallback')->once()->with('mycode')->andReturn($tokens);

        $moloni = new Moloni($client, $auth);

        $this->assertSame($tokens, $moloni->handleAuthorizationCallback('mycode'));
    }

    // ─── Resource factory methods ─────────────────────────────────────────────

    /**
     * @dataProvider resourceFactoryProvider
     */
    public function test_resource_factory_returns_correct_instance(string $method, string $expectedClass): void
    {
        $resource = $this->moloni->{$method}();

        $this->assertInstanceOf($expectedClass, $resource);
    }

    public static function resourceFactoryProvider(): array
    {
        return [
            // Entities
            ['customers',          Customers::class],
            ['suppliers',          Suppliers::class],
            // Products
            ['products',           Products::class],
            ['productCategories',  ProductCategories::class],
            // Documents
            ['invoices',           Invoices::class],
            ['receipts',           Receipts::class],
            ['creditNotes',        CreditNotes::class],
            ['debitNotes',         DebitNotes::class],
            ['simplifiedInvoices', SimplifiedInvoices::class],
            ['invoiceReceipts',    InvoiceReceipts::class],
            ['estimates',          Estimates::class],
            ['purchaseOrders',     PurchaseOrders::class],
            ['deliveryNotes',      DeliveryNotes::class],
            ['waybills',           Waybills::class],
            ['supplierInvoices',   SupplierInvoices::class],
            // Settings
            ['taxes',              Taxes::class],
            ['paymentMethods',     PaymentMethods::class],
            ['warehouses',         Warehouses::class],
            ['measurementUnits',   MeasurementUnits::class],
            ['documentSets',       DocumentSets::class],
            ['bankAccounts',       BankAccounts::class],
            // Company
            ['companies',          Companies::class],
            // Global
            ['countries',          Countries::class],
            ['currencies',         Currencies::class],
        ];
    }

    // ─── Facade ───────────────────────────────────────────────────────────────

    public function test_facade_company_returns_fluent_interface(): void
    {
        $result = MoloniFacade::company(1);

        $this->assertInstanceOf(Moloni::class, $result);
    }

    public function test_facade_customers_returns_customers_resource(): void
    {
        $this->assertInstanceOf(Customers::class, MoloniFacade::company(1)->customers());
    }

    public function test_facade_invoices_returns_invoices_resource(): void
    {
        $this->assertInstanceOf(Invoices::class, MoloniFacade::company(1)->invoices());
    }
}
