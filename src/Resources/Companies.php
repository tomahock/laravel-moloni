<?php

namespace Tomahock\Moloni\Resources;

class Companies extends AbstractResource
{
    protected string $endpoint = 'companies';

    public function getAll(array $params = []): array
    {
        return $this->client->post($this->buildEndpoint('getAll'), $params);
    }

    public function getOne(array $params = []): array
    {
        $companyId = $params['company_id'] ?? $this->companyId();
        return $this->client->post($this->buildEndpoint('getOne'), ['company_id' => $companyId]);
    }

    public function update(array $data): array
    {
        return $this->client->post(
            $this->buildEndpoint('update'),
            array_merge(['company_id' => $this->companyId()], $data)
        );
    }
}
