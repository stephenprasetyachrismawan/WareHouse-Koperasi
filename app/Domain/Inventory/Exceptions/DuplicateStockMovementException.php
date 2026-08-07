<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

/**
 * Thrown when a stock movement is rejected because a transaction
 * with the same idempotency key already exists for this warehouse.
 */
final class DuplicateStockMovementException extends RuntimeException
{
    public function __construct(string $idempotencyKey, int $warehouseId)
    {
        parent::__construct(
            "Duplicate stock movement: idempotency key '{$idempotencyKey}' already exists for warehouse {$warehouseId}."
        );
    }
}
