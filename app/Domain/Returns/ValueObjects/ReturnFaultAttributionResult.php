<?php

namespace App\Domain\Returns\ValueObjects;

use App\Enums\ReturnFaultAttribution;

readonly class ReturnFaultAttributionResult
{
    public function __construct(
        public ReturnFaultAttribution $attribution,
        public string $ruleVersion,
        public ReturnAttributionEvidence $evidence,
    ) {}
}
