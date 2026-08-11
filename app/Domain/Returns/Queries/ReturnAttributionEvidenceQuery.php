<?php

namespace App\Domain\Returns\Queries;

use App\Domain\Returns\ValueObjects\ReturnAttributionEvidence;
use App\Enums\QualityInspectionResult;
use App\Models\QualityInspection;
use App\Models\ReturnRequestItem;

/**
 * FR-32 evidence resolution. The current architecture has no lot/serial
 * tracking, so a specific returned physical unit can never be traced to the
 * exact receiving batch it came from. The only defensible, persisted signal
 * available is item+warehouse scoped: has this Item ever passed a receiving
 * Quality Inspection in this exact Warehouse (via the real
 * GoodsReceiptItem -> QualityInspection chain Phase 4.3 built)? This is an
 * existence check, not "pick the latest QC record" — it never associates a
 * specific unrelated inspection as *the* source of the returned unit, and it
 * never crosses tenant/warehouse boundaries.
 */
class ReturnAttributionEvidenceQuery
{
    public function execute(ReturnRequestItem $returnRequestItem): ReturnAttributionEvidence
    {
        $warehouseId = $returnRequestItem->returnRequest->warehouse_id;
        $itemId = $returnRequestItem->item_id;

        $inspection = QualityInspection::query()
            ->where('warehouse_id', $warehouseId)
            ->where('result', QualityInspectionResult::Pass->value)
            ->whereHas('goodsReceiptItem', function ($query) use ($itemId) {
                $query->where('item_id', $itemId);
            })
            ->first();

        if ($inspection) {
            return new ReturnAttributionEvidence(
                qcEvidenceExists: true,
                qualityInspectionId: $inspection->id,
                basis: 'Traceable QC evidence found',
            );
        }

        return new ReturnAttributionEvidence(
            qcEvidenceExists: false,
            qualityInspectionId: null,
            basis: 'No traceable QC evidence',
        );
    }
}
