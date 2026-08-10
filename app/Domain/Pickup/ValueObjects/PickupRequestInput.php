<?php

namespace App\Domain\Pickup\ValueObjects;

use InvalidArgumentException;

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
    ) {
        foreach ($this->items as $item) {
            if (! $item instanceof PickupRequestItemInput) {
                throw new InvalidArgumentException('Items must be of type PickupRequestItemInput');
            }
        }
    }
}
