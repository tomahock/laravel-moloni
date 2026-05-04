<?php

namespace Tomahock\Moloni;

use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\BankAccounts;
use Tomahock\Moloni\Resources\Companies;
use Tomahock\Moloni\Resources\Countries;
use Tomahock\Moloni\Resources\CreditNotes;
use Tomahock\Moloni\Resources\Currencies;
use Tomahock\Moloni\Resources\Customers;
use Tomahock\Moloni\Resources\DebitNotes;
use Tomahock\Moloni\Resources\DeliveryMethods;
use Tomahock\Moloni\Resources\DeliveryNotes;
use Tomahock\Moloni\Resources\MaturityDates;
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

class Moloni
{
    private MoloniClient $client;
    private MoloniAuthenticator $authenticator;

    public function __construct(MoloniClient $client, MoloniAuthenticator $authenticator)
    {
        $this->client = $client;
        $this->authenticator = $authenticator;
    }

    public function company(int $companyId): static
    {
        $this->client->setCompanyId($companyId);
        return $this;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authenticator->getAuthorizationUrl();
    }

    public function handleAuthorizationCallback(string $code): array
    {
        return $this->authenticator->handleAuthorizationCallback($code);
    }

    // Entities

    public function customers(): Customers
    {
        return new Customers($this->client);
    }

    public function suppliers(): Suppliers
    {
        return new Suppliers($this->client);
    }

    // Products

    public function products(): Products
    {
        return new Products($this->client);
    }

    public function productCategories(): ProductCategories
    {
        return new ProductCategories($this->client);
    }

    // Documents

    public function invoices(): Invoices
    {
        return new Invoices($this->client);
    }

    public function receipts(): Receipts
    {
        return new Receipts($this->client);
    }

    public function creditNotes(): CreditNotes
    {
        return new CreditNotes($this->client);
    }

    public function debitNotes(): DebitNotes
    {
        return new DebitNotes($this->client);
    }

    public function simplifiedInvoices(): SimplifiedInvoices
    {
        return new SimplifiedInvoices($this->client);
    }

    public function invoiceReceipts(): InvoiceReceipts
    {
        return new InvoiceReceipts($this->client);
    }

    public function estimates(): Estimates
    {
        return new Estimates($this->client);
    }

    public function purchaseOrders(): PurchaseOrders
    {
        return new PurchaseOrders($this->client);
    }

    public function deliveryNotes(): DeliveryNotes
    {
        return new DeliveryNotes($this->client);
    }

    public function waybills(): Waybills
    {
        return new Waybills($this->client);
    }

    public function supplierInvoices(): SupplierInvoices
    {
        return new SupplierInvoices($this->client);
    }

    // Settings

    public function taxes(): Taxes
    {
        return new Taxes($this->client);
    }

    public function paymentMethods(): PaymentMethods
    {
        return new PaymentMethods($this->client);
    }

    public function maturityDates(): MaturityDates
    {
        return new MaturityDates($this->client);
    }

    public function deliveryMethods(): DeliveryMethods
    {
        return new DeliveryMethods($this->client);
    }

    public function warehouses(): Warehouses
    {
        return new Warehouses($this->client);
    }

    public function measurementUnits(): MeasurementUnits
    {
        return new MeasurementUnits($this->client);
    }

    public function documentSets(): DocumentSets
    {
        return new DocumentSets($this->client);
    }

    public function bankAccounts(): BankAccounts
    {
        return new BankAccounts($this->client);
    }

    // Company

    public function companies(): Companies
    {
        return new Companies($this->client);
    }

    // Global data

    public function countries(): Countries
    {
        return new Countries($this->client);
    }

    public function currencies(): Currencies
    {
        return new Currencies($this->client);
    }
}
