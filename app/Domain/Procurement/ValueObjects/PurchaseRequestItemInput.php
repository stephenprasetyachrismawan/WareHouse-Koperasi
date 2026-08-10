<?php

namespace App\Domain\Procurement\ValueObjects;

use InvalidArgumentException;

readonly class PurchaseRequestItemInput
{
    public function __construct(
        public int $itemId,
        public int $quantity,
        public ?string $notes = null,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }
    }
}
