<?php

namespace App\Domain\Procurement\ValueObjects;

use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use InvalidArgumentException;

readonly class CompleteQualityInspectionInput
{
    public function __construct(
        public int $warehouseId,
        public QualityInspectionResult $result,
        public QualityInspectionCondition $condition,
        public ?string $notes = null,
        public ?string $evidencePath = null,
        public ?string $evidenceMime = null,
    ) {
        if ($result === QualityInspectionResult::Fail && trim((string) $notes) === '') {
            throw new InvalidArgumentException('Notes are required when quality inspection result is FAIL.');
        }
    }
}
