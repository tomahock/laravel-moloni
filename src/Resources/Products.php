<?php

namespace Tomahock\Moloni\Resources;

class Products extends AbstractResource
{
    protected string $endpoint = 'products';

    public function getByReference(string $reference): array
    {
        return $this->getAll(['reference' => $reference]);
    }

    public function search(string $search): array
    {
        return $this->getAll(['search' => $search]);
    }

    public function getByCategory(int $categoryId): array
    {
        return $this->getAll(['category_id' => $categoryId]);
    }

    public function updateStock(int $productId, float $qty, string $movement = 'add', ?int $warehouseId = null): array
    {
        $data = [
            'company_id' => $this->companyId(),
            'product_id' => $productId,
            'qty' => $qty,
            'movement' => $movement,
        ];

        if ($warehouseId !== null) {
            $data['warehouse_id'] = $warehouseId;
        }

        return $this->client->post($this->buildEndpoint('updateStock'), $data);
    }
}
