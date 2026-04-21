<?php

namespace Tomahock\Moloni\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Tomahock\Moloni\Exceptions\MoloniAuthException;

class MoloniAuthenticator
{
    private const GRANT_ENDPOINT = '/grant/';
    private const AUTH_ENDPOINT = 'https://www.moloni.pt/ac/root/oauth/';
    private const CACHE_KEY_ACCESS = '_access_token';
    private const CACHE_KEY_REFRESH = '_refresh_token';

    private Client $httpClient;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 30,
        ]);
    }

    public function getAccessToken(): string
    {
        $cacheKey = $this->config['token_cache_prefix'] . self::CACHE_KEY_ACCESS;
        $cacheDriver = Cache::driver($this->config['token_cache_driver'] ?? null);

        if ($token = $cacheDriver->get($cacheKey)) {
            return $token;
        }

        return $this->fetchNewToken();
    }

    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
        ]);

        return self::AUTH_ENDPOINT . '?' . $params;
    }

    public function handleAuthorizationCallback(string $code): array
    {
        $tokens = $this->requestToken([
            'grant_type' => 'authorization_code',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'code' => $code,
        ]);

        $this->cacheTokens($tokens);

        return $tokens;
    }

    public function forgetTokens(): void
    {
        $prefix = $this->config['token_cache_prefix'];
        $driver = Cache::driver($this->config['token_cache_driver'] ?? null);

        $driver->forget($prefix . self::CACHE_KEY_ACCESS);
        $driver->forget($prefix . self::CACHE_KEY_REFRESH);
    }

    private function fetchNewToken(): string
    {
        $refreshToken = $this->getCachedRefreshToken();

        $tokens = $refreshToken
            ? $this->refreshToken($refreshToken)
            : $this->fetchTokenWithGrantType();

        $this->cacheTokens($tokens);

        return $tokens['access_token'];
    }

    private function fetchTokenWithGrantType(): array
    {
        $grantType = $this->config['grant_type'] ?? 'password';

        $params = match ($grantType) {
            'password' => [
                'grant_type' => 'password',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ],
            default => throw new MoloniAuthException(
                "Grant type '{$grantType}' requires an authorization code. Use getAuthorizationUrl() and handleAuthorizationCallback()."
            ),
        };

        return $this->requestToken($params);
    }

    private function refreshToken(string $refreshToken): array
    {
        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $refreshToken,
        ]);
    }

    private function requestToken(array $params): array
    {
        try {
            $response = $this->httpClient->get(
                rtrim($this->config['base_url'], '/') . self::GRANT_ENDPOINT,
                ['query' => $params]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['access_token'])) {
                throw new MoloniAuthException(
                    'Failed to obtain access token: ' . ($data['error_description'] ?? 'Unknown error'),
                    $response->getStatusCode()
                );
            }

            return $data;
        } catch (GuzzleException $e) {
            throw new MoloniAuthException('Authentication request failed: ' . $e->getMessage(), $e->getCode(), [], $e);
        }
    }

    private function getCachedRefreshToken(): ?string
    {
        $cacheKey = $this->config['token_cache_prefix'] . self::CACHE_KEY_REFRESH;
        return Cache::driver($this->config['token_cache_driver'] ?? null)->get($cacheKey);
    }

    private function cacheTokens(array $tokens): void
    {
        $prefix = $this->config['token_cache_prefix'];
        $driver = Cache::driver($this->config['token_cache_driver'] ?? null);

        // Access token expires in 1 hour, cache for 55 minutes to be safe
        $driver->put($prefix . self::CACHE_KEY_ACCESS, $tokens['access_token'], now()->addSeconds(($tokens['expires_in'] ?? 3600) - 300));

        if (!empty($tokens['refresh_token'])) {
            // Refresh token is valid for 14 days
            $driver->put($prefix . self::CACHE_KEY_REFRESH, $tokens['refresh_token'], now()->addDays(13));
        }
    }
}
