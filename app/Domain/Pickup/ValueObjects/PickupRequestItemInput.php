<?php

namespace App\Domain\Pickup\ValueObjects;

readonly class PickupRequestItemInput
{
    public function __construct(
        public int $itemId,
        public int $quantity,
        public ?string $notes = null
    ) {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0.');
        }
    }
}
