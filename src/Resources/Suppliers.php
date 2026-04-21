<?php

namespace Tomahock\Moloni\Resources;

class Suppliers extends AbstractResource
{
    protected string $endpoint = 'suppliers';

    public function getByVat(string $vat): array
    {
        return $this->getAll(['vat' => $vat]);
    }

    public function search(string $search): array
    {
        return $this->getAll(['search' => $search]);
    }
}
