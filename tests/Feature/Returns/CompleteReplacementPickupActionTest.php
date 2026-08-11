<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\CompleteReplacementPickupAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Enums\MovementType;
use App\Enums\PickupRequestStatus;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PickupRequest;
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

class CompleteReplacementPickupActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private Item $item;

    private User $staff;

    private ReturnRequest $returnRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);

        $koperasi = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $this->staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 10]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
            'submitted_by' => $koperasi->id,
        ]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $this->item->id,
            'return_quantity' => 4,
        ]);

        $this->returnRequest = app(PrepareReplacementPickupAction::class)->execute($returnRequest);
    }

    public function test_staff_completes_the_replacement_pickup(): void
    {
        $result = app(CompleteReplacementPickupAction::class)->execute($this->staff, $this->returnRequest);

        $this->assertEquals(ReturnStatus::Completed, $result->status);

        $pickup = PickupRequest::find($this->returnRequest->replacement_pickup_request_id);
        $this->assertEquals(PickupRequestStatus::Completed, $pickup->status);
        $this->assertEquals(4, $pickup->items->first()->fulfilled_quantity);

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(6, $balance->quantity);

        $this->assertSame(1, StockTransaction::count());
        $transaction = StockTransaction::first();
        $this->assertEquals(MovementType::ReplacementIssue, $transaction->movement_type);
        $this->assertEquals(-4, $transaction->signed_quantity);
    }

    public function test_retry_does_not_double_issue_stock(): void
    {
        $action = app(CompleteReplacementPickupAction::class);
        $action->execute($this->staff, $this->returnRequest);
        $action->execute($this->staff, $this->returnRequest->fresh());

        $this->assertSame(1, StockTransaction::count());

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(6, $balance->quantity);
    }

    public function test_it_fails_if_return_is_not_ready_for_repickup(): void
    {
        $notReady = ReturnRequest::factory()->replacementPending()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnRequestItem::factory()->create(['return_request_id' => $notReady->id, 'item_id' => $this->item->id]);

        $this->expectException(\RuntimeException::class);
        app(CompleteReplacementPickupAction::class)->execute($this->staff, $notReady);
    }

    public function test_koperasi_cannot_complete_the_replacement_pickup(): void
    {
        $koperasi = User::where('id', $this->returnRequest->submitted_by)->first();

        $this->expectException(AuthorizationException::class);
        app(CompleteReplacementPickupAction::class)->execute($koperasi, $this->returnRequest);
    }

    public function test_staff_from_another_warehouse_cannot_complete(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherStaff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherStaff->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $this->expectException(AuthorizationException::class);
        app(CompleteReplacementPickupAction::class)->execute($otherStaff, $this->returnRequest);
    }
}
