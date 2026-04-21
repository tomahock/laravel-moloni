<?php

namespace Tomahock\Moloni\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tomahock\Moloni\Exceptions\MoloniAuthException;
use Tomahock\Moloni\Exceptions\MoloniException;

class MoloniClient
{
    private Client $httpClient;
    private MoloniAuthenticator $authenticator;
    private array $config;
    private ?int $companyId = null;

    public function __construct(MoloniAuthenticator $authenticator, array $config)
    {
        $this->authenticator = $authenticator;
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => rtrim($config['base_url'], '/') . '/',
            'timeout' => $config['timeout'] ?? 30,
        ]);
    }

    public function setCompanyId(int $companyId): static
    {
        $this->companyId = $companyId;
        return $this;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    public function post(string $endpoint, array $data = [], array $params = []): array
    {
        return $this->request('POST', $endpoint, $params, $data);
    }

    private function request(string $method, string $endpoint, array $query = [], array $body = []): array
    {
        $retryTimes = $this->config['retry']['times'] ?? 1;
        $attempt = 0;

        while (true) {
            try {
                $accessToken = $this->authenticator->getAccessToken();

                $options = [
                    'query' => array_merge(['access_token' => $accessToken], $query),
                ];

                if (!empty($body)) {
                    $options['json'] = $body;
                    $options['query']['json'] = 'true';
                }

                $response = $this->httpClient->request($method, ltrim($endpoint, '/'), $options);
                $contents = $response->getBody()->getContents();
                $data = json_decode($contents, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new MoloniException('Invalid JSON response: ' . $contents);
                }

                $this->assertNoErrors($data);

                return $data;
            } catch (MoloniAuthException $e) {
                // Token might be expired, clear and retry once
                if ($attempt === 0) {
                    $this->authenticator->forgetTokens();
                    $attempt++;
                    continue;
                }
                throw $e;
            } catch (GuzzleException $e) {
                if ($attempt < $retryTimes) {
                    $attempt++;
                    usleep(($this->config['retry']['sleep'] ?? 500) * 1000);
                    continue;
                }
                throw new MoloniException('HTTP request failed: ' . $e->getMessage(), $e->getCode(), [], $e);
            }
        }
    }

    private function assertNoErrors(array $data): void
    {
        if (isset($data['valid']) && $data['valid'] === 0) {
            $message = $data['errors'][0]['message'] ?? 'Unknown Moloni API error';
            throw new MoloniException($message, 0, $data['errors'] ?? []);
        }
    }
}
