<?php

namespace Tomahock\Moloni\Facades;

use Illuminate\Support\Facades\Facade;
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

/**
 * @method static \Tomahock\Moloni\Moloni company(int $companyId)
 * @method static string getAuthorizationUrl()
 * @method static array handleAuthorizationCallback(string $code)
 * @method static Customers customers()
 * @method static Suppliers suppliers()
 * @method static Products products()
 * @method static ProductCategories productCategories()
 * @method static Invoices invoices()
 * @method static Receipts receipts()
 * @method static CreditNotes creditNotes()
 * @method static DebitNotes debitNotes()
 * @method static SimplifiedInvoices simplifiedInvoices()
 * @method static InvoiceReceipts invoiceReceipts()
 * @method static Estimates estimates()
 * @method static PurchaseOrders purchaseOrders()
 * @method static DeliveryNotes deliveryNotes()
 * @method static Waybills waybills()
 * @method static SupplierInvoices supplierInvoices()
 * @method static Taxes taxes()
 * @method static PaymentMethods paymentMethods()
 * @method static Warehouses warehouses()
 * @method static MeasurementUnits measurementUnits()
 * @method static DocumentSets documentSets()
 * @method static BankAccounts bankAccounts()
 * @method static Companies companies()
 * @method static Countries countries()
 * @method static Currencies currencies()
 */
class Moloni extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tomahock\Moloni\Moloni::class;
    }
}
