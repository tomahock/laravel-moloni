<?php

namespace Tomahock\Moloni\Resources;

abstract class AbstractDocument extends AbstractResource
{
    public function getByDate(string $dateStart, string $dateEnd, array $params = []): array
    {
        return $this->getAll(array_merge([
            'date' => $dateStart,
            'expiration_date' => $dateEnd,
        ], $params));
    }

    public function getByCustomer(int $customerId, array $params = []): array
    {
        return $this->getAll(array_merge(['customer_id' => $customerId], $params));
    }

    public function sendEmail(int $documentId, array $emailData): array
    {
        return $this->client->post(
            $this->buildEndpoint('sendEmail'),
            array_merge(['company_id' => $this->companyId(), 'document_id' => $documentId], $emailData)
        );
    }

    public function getPdfLink(int $documentId): array
    {
        return $this->client->post(
            $this->buildEndpoint('getPDFLink'),
            ['company_id' => $this->companyId(), 'document_id' => $documentId]
        );
    }

    public function getNextNumber(array $params = []): array
    {
        return $this->client->post(
            $this->buildEndpoint('getNextNumber'),
            array_merge(['company_id' => $this->companyId()], $params)
        );
    }
}
