<?php

namespace App\Actions\Returns;

use App\Domain\Returns\Queries\ReturnAttributionEvidenceQuery;
use App\Domain\Returns\ValueObjects\ReturnFaultAttributionResult;
use App\Enums\ReturnFaultAttribution;
use App\Models\ReturnRequestItem;

/**
 * FR-32, exactly as written — do not reverse:
 *   traceable QC evidence EXISTS      -> WAREHOUSE
 *   traceable QC evidence DOES NOT EXIST -> SUPPLIER
 *
 * Attribution is a system-computed rule, never a manual choice by the
 * approver. The rule version is stable and stored alongside the result so
 * historical returns remain explainable even if a future rule change lands.
 */
class DetermineReturnFaultAction
{
    public const RULE_VERSION = 'FR32_V1';

    public function __construct(
        private readonly ReturnAttributionEvidenceQuery $evidenceQuery,
    ) {}

    public function execute(ReturnRequestItem $returnRequestItem): ReturnFaultAttributionResult
    {
        $evidence = $this->evidenceQuery->execute($returnRequestItem);

        $attribution = $evidence->qcEvidenceExists
            ? ReturnFaultAttribution::Warehouse
            : ReturnFaultAttribution::Supplier;

        return new ReturnFaultAttributionResult(
            attribution: $attribution,
            ruleVersion: self::RULE_VERSION,
            evidence: $evidence,
        );
    }
}
