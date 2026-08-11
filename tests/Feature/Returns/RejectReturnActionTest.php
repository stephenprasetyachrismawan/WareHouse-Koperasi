<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\RejectReturnAction;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Approval;
use App\Models\Item;
use App\Models\ReturnDisposal;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectReturnActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

    private Item $item;

    private ReturnRequest $returnRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        StockBalance::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 42,
        ]);

        $this->returnRequest = ReturnRequest::factory()->waitingApproval()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 4,
        ]);
    }

    public function test_kepala_gudang_rejects_with_reason(): void
    {
        $result = app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest, 'Foto tidak menunjukkan kerusakan yang jelas.');

        $this->assertEquals(ReturnStatus::Rejected, $result->status);
        $this->assertEquals($this->kepalaGudang->id, $result->rejected_by);
        $this->assertNotNull($result->rejected_at);
        $this->assertEquals('Foto tidak menunjukkan kerusakan yang jelas.', $result->decision_notes);
        $this->assertTrue($result->status->isTerminal());

        $this->assertEquals(1, Approval::where('approvable_type', ReturnRequest::class)
            ->where('approvable_id', $this->returnRequest->id)
            ->where('status', 'REJECTED')
            ->count());
    }

    public function test_rejection_reason_is_mandatory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest, '   ');
    }

    public function test_rejected_return_creates_no_disposal_and_no_attribution(): void
    {
        $result = app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest, 'Ditolak.');

        $this->assertNull($result->fault_attribution);
        $this->assertSame(0, ReturnDisposal::count());
    }

    public function test_rejection_does_not_mutate_stock(): void
    {
        app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest, 'Ditolak.');

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(42, $balance->quantity);
        $this->assertSame(0, StockTransaction::count());
    }

    public function test_it_fails_if_return_is_not_waiting_approval(): void
    {
        $this->returnRequest->update(['status' => ReturnStatus::AdminVerified]);

        $this->expectException(\RuntimeException::class);
        app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest->fresh(), 'Ditolak.');
    }

    public function test_double_reject_retry_fails_on_second_attempt(): void
    {
        app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest, 'Ditolak.');

        $this->expectException(\RuntimeException::class);
        app(RejectReturnAction::class)->execute($this->kepalaGudang, $this->returnRequest->fresh(), 'Ditolak lagi.');
    }

    public function test_staff_admin_cannot_reject(): void
    {
        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(RejectReturnAction::class)->execute($staff, $this->returnRequest, 'Ditolak.');
    }

    public function test_koperasi_cannot_reject(): void
    {
        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(RejectReturnAction::class)->execute($koperasi, $this->returnRequest, 'Ditolak.');
    }

    public function test_cross_tenant_kepala_gudang_cannot_reject(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherHead->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(RejectReturnAction::class)->execute($otherHead, $this->returnRequest, 'Ditolak.');
    }
}
