<?php

namespace Tomahock\Moloni\Resources;

class Customers extends AbstractResource
{
    protected string $endpoint = 'customers';

    public function getByVat(string $vat): array
    {
        return $this->getAll(['vat' => $vat]);
    }

    public function getByEmail(string $email): array
    {
        return $this->getAll(['email' => $email]);
    }

    public function search(string $search): array
    {
        return $this->getAll(['search' => $search]);
    }

    public function getNextNumber(): array
    {
        return $this->client->post(
            $this->buildEndpoint('getNextNumber'),
            ['company_id' => $this->companyId()]
        );
    }
}
