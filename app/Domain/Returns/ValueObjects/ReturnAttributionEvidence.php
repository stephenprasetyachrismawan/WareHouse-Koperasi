<?php

namespace App\Domain\Returns\ValueObjects;

readonly class ReturnAttributionEvidence
{
    public function __construct(
        public bool $qcEvidenceExists,
        public ?int $qualityInspectionId,
        public string $basis,
    ) {}
}
