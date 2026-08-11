<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\SubmitReturnForApprovalAction;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmitReturnForApprovalActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
    }

    public function test_it_transitions_admin_verified_to_waiting_approval(): void
    {
        $returnRequest = ReturnRequest::factory()->adminVerified()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'barcode_verified' => true]);

        $action = app(SubmitReturnForApprovalAction::class);
        $result = $action->execute($this->staff, $returnRequest, $returnRequest->version);

        $this->assertEquals(ReturnStatus::WaitingApproval, $result->status);
        $this->assertNotNull($result->waiting_approval_at);
    }

    public function test_it_rejects_direct_submitted_to_waiting_approval(): void
    {
        $returnRequest = ReturnRequest::factory()->submitted()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id]);

        $action = app(SubmitReturnForApprovalAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $returnRequest, $returnRequest->version);
    }

    public function test_it_rejects_when_item_barcode_was_not_verified(): void
    {
        $returnRequest = ReturnRequest::factory()->adminVerified()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'barcode_verified' => false]);

        $action = app(SubmitReturnForApprovalAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $returnRequest, $returnRequest->version);
    }

    public function test_it_prevents_stale_version_double_submission(): void
    {
        $returnRequest = ReturnRequest::factory()->adminVerified()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'barcode_verified' => true]);

        $action = app(SubmitReturnForApprovalAction::class);
        $action->execute($this->staff, $returnRequest, $returnRequest->version);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $returnRequest->fresh(), $returnRequest->version);
    }

    public function test_non_staff_cannot_submit_for_approval(): void
    {
        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->adminVerified()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'barcode_verified' => true]);

        $action = app(SubmitReturnForApprovalAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($koperasi, $returnRequest, $returnRequest->version);
    }
}
