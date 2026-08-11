<?php

namespace Tests\Feature\Procurement;

use App\Enums\QualityInspectionResult;
use App\Models\GoodsReceiptItem;
use App\Models\QualityInspection;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityInspectionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_uuid_on_creation(): void
    {
        $inspection = QualityInspection::factory()->create();

        $this->assertNotNull($inspection->uuid);
    }

    public function test_it_belongs_to_warehouse_receipt_item_and_inspector(): void
    {
        $warehouse = Warehouse::factory()->create();
        $receiptItem = GoodsReceiptItem::factory()->create(['warehouse_id' => $warehouse->id]);
        $inspector = User::factory()->create();

        $inspection = QualityInspection::factory()->create([
            'warehouse_id' => $warehouse->id,
            'goods_receipt_item_id' => $receiptItem->id,
            'inspected_by' => $inspector->id,
        ]);

        $this->assertTrue($inspection->warehouse->is($warehouse));
        $this->assertTrue($inspection->goodsReceiptItem->is($receiptItem));
        $this->assertTrue($inspection->inspector->is($inspector));
    }

    public function test_is_pass_helper(): void
    {
        $passed = QualityInspection::factory()->passed()->create();
        $failed = QualityInspection::factory()->failed()->create();

        $this->assertTrue($passed->isPass());
        $this->assertFalse($failed->isPass());
        $this->assertEquals(QualityInspectionResult::Fail, $failed->result);
    }

    public function test_only_one_final_inspection_allowed_per_receipt_item(): void
    {
        $receiptItem = GoodsReceiptItem::factory()->create();
        QualityInspection::factory()->create(['goods_receipt_item_id' => $receiptItem->id]);

        $this->expectException(QueryException::class);

        QualityInspection::factory()->create(['goods_receipt_item_id' => $receiptItem->id]);
    }
}
