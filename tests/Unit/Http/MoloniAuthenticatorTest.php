<?php

namespace Tomahock\Moloni\Tests\Unit\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Tomahock\Moloni\Exceptions\MoloniAuthException;
use Tomahock\Moloni\Http\MoloniAuthenticator;
use Tomahock\Moloni\Tests\Support\MocksHttpClient;
use Tomahock\Moloni\Tests\TestCase;

class MoloniAuthenticatorTest extends TestCase
{
    use MocksHttpClient;

    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->config = $this->defaultAuthConfig();
    }

    // ─── getAccessToken ───────────────────────────────────────────────────────

    public function test_returns_cached_access_token_without_http_call(): void
    {
        Cache::put('moloni_token_access_token', 'cached_token', now()->addHour());

        $auth = $this->makeAuthenticator([]); // no responses needed

        $this->assertSame('cached_token', $auth->getAccessToken());
        $this->assertSame(0, $this->requestCount());
    }

    public function test_fetches_token_via_password_grant_when_cache_empty(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
                'token_type' => 'bearer',
                'refresh_token' => 'new_refresh_token',
            ]),
        ]);

        $token = $auth->getAccessToken();

        $this->assertSame('new_access_token', $token);
        $this->assertSame(1, $this->requestCount());

        $req = $this->lastRequest();
        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('password', $query['grant_type']);
        $this->assertSame('test_client_id', $query['client_id']);
        $this->assertSame('user@test.com', $query['username']);
    }

    public function test_caches_access_token_after_fetch(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'cached_later',
                'expires_in' => 3600,
                'refresh_token' => 'r',
            ]),
        ]);

        $auth->getAccessToken();

        $this->assertSame('cached_later', Cache::get('moloni_token_access_token'));
    }

    public function test_caches_refresh_token_after_fetch(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'at',
                'expires_in' => 3600,
                'refresh_token' => 'stored_refresh',
            ]),
        ]);

        $auth->getAccessToken();

        $this->assertSame('stored_refresh', Cache::get('moloni_token_refresh_token'));
    }

    public function test_uses_refresh_token_when_access_token_expired(): void
    {
        Cache::put('moloni_token_refresh_token', 'existing_refresh', now()->addDays(13));

        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'refreshed_token',
                'expires_in' => 3600,
                'refresh_token' => 'new_refresh',
            ]),
        ]);

        $token = $auth->getAccessToken();

        $this->assertSame('refreshed_token', $token);

        $req = $this->lastRequest();
        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('refresh_token', $query['grant_type']);
        $this->assertSame('existing_refresh', $query['refresh_token']);
    }

    public function test_throws_when_response_has_no_access_token(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'error' => 'invalid_grant',
                'error_description' => 'Bad credentials provided',
            ]),
        ]);

        $this->expectException(MoloniAuthException::class);
        $this->expectExceptionMessageMatches('/Bad credentials provided/');

        $auth->getAccessToken();
    }

    public function test_throws_when_http_request_fails(): void
    {
        $auth = $this->makeAuthenticator([
            new ConnectException('Connection refused', new Request('GET', '/grant/')),
        ]);

        $this->expectException(MoloniAuthException::class);
        $this->expectExceptionMessageMatches('/Authentication request failed/');

        $auth->getAccessToken();
    }

    public function test_throws_for_non_password_grant_type_without_code(): void
    {
        $auth = new MoloniAuthenticator(array_merge($this->config, ['grant_type' => 'authorization_code']));

        $this->expectException(MoloniAuthException::class);
        $this->expectExceptionMessageMatches('/requires an authorization code/');

        $auth->getAccessToken();
    }

    // ─── getAuthorizationUrl ──────────────────────────────────────────────────

    public function test_builds_correct_authorization_url(): void
    {
        $auth = new MoloniAuthenticator($this->config);

        $url = $auth->getAuthorizationUrl();

        $this->assertStringContainsString('https://www.moloni.pt/ac/root/oauth/', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=test_client_id', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
    }

    // ─── handleAuthorizationCallback ─────────────────────────────────────────

    public function test_exchanges_authorization_code_for_tokens(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'code_access',
                'expires_in' => 3600,
                'refresh_token' => 'code_refresh',
            ]),
        ]);

        $tokens = $auth->handleAuthorizationCallback('auth_code_abc');

        $this->assertSame('code_access', $tokens['access_token']);

        $req = $this->lastRequest();
        parse_str($req->getUri()->getQuery(), $query);
        $this->assertSame('authorization_code', $query['grant_type']);
        $this->assertSame('auth_code_abc', $query['code']);
    }

    public function test_authorization_callback_caches_tokens(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'code_access',
                'expires_in' => 3600,
                'refresh_token' => 'code_refresh',
            ]),
        ]);

        $auth->handleAuthorizationCallback('auth_code_abc');

        $this->assertSame('code_access', Cache::get('moloni_token_access_token'));
        $this->assertSame('code_refresh', Cache::get('moloni_token_refresh_token'));
    }

    public function test_authorization_callback_throws_when_code_rejected(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse(['error' => 'invalid_grant', 'error_description' => 'Code expired']),
        ]);

        $this->expectException(MoloniAuthException::class);

        $auth->handleAuthorizationCallback('bad_code');
    }

    // ─── forgetTokens ─────────────────────────────────────────────────────────

    public function test_forget_tokens_removes_both_cached_tokens(): void
    {
        Cache::put('moloni_token_access_token', 'at', now()->addHour());
        Cache::put('moloni_token_refresh_token', 'rt', now()->addDays(13));

        $auth = new MoloniAuthenticator($this->config);
        $auth->forgetTokens();

        $this->assertNull(Cache::get('moloni_token_access_token'));
        $this->assertNull(Cache::get('moloni_token_refresh_token'));
    }

    public function test_forget_tokens_is_idempotent_when_cache_is_empty(): void
    {
        $auth = new MoloniAuthenticator($this->config);

        // Should not throw
        $auth->forgetTokens();

        $this->assertNull(Cache::get('moloni_token_access_token'));
    }

    // ─── Token expiry handling ────────────────────────────────────────────────

    public function test_access_token_not_cached_when_response_missing_expires_in(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'at_no_expiry',
                'refresh_token' => 'rt',
                // expires_in deliberately omitted — should default to 3600
            ]),
        ]);

        $token = $auth->getAccessToken();

        $this->assertSame('at_no_expiry', $token);
        $this->assertNotNull(Cache::get('moloni_token_access_token'));
    }

    public function test_refresh_token_not_cached_when_absent_in_response(): void
    {
        $auth = $this->makeAuthenticator([
            $this->jsonResponse([
                'access_token' => 'at',
                'expires_in' => 3600,
                // refresh_token deliberately omitted
            ]),
        ]);

        $auth->getAccessToken();

        $this->assertNull(Cache::get('moloni_token_refresh_token'));
    }
}
