<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\DetermineReturnFaultAction;
use App\Enums\ReturnFaultAttribution;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\QualityInspection;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnFaultAttributionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private ReturnRequestItem $returnRequestItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $this->warehouse->id]);
        $this->returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $this->item->id,
        ]);
    }

    public function test_fr32_qc_evidence_attributes_fault_to_warehouse(): void
    {
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
        ]);
        QualityInspection::factory()->passed()->create([
            'warehouse_id' => $this->warehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);

        $result = app(DetermineReturnFaultAction::class)->execute($this->returnRequestItem);

        $this->assertEquals(ReturnFaultAttribution::Warehouse, $result->attribution);
        $this->assertEquals(DetermineReturnFaultAction::RULE_VERSION, $result->ruleVersion);
        $this->assertTrue($result->evidence->qcEvidenceExists);
        $this->assertNotNull($result->evidence->qualityInspectionId);
    }

    public function test_fr32_missing_qc_evidence_attributes_fault_to_supplier(): void
    {
        $result = app(DetermineReturnFaultAction::class)->execute($this->returnRequestItem);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->attribution);
        $this->assertEquals(DetermineReturnFaultAction::RULE_VERSION, $result->ruleVersion);
        $this->assertFalse($result->evidence->qcEvidenceExists);
        $this->assertNull($result->evidence->qualityInspectionId);
    }

    public function test_qc_for_a_different_item_does_not_count(): void
    {
        $unrelatedItem = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $unrelatedItem->id,
        ]);
        QualityInspection::factory()->passed()->create([
            'warehouse_id' => $this->warehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);

        $result = app(DetermineReturnFaultAction::class)->execute($this->returnRequestItem);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->attribution);
    }

    public function test_qc_from_another_warehouse_does_not_count(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $otherWarehouse->id,
            'item_id' => $this->item->id,
        ]);
        QualityInspection::factory()->passed()->create([
            'warehouse_id' => $otherWarehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);

        $result = app(DetermineReturnFaultAction::class)->execute($this->returnRequestItem);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->attribution);
    }

    public function test_failed_qc_does_not_count_as_evidence(): void
    {
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
        ]);
        QualityInspection::factory()->failed()->create([
            'warehouse_id' => $this->warehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);

        $result = app(DetermineReturnFaultAction::class)->execute($this->returnRequestItem);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->attribution);
    }
}
