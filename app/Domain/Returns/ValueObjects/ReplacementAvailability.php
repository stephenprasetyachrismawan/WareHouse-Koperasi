<?php

namespace App\Domain\Returns\ValueObjects;

readonly class ReplacementAvailability
{
    public function __construct(
        public bool $isAvailable,
        public int $requiredQuantity,
        public int $availableQuantity,
        public int $shortfallQuantity,
    ) {}
}
