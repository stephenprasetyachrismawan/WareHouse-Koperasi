<?php

declare(strict_types=1);

namespace App\Domain\Inventory\ValueObjects;

use App\Enums\MovementType;
use InvalidArgumentException;

/**
 * Immutable value object for recording a stock movement.
 *
 * This DTO captures the validated intent of a stock movement before
 * the action executes the transaction. Actor and warehouse are not
 * trusted from client input — they must be set server-side.
 */
final readonly class StockMovementInput
{
    public function __construct(
        public int $warehouseId,
        public int $itemId,
        public MovementType $movementType,
        public int $quantity,
        public int $performedBy,
        public string $idempotencyKey,
        public ?string $reason = null,
        public ?string $sourceType = null,
        public ?int $sourceId = null,
        public ?int $reversalOfId = null,
        public ?array $metadata = null,
    ) {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Stock movement quantity must not be zero.');
        }

        if ($quantity < 0) {
            throw new InvalidArgumentException('Stock movement quantity must be positive. Direction is inferred from movement type.');
        }
    }
}
