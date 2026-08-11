<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Enums\MovementType;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Livewire\Pickup\Fulfilment;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReplacementPickupFulfilmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A replacement Pickup is a real PickupRequest row and would otherwise
     * be findable through the ordinary Staff Fulfilment search screen. This
     * proves that screen now routes it to CompleteReplacementPickupAction
     * (correct MovementType, completes the linked Return) instead of the
     * ordinary FulfillPickupAction (wrong MovementType, leaves the Return
     * stuck at READY_FOR_REPICKUP forever).
     */
    public function test_fulfilling_a_replacement_pickup_from_the_ordinary_staff_screen_uses_the_correct_action(): void
    {
        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        StockBalance::factory()->create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 10]);

        $koperasi = User::factory()->create();
        $membership = WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $warehouse->id,
            'cooperative_membership_id' => $membership->id,
            'submitted_by' => $koperasi->id,
        ]);
        ReturnRequestItem::factory()->create([
            'return_request_id' => $returnRequest->id,
            'item_id' => $item->id,
            'return_quantity' => 3,
        ]);

        $prepared = app(PrepareReplacementPickupAction::class)->execute($returnRequest);
        $pickup = PickupRequest::find($prepared->replacement_pickup_request_id);

        Livewire::actingAs($staff)
            ->test(Fulfilment::class)
            ->set('searchRequestNumber', $pickup->request_number)
            ->call('search')
            ->call('fulfill')
            ->assertHasNoErrors();

        $this->assertEquals(ReturnStatus::Completed, $returnRequest->fresh()->status);

        $balance = StockBalance::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->first();
        $this->assertEquals(7, $balance->quantity);

        $this->assertSame(1, StockTransaction::count());
        $this->assertEquals(MovementType::ReplacementIssue, StockTransaction::first()->movement_type);
    }
}
