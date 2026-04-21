<?php

namespace Tomahock\Moloni\Resources;

use Tomahock\Moloni\Http\MoloniClient;

abstract class AbstractResource
{
    protected MoloniClient $client;
    protected string $endpoint = '';

    public function __construct(MoloniClient $client)
    {
        $this->client = $client;
    }

    protected function companyId(): int
    {
        $id = $this->client->getCompanyId();

        if ($id === null) {
            throw new \RuntimeException('Company ID not set. Call Moloni::company(id) first.');
        }

        return $id;
    }

    protected function buildEndpoint(string $action): string
    {
        return "/{$this->endpoint}/{$action}/";
    }

    public function getAll(array $params = []): array
    {
        return $this->client->post(
            $this->buildEndpoint('getAll'),
            array_merge(['company_id' => $this->companyId()], $params)
        );
    }

    public function getOne(array $params): array
    {
        return $this->client->post(
            $this->buildEndpoint('getOne'),
            array_merge(['company_id' => $this->companyId()], $params)
        );
    }

    public function insert(array $data): array
    {
        return $this->client->post(
            $this->buildEndpoint('insert'),
            array_merge(['company_id' => $this->companyId()], $data)
        );
    }

    public function update(array $data): array
    {
        return $this->client->post(
            $this->buildEndpoint('update'),
            array_merge(['company_id' => $this->companyId()], $data)
        );
    }

    public function delete(array $params): array
    {
        return $this->client->post(
            $this->buildEndpoint('delete'),
            array_merge(['company_id' => $this->companyId()], $params)
        );
    }

    public function countAll(array $params = []): array
    {
        return $this->client->post(
            $this->buildEndpoint('countAll'),
            array_merge(['company_id' => $this->companyId()], $params)
        );
    }
}
