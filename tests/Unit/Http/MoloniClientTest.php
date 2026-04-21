<?php

namespace Tomahock\Moloni\Tests\Unit\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tomahock\Moloni\Exceptions\MoloniAuthException;
use Tomahock\Moloni\Exceptions\MoloniException;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;
use Tomahock\Moloni\Tests\Support\MocksHttpClient;
use Tomahock\Moloni\Tests\TestCase;

class MoloniClientTest extends TestCase
{
    use MocksHttpClient;

    // ─── companyId ────────────────────────────────────────────────────────────

    public function test_company_id_is_null_by_default(): void
    {
        $auth = Mockery::mock(MoloniAuthenticator::class);
        $client = new MoloniClient($auth, $this->defaultClientConfig());

        $this->assertNull($client->getCompanyId());
    }

    public function test_set_company_id_stores_value(): void
    {
        $auth = Mockery::mock(MoloniAuthenticator::class);
        $client = new MoloniClient($auth, $this->defaultClientConfig());

        $client->setCompanyId(99);

        $this->assertSame(99, $client->getCompanyId());
    }

    public function test_set_company_id_returns_self_for_chaining(): void
    {
        $auth = Mockery::mock(MoloniAuthenticator::class);
        $client = new MoloniClient($auth, $this->defaultClientConfig());

        $this->assertSame($client, $client->setCompanyId(1));
    }

    // ─── Successful requests ──────────────────────────────────────────────────

    public function test_post_sends_access_token_as_query_param(): void
    {
        $client = $this->makeClient([$this->jsonResponse([])]);

        $client->post('/customers/getAll/', ['company_id' => 1]);

        $req = $this->lastRequest();
        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('test_access_token', $query['access_token']);
    }

    public function test_post_with_body_sends_json_flag_in_query(): void
    {
        $client = $this->makeClient([$this->jsonResponse(['customer_id' => 5])]);

        $client->post('/customers/insert/', ['company_id' => 1, 'name' => 'Acme']);

        $req = $this->lastRequest();
        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('true', $query['json']);
    }

    public function test_post_with_body_sends_data_as_json_payload(): void
    {
        $client = $this->makeClient([$this->jsonResponse(['customer_id' => 5])]);
        $data = ['company_id' => 1, 'name' => 'Acme', 'vat' => '123456789'];

        $client->post('/customers/insert/', $data);

        $req = $this->lastRequest();
        $body = json_decode($req->getBody()->getContents(), true);
        $this->assertSame('Acme', $body['name']);
        $this->assertSame('123456789', $body['vat']);
    }

    public function test_post_returns_decoded_array(): void
    {
        $payload = [['customer_id' => 1, 'name' => 'John']];
        $client = $this->makeClient([$this->jsonResponse($payload)]);

        $result = $client->post('/customers/getAll/', ['company_id' => 1]);

        $this->assertSame($payload, $result);
    }

    public function test_get_request_sends_correct_method(): void
    {
        $client = $this->makeClient([$this->jsonResponse([])]);

        $client->get('/some/endpoint/');

        $this->assertSame('GET', $this->lastRequest()->getMethod());
    }

    public function test_post_request_sends_correct_method(): void
    {
        $client = $this->makeClient([$this->jsonResponse([])]);

        $client->post('/some/endpoint/', ['company_id' => 1]);

        $this->assertSame('POST', $this->lastRequest()->getMethod());
    }

    // ─── API error responses ──────────────────────────────────────────────────

    public function test_throws_moloni_exception_on_valid_zero_response(): void
    {
        $client = $this->makeClient([
            $this->jsonResponse(['valid' => 0, 'errors' => [['message' => 'Customer not found']]]),
        ]);

        $this->expectException(MoloniException::class);
        $this->expectExceptionMessage('Customer not found');

        $client->post('/customers/getOne/', ['company_id' => 1, 'customer_id' => 999]);
    }

    public function test_exception_carries_errors_array(): void
    {
        $errors = [
            ['message' => 'Name required'],
            ['message' => 'VAT invalid'],
        ];
        $client = $this->makeClient([
            $this->jsonResponse(['valid' => 0, 'errors' => $errors]),
        ]);

        try {
            $client->post('/customers/insert/', ['company_id' => 1]);
            $this->fail('Expected MoloniException');
        } catch (MoloniException $e) {
            $this->assertSame($errors, $e->getErrors());
        }
    }

    public function test_throws_with_generic_message_when_errors_empty(): void
    {
        $client = $this->makeClient([
            $this->jsonResponse(['valid' => 0, 'errors' => []]),
        ]);

        $this->expectException(MoloniException::class);
        $this->expectExceptionMessage('Unknown Moloni API error');

        $client->post('/customers/getAll/', ['company_id' => 1]);
    }

    public function test_throws_on_invalid_json_response(): void
    {
        $client = $this->makeClient([
            new Response(200, [], 'this is not json'),
        ]);

        $this->expectException(MoloniException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON response/');

        $client->post('/something/', ['company_id' => 1]);
    }

    // ─── Auth token expiry retry ──────────────────────────────────────────────

    public function test_clears_tokens_and_retries_once_on_auth_exception(): void
    {
        // getAccessToken throws on first call (token expired), then succeeds on retry
        $auth = Mockery::mock(MoloniAuthenticator::class);
        $auth->shouldReceive('getAccessToken')
            ->once()
            ->andThrow(new MoloniAuthException('Token expired'));
        $auth->shouldReceive('forgetTokens')->once();
        $auth->shouldReceive('getAccessToken')
            ->once()
            ->andReturn('fresh_token');

        $client = new MoloniClient($auth, $this->defaultClientConfig());
        $this->injectMockResponses($client, [
            $this->jsonResponse([['id' => 1]]),
        ]);

        $result = $client->post('/invoices/getAll/', ['company_id' => 1]);

        $this->assertSame([['id' => 1]], $result);
    }

    public function test_rethrows_auth_exception_on_second_failure(): void
    {
        $auth = Mockery::mock(MoloniAuthenticator::class);
        $auth->shouldReceive('getAccessToken')
            ->twice()
            ->andThrow(new MoloniAuthException('Auth failed both times'));
        $auth->shouldReceive('forgetTokens')->once();

        $client = new MoloniClient($auth, $this->defaultClientConfig());
        $this->injectMockResponses($client, []);

        $this->expectException(MoloniAuthException::class);

        $client->post('/invoices/getAll/', ['company_id' => 1]);
    }

    // ─── HTTP retry on network errors ─────────────────────────────────────────

    public function test_retries_once_on_guzzle_exception_and_succeeds(): void
    {
        $config = array_merge($this->defaultClientConfig(), ['retry' => ['times' => 1, 'sleep' => 0]]);
        $client = $this->makeClient([
            new ConnectException('Connection refused', new Request('POST', '/')),
            $this->jsonResponse([['id' => 1]]),
        ], $config);

        $result = $client->post('/invoices/getAll/', ['company_id' => 1]);

        $this->assertSame([['id' => 1]], $result);
        $this->assertSame(2, $this->requestCount());
    }

    public function test_throws_moloni_exception_after_exhausting_retries(): void
    {
        $config = array_merge($this->defaultClientConfig(), ['retry' => ['times' => 1, 'sleep' => 0]]);
        $client = $this->makeClient([
            new ConnectException('No network', new Request('POST', '/')),
            new ConnectException('No network', new Request('POST', '/')),
        ], $config);

        $this->expectException(MoloniException::class);
        $this->expectExceptionMessageMatches('/HTTP request failed/');

        $client->post('/invoices/getAll/', ['company_id' => 1]);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function injectMockResponses(MoloniClient $client, array $responses): void
    {
        $baseUri = rtrim($this->defaultClientConfig()['base_url'], '/') . '/';
        $guzzle = $this->buildGuzzleWithMock($responses, $baseUri);

        $ref = new \ReflectionClass($client);
        $prop = $ref->getProperty('httpClient');
        $prop->setAccessible(true);
        $prop->setValue($client, $guzzle);
    }
}
