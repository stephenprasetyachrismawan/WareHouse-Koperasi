<?php

namespace App\Domain\Procurement\ValueObjects;

readonly class CreatePurchaseOrderInput
{
    /**
     * @param  array<int, array{item_id: int, unit_cost: float, notes?: ?string}>  $items
     */
    public function __construct(
        public int $warehouseId,
        public int $groupId,
        public int $supplierId,
        public ?string $notes,
        public array $items,
    ) {}
}
