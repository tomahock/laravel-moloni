<?php

namespace Tomahock\Moloni\Resources;

class Countries extends AbstractResource
{
    protected string $endpoint = 'countries';

    public function getAll(array $params = []): array
    {
        // Countries is a global endpoint, no company_id needed
        return $this->client->post($this->buildEndpoint('getAll'), $params);
    }
}
