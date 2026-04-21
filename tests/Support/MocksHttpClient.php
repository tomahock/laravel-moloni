<?php

namespace Tomahock\Moloni\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Http\MoloniClient;

trait MocksHttpClient
{
    protected array $requestHistory = [];

    /**
     * Build a MoloniClient with mocked HTTP responses and an authenticator
     * that always returns 'test_access_token'.
     */
    protected function makeClient(array $responses, ?array $config = null): MoloniClient
    {
        $config ??= $this->defaultClientConfig();
        $baseUri = rtrim($config['base_url'], '/') . '/';
        $guzzle = $this->buildGuzzleWithMock($responses, $baseUri);

        $auth = \Mockery::mock(MoloniAuthenticator::class);
        $auth->shouldReceive('getAccessToken')->andReturn('test_access_token');
        $auth->shouldReceive('forgetTokens');

        $client = new MoloniClient($auth, $config);
        $this->injectHttpClient($client, $guzzle);

        return $client;
    }

    /**
     * Build a MoloniAuthenticator with mocked HTTP responses.
     */
    protected function makeAuthenticator(array $responses, array $config = []): MoloniAuthenticator
    {
        $config = array_merge($this->defaultAuthConfig(), $config);
        $guzzle = $this->buildGuzzleWithMock($responses);

        $auth = new MoloniAuthenticator($config);
        $this->injectHttpClient($auth, $guzzle);

        return $auth;
    }

    protected function jsonResponse(mixed $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    protected function defaultClientConfig(): array
    {
        return [
            'base_url' => 'https://api.moloni.pt/v1',
            'timeout' => 5,
            'retry' => ['times' => 1, 'sleep' => 0],
        ];
    }

    protected function defaultAuthConfig(): array
    {
        return [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_secret',
            'username' => 'user@test.com',
            'password' => 'pass',
            'grant_type' => 'password',
            'redirect_uri' => 'https://app.test/callback',
            'base_url' => 'https://api.moloni.pt/v1',
            'token_cache_prefix' => 'moloni_token',
            'token_cache_driver' => null,
            'timeout' => 5,
        ];
    }

    protected function buildGuzzleWithMock(array $responses, ?string $baseUri = null): Client
    {
        $this->requestHistory = [];
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->requestHistory));

        $options = ['handler' => $stack];
        if ($baseUri !== null) {
            $options['base_uri'] = $baseUri;
        }

        return new Client($options);
    }

    protected function injectHttpClient(object $target, Client $guzzle): void
    {
        $ref = new \ReflectionClass($target);
        $prop = $ref->getProperty('httpClient');
        $prop->setAccessible(true);
        $prop->setValue($target, $guzzle);
    }

    protected function lastRequest(): ?\GuzzleHttp\Psr7\Request
    {
        return $this->requestHistory ? $this->requestHistory[array_key_last($this->requestHistory)]['request'] : null;
    }

    protected function requestCount(): int
    {
        return count($this->requestHistory);
    }
}
