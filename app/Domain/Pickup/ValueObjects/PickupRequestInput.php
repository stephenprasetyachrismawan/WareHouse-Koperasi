<?php

namespace App\Domain\Pickup\ValueObjects;

readonly class PickupRequestInput
{
    /**
     * @param  array<PickupRequestItemInput>  $items
     */
    public function __construct(
        public int $warehouseId,
        public int $userId,
        public ?string $notes = null,
        public array $items = []
    ) {}
}
