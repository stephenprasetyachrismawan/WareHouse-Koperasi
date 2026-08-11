<?php

namespace App\Domain\Returns\ValueObjects;

use InvalidArgumentException;

readonly class VerifyReturnInput
{
    public function __construct(
        public int $warehouseId,
        public string $scannedBarcode,
        public int $verifiedQuantity,
        public string $evidencePath,
        public string $evidenceMime,
        public ?string $notes,
        public int $expectedVersion,
    ) {
        if (trim($this->scannedBarcode) === '') {
            throw new InvalidArgumentException('A barcode (scanned or manually entered) is required to verify a return item.');
        }

        if ($this->verifiedQuantity <= 0) {
            throw new InvalidArgumentException('Verified quantity must be greater than zero.');
        }
    }
}
