<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\DetermineReturnFaultAction;
use App\Enums\ReturnFaultAttribution;
use App\Enums\WarehouseRole;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\QualityInspection;
use App\Models\ReturnEvidence;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnApprovalTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_from_another_warehouse_cannot_view_waiting_approval_return(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $headB = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $headB->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouseA->id]);

        $this->assertFalse(Gate::forUser($headB)->allows('view', $returnRequest));
        $this->assertFalse(Gate::forUser($headB)->allows('approve', $returnRequest));
    }

    public function test_head_from_another_warehouse_cannot_download_evidence(): void
    {
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $headB = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $headB->id,
            'warehouse_id' => $warehouseB->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouseA->id]);
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $warehouseA->id,
        ]);

        $this->actingAs($headB)
            ->get(route('returns.evidence', $evidence->uuid))
            ->assertForbidden();
    }

    public function test_head_of_own_warehouse_can_view_evidence(): void
    {
        $warehouse = Warehouse::factory()->create();
        $head = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouse->id]);
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $warehouse->id,
            'path' => 'return-evidence/does-not-exist.jpg',
        ]);

        // Policy passes; 404 only because the fixture file was never stored.
        $this->actingAs($head)
            ->get(route('returns.evidence', $evidence->uuid))
            ->assertNotFound();
    }

    public function test_authorized_evidence_is_read_from_the_private_disk(): void
    {
        Storage::fake('private');

        $warehouse = Warehouse::factory()->create();
        $head = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouse->id]);
        $path = "return-evidence/{$warehouse->id}/evidence.jpg";
        Storage::disk('private')->put($path, 'synthetic evidence');
        $evidence = ReturnEvidence::factory()->create([
            'return_request_id' => $returnRequest->id,
            'warehouse_id' => $warehouse->id,
            'path' => $path,
            'mime' => 'image/jpeg',
        ]);

        $this->actingAs($head)
            ->get(route('returns.evidence', $evidence->uuid))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_historical_attribution_is_not_altered_by_a_later_qc_record(): void
    {
        $warehouse = Warehouse::factory()->create();
        $head = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouse->id]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
        ]);

        // No QC evidence yet -> SUPPLIER.
        $approved = app(ApproveReturnAction::class)->execute($head, $returnRequest);
        $this->assertEquals(ReturnFaultAttribution::Supplier, $approved->fault_attribution);

        // A QC record now appears for this item/warehouse, after the decision.
        $goodsReceiptItem = GoodsReceiptItem::factory()->create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
        ]);
        QualityInspection::factory()->passed()->create([
            'warehouse_id' => $warehouse->id,
            'goods_receipt_item_id' => $goodsReceiptItem->id,
        ]);

        // The already-decided Return's stored attribution must not change.
        $this->assertEquals(ReturnFaultAttribution::Supplier, $approved->fresh()->fault_attribution);
        $this->assertEquals(DetermineReturnFaultAction::RULE_VERSION, $approved->fresh()->fault_rule_version);
    }

    public function test_client_forged_attribution_is_overwritten_by_the_computed_result(): void
    {
        $warehouse = Warehouse::factory()->create();
        $head = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $head->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        $returnRequest = ReturnRequest::factory()->waitingApproval()->create([
            'warehouse_id' => $warehouse->id,
            'fault_attribution' => ReturnFaultAttribution::Warehouse,
            'fault_rule_version' => 'FORGED_V0',
        ]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
        ]);

        // No real QC evidence exists, so the correct outcome is SUPPLIER,
        // regardless of the attribution the row was seeded with beforehand.
        $result = app(ApproveReturnAction::class)->execute($head, $returnRequest);

        $this->assertEquals(ReturnFaultAttribution::Supplier, $result->fault_attribution);
        $this->assertEquals(DetermineReturnFaultAction::RULE_VERSION, $result->fault_rule_version);
    }

    public function test_the_staff_who_verified_the_return_does_not_automatically_gain_approval_authority(): void
    {
        $warehouse = Warehouse::factory()->create();
        $staffWhoVerified = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staffWhoVerified->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create([
            'warehouse_id' => $warehouse->id,
            'verified_by' => $staffWhoVerified->id,
        ]);

        $this->assertFalse(Gate::forUser($staffWhoVerified)->allows('approve', $returnRequest));

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($staffWhoVerified, $returnRequest);
    }

    public function test_koperasi_cannot_approve_even_via_route_authorization(): void
    {
        $warehouse = Warehouse::factory()->create();
        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $warehouse->id]);

        $this->assertFalse(Gate::forUser($koperasi)->allows('approve', $returnRequest));

        $this->expectException(AuthorizationException::class);
        app(ApproveReturnAction::class)->execute($koperasi, $returnRequest);
    }
}
