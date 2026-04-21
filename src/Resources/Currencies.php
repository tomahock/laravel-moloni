<?php

namespace Tomahock\Moloni\Resources;

class Currencies extends AbstractResource
{
    protected string $endpoint = 'currencies';

    public function getAll(array $params = []): array
    {
        return $this->client->post($this->buildEndpoint('getAll'), $params);
    }
}
