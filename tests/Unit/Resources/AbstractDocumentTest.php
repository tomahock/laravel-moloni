<?php

namespace Tomahock\Moloni\Tests\Unit\Resources;

use Mockery;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Resources\AbstractDocument;
use Tomahock\Moloni\Tests\TestCase;

class AbstractDocumentTest extends TestCase
{
    private function makeDocument(string $endpoint, MoloniClient $client): AbstractDocument
    {
        return new class ($client, $endpoint) extends AbstractDocument {
            public function __construct(MoloniClient $c, string $ep)
            {
                parent::__construct($c);
                $this->endpoint = $ep;
            }
        };
    }

    private function mockClient(int $companyId = 1): MoloniClient
    {
        $mock = Mockery::mock(MoloniClient::class);
        $mock->shouldReceive('getCompanyId')->andReturn($companyId);
        return $mock;
    }

    // ─── getByDate ────────────────────────────────────────────────────────────

    public function test_get_by_date_passes_date_params_to_get_all(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with(
                '/invoices/getAll/',
                Mockery::subset(['company_id' => 1, 'date' => '2024-01-01', 'expiration_date' => '2024-01-31'])
            )
            ->andReturn([]);

        $this->makeDocument('invoices', $client)->getByDate('2024-01-01', '2024-01-31');
    }

    public function test_get_by_date_merges_extra_params(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/getAll/', Mockery::subset([
                'date' => '2024-01-01',
                'expiration_date' => '2024-01-31',
                'customer_id' => 99,
            ]))
            ->andReturn([]);

        $this->makeDocument('invoices', $client)->getByDate('2024-01-01', '2024-01-31', ['customer_id' => 99]);
    }

    // ─── getByCustomer ────────────────────────────────────────────────────────

    public function test_get_by_customer_passes_customer_id_to_get_all(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/getAll/', Mockery::subset(['company_id' => 1, 'customer_id' => 7]))
            ->andReturn([]);

        $this->makeDocument('invoices', $client)->getByCustomer(7);
    }

    // ─── sendEmail ────────────────────────────────────────────────────────────

    public function test_send_email_posts_to_correct_endpoint_with_document_id(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/sendEmail/', Mockery::subset([
                'company_id' => 1,
                'document_id' => 55,
                'email' => 'customer@example.com',
            ]))
            ->andReturn(['valid' => 1]);

        $this->makeDocument('invoices', $client)->sendEmail(55, ['email' => 'customer@example.com']);
    }

    // ─── getPdfLink ───────────────────────────────────────────────────────────

    public function test_get_pdf_link_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/getPDFLink/', Mockery::subset(['company_id' => 1, 'document_id' => 33]))
            ->andReturn(['url' => 'https://example.com/doc.pdf']);

        $result = $this->makeDocument('invoices', $client)->getPdfLink(33);

        $this->assertSame(['url' => 'https://example.com/doc.pdf'], $result);
    }

    // ─── getNextNumber ────────────────────────────────────────────────────────

    public function test_get_next_number_posts_to_correct_endpoint(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/getNextNumber/', Mockery::subset(['company_id' => 1]))
            ->andReturn(['next_number' => 'FT 2024/1']);

        $result = $this->makeDocument('invoices', $client)->getNextNumber();

        $this->assertSame(['next_number' => 'FT 2024/1'], $result);
    }

    public function test_get_next_number_passes_extra_params(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('post')
            ->once()
            ->with('/invoices/getNextNumber/', Mockery::subset(['company_id' => 1, 'document_set_id' => 2]))
            ->andReturn([]);

        $this->makeDocument('invoices', $client)->getNextNumber(['document_set_id' => 2]);
    }
}
