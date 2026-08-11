<?php

namespace Tests\Feature\Returns;

use App\Actions\Returns\VerifyReturnAction;
use App\Domain\Returns\ValueObjects\VerifyReturnInput;
use App\Enums\ReturnEvidencePurpose;
use App\Enums\ReturnStatus;
use App\Enums\WarehouseRole;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\PickupRequest;
use App\Models\PickupRequestItem;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyReturnActionTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $staff;

    private Item $item;

    private ReturnRequest $returnRequest;

    private ReturnRequestItem $returnRequestItem;

    private string $barcode = 'BC-0001';

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

        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        ItemBarcode::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'barcode' => $this->barcode,
            'is_primary' => true,
        ]);

        $pickupRequest = PickupRequest::factory()->completed()->create(['warehouse_id' => $this->warehouse->id]);
        $pickupRequestItem = PickupRequestItem::factory()->create([
            'pickup_request_id' => $pickupRequest->id,
            'item_id' => $this->item->id,
            'requested_quantity' => 5,
            'fulfilled_quantity' => 5,
        ]);

        $this->returnRequest = ReturnRequest::factory()->submitted()->create([
            'warehouse_id' => $this->warehouse->id,
            'pickup_request_id' => $pickupRequest->id,
        ]);
        $this->returnRequestItem = ReturnRequestItem::factory()->create([
            'return_request_id' => $this->returnRequest->id,
            'pickup_request_item_id' => $pickupRequestItem->id,
            'item_id' => $this->item->id,
            'return_quantity' => 3,
        ]);
    }

    private function validInput(array $overrides = []): VerifyReturnInput
    {
        return new VerifyReturnInput(
            warehouseId: $overrides['warehouseId'] ?? $this->warehouse->id,
            scannedBarcode: $overrides['scannedBarcode'] ?? $this->barcode,
            verifiedQuantity: $overrides['verifiedQuantity'] ?? 3,
            evidencePath: $overrides['evidencePath'] ?? 'return-evidence/staff.jpg',
            evidenceMime: $overrides['evidenceMime'] ?? 'image/jpeg',
            notes: $overrides['notes'] ?? 'Sesuai',
            expectedVersion: $overrides['expectedVersion'] ?? $this->returnRequest->version,
        );
    }

    public function test_staff_verifies_return_successfully(): void
    {
        $action = app(VerifyReturnAction::class);

        $result = $action->execute($this->staff, $this->returnRequest, $this->validInput());

        $this->assertEquals(ReturnStatus::AdminVerified, $result->status);
        $this->assertEquals($this->staff->id, $result->verified_by);
        $this->assertNotNull($result->verified_at);
        $this->assertEquals(2, $result->version);

        $item = $result->items->first();
        $this->assertTrue($item->barcode_verified);
        $this->assertEquals($this->barcode, $item->verified_barcode);

        $evidence = $result->evidence()->where('purpose', ReturnEvidencePurpose::ReturnVerification->value)->first();
        $this->assertNotNull($evidence);
        $this->assertEquals($this->staff->id, $evidence->uploaded_by);
    }

    public function test_it_fails_with_unrecognized_barcode_and_leaves_state_unchanged(): void
    {
        $action = app(VerifyReturnAction::class);

        try {
            $action->execute($this->staff, $this->returnRequest, $this->validInput(['scannedBarcode' => 'UNKNOWN']));
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertEquals(ReturnStatus::Submitted, $this->returnRequest->fresh()->status);
        $this->assertFalse($this->returnRequestItem->fresh()->barcode_verified);
    }

    public function test_it_fails_when_barcode_belongs_to_a_different_item(): void
    {
        $otherItem = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        ItemBarcode::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $otherItem->id,
            'barcode' => 'BC-OTHER',
        ]);

        $action = app(VerifyReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $this->returnRequest, $this->validInput(['scannedBarcode' => 'BC-OTHER']));
    }

    public function test_it_fails_when_verified_quantity_does_not_match_declared_quantity(): void
    {
        $action = app(VerifyReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $this->returnRequest, $this->validInput(['verifiedQuantity' => 99]));
    }

    public function test_it_fails_if_return_is_not_in_submitted_status(): void
    {
        $this->returnRequest->update(['status' => ReturnStatus::AdminVerified]);

        $action = app(VerifyReturnAction::class);

        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $this->returnRequest->fresh(), $this->validInput());
    }

    public function test_it_prevents_duplicate_verification_via_stale_version(): void
    {
        $action = app(VerifyReturnAction::class);

        $action->execute($this->staff, $this->returnRequest, $this->validInput());

        // Re-using the stale expectedVersion (1) against the now-verified (version 2) record must fail.
        $this->expectException(\RuntimeException::class);
        $action->execute($this->staff, $this->returnRequest->fresh(), $this->validInput());
    }

    public function test_non_staff_cannot_verify_a_return(): void
    {
        $koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $koperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $action = app(VerifyReturnAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($koperasi, $this->returnRequest, $this->validInput());
    }

    public function test_staff_from_another_warehouse_cannot_verify(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherStaff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherStaff->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $action = app(VerifyReturnAction::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($otherStaff, $this->returnRequest, $this->validInput());
    }
}
