<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\CreateReturnAction;
use App\Domain\Returns\ValueObjects\CreateReturnInput;
use App\Enums\ReturnEvidencePurpose;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateReturnActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $koperasi;

    private WarehouseMembership $membership;

    private Item $item;

    private PickupRequest $pickupRequest;

    private PickupRequestItem $pickupRequestItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->koperasi = User::factory()->create();
        $this->membership = WarehouseMembership::factory()->create([
            'user_id' => $this->koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);

        $this->pickupRequest = PickupRequest::factory()->completed()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->koperasi->id,
        ]);
        $this->pickupRequestItem = PickupRequestItem::factory()->create([
            'pickup_request_id' => $this->pickupRequest->id,
            'item_id' => $this->item->id,
            'requested_quantity' => 10,
            'fulfilled_quantity' => 10,
        ]);

        StockBalance::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);
    }

    private function validInput(array $overrides = []): CreateReturnInput
    {
        return new CreateReturnInput(
            warehouseId: $overrides['warehouseId'] ?? $this->warehouse->id,
            pickupRequestId: $overrides['pickupRequestId'] ?? $this->pickupRequest->id,
            pickupRequestItemId: $overrides['pickupRequestItemId'] ?? $this->pickupRequestItem->id,
            returnQuantity: $overrides['returnQuantity'] ?? 3,
            reasonCode: $overrides['reasonCode'] ?? ReturnReasonCode::Damaged,
            reasonNotes: $overrides['reasonNotes'] ?? null,
            evidencePath: $overrides['evidencePath'] ?? 'return-evidence/test.jpg',
            evidenceMime: $overrides['evidenceMime'] ?? 'image/jpeg',
        );
    }

    public function test_koperasi_submits_return_successfully(): void
    {
        $action = app(CreateReturnAction::class);

        $returnRequest = $action->execute($this->koperasi, $this->validInput());

        $this->assertInstanceOf(ReturnRequest::class, $returnRequest);
        $this->assertEquals(ReturnStatus::Submitted, $returnRequest->status);
        $this->assertEquals($this->warehouse->id, $returnRequest->warehouse_id);
        $this->assertEquals($this->membership->id, $returnRequest->cooperative_membership_id);
        $this->assertEquals($this->koperasi->id, $returnRequest->submitted_by);
        $this->assertStringStartsWith('RET-', $returnRequest->return_number);
        $this->assertNotNull($returnRequest->submitted_at);
        $this->assertEquals(1, $returnRequest->version);

        $this->assertCount(1, $returnRequest->items);
        $this->assertEquals(3, $returnRequest->items->first()->return_quantity);
        $this->assertEquals($this->item->id, $returnRequest->items->first()->item_id);

        $this->assertCount(1, $returnRequest->evidence);
        $this->assertEquals(ReturnEvidencePurpose::ReturnSubmission, $returnRequest->evidence->first()->purpose);
        $this->assertEquals($this->koperasi->id, $returnRequest->evidence->first()->uploaded_by);
    }

    public function test_it_does_not_mutate_stock_balance_or_create_stock_transaction(): void
    {
        $action = app(CreateReturnAction::class);

        $action->execute($this->koperasi, $this->validInput());

        $balance = StockBalance::where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)
            ->first();

        $this->assertEquals(50, $balance->quantity);
        $this->assertSame(0, StockTransaction::count());
    }

    public function test_it_fails_if_pickup_request_is_not_completed(): void
    {
        $incompletePickup = PickupRequest::factory()->approved()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->koperasi->id,
        ]);
        $incompleteItem = PickupRequestItem::factory()->create([
            'pickup_request_id' => $incompletePickup->id,
            'item_id' => $this->item->id,
            'requested_quantity' => 5,
            'fulfilled_quantity' => 0,
        ]);

        $action = app(CreateReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->koperasi, $this->validInput([
            'pickupRequestId' => $incompletePickup->id,
            'pickupRequestItemId' => $incompleteItem->id,
        ]));
    }

    public function test_it_fails_if_pickup_belongs_to_another_koperasi_user(): void
    {
        $otherKoperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherKoperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $action = app(CreateReturnAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($otherKoperasi, $this->validInput());
    }

    public function test_it_fails_if_warehouse_does_not_match_actor_membership(): void
    {
        $otherWarehouse = Warehouse::factory()->create();

        $action = app(CreateReturnAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($this->koperasi, $this->validInput(['warehouseId' => $otherWarehouse->id]));
    }

    public function test_it_fails_if_return_quantity_exceeds_eligible_quantity(): void
    {
        $action = app(CreateReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->koperasi, $this->validInput(['returnQuantity' => 11]));
    }

    public function test_it_enforces_cumulative_eligibility_across_multiple_returns(): void
    {
        $action = app(CreateReturnAction::class);

        $action->execute($this->koperasi, $this->validInput(['returnQuantity' => 7]));

        // Only 3 remain eligible (10 fulfilled - 7 already returned)
        $this->expectException(\RuntimeException::class);
        $action->execute($this->koperasi, $this->validInput(['returnQuantity' => 4]));
    }

    public function test_it_allows_a_second_return_within_remaining_eligible_quantity(): void
    {
        $action = app(CreateReturnAction::class);

        $action->execute($this->koperasi, $this->validInput(['returnQuantity' => 7]));
        $second = $action->execute($this->koperasi, $this->validInput(['returnQuantity' => 3]));

        $this->assertEquals(3, $second->items->first()->return_quantity);
        $this->assertEquals(2, ReturnRequest::count());
    }

    public function test_non_koperasi_role_cannot_create_a_return(): void
    {
        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $action = app(CreateReturnAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($staff, $this->validInput());
    }

    public function test_reason_other_requires_notes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CreateReturnInput(
            warehouseId: $this->warehouse->id,
            pickupRequestId: $this->pickupRequest->id,
            pickupRequestItemId: $this->pickupRequestItem->id,
            returnQuantity: 1,
            reasonCode: ReturnReasonCode::Other,
            reasonNotes: '   ',
            evidencePath: 'return-evidence/test.jpg',
            evidenceMime: 'image/jpeg',
        );
    }

    public function test_return_quantity_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CreateReturnInput(
            warehouseId: $this->warehouse->id,
            pickupRequestId: $this->pickupRequest->id,
            pickupRequestItemId: $this->pickupRequestItem->id,
            returnQuantity: 0,
            reasonCode: ReturnReasonCode::Damaged,
            reasonNotes: null,
            evidencePath: 'return-evidence/test.jpg',
            evidenceMime: 'image/jpeg',
        );
    }

    public function test_it_fails_if_pickup_request_item_does_not_belong_to_pickup_request(): void
    {
        $otherPickup = PickupRequest::factory()->completed()->create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->koperasi->id,
        ]);
        $otherItem = PickupRequestItem::factory()->create([
            'pickup_request_id' => $otherPickup->id,
            'item_id' => $this->item->id,
            'requested_quantity' => 5,
            'fulfilled_quantity' => 5,
        ]);

        $action = app(CreateReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->koperasi, $this->validInput([
            'pickupRequestId' => $this->pickupRequest->id,
            'pickupRequestItemId' => $otherItem->id,
        ]));
    }
}
