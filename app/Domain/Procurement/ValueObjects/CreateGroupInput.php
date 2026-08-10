<?php

namespace App\Domain\Procurement\ValueObjects;

readonly class CreateGroupInput
{
    /**
     * @param  AllocationInput[]  $allocations
     */
    public function __construct(
        public int $warehouseId,
        public ?string $notes,
        public array $allocations,
    ) {}
}
