<?php

namespace Tests\Feature\Notifications;

use App\Actions\Procurement\CreatePurchaseOrderAction;
use App\Actions\Procurement\CreatePurchaseRequestGroupAction;
use App\Actions\Procurement\SendPurchaseOrderAction;
use App\Domain\Procurement\ValueObjects\AllocationInput;
use App\Domain\Procurement\ValueObjects\CreateGroupInput;
use App\Domain\Procurement\ValueObjects\CreatePurchaseOrderInput;
use App\Enums\NotificationType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PurchaseOrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_creator_receives_po_status_and_receipt_staff_receives_receipt_required(): void
    {
        Gate::before(fn () => true);

        $warehouse = Warehouse::factory()->create();
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);
        $supplier = Supplier::factory()->create(['warehouse_id' => $warehouse->id, 'is_active' => true]);

        $prCreator = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $prCreator->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);

        $purchasing = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $purchasing->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Purchasing->value,
            'status' => 'active',
        ]);

        $pr = PurchaseRequest::create([
            'warehouse_id' => $warehouse->id,
            'request_number' => 'PR-TEST-'.uniqid(),
            'source' => 'MANUAL_STAFF',
            'urgency' => 'NORMAL',
            'status' => PurchaseRequestStatus::Approved->value,
            'created_by' => $prCreator->id,
            'approved_at' => now(),
        ]);
        $prItem = $pr->items()->create(['item_id' => $item->id, 'requested_quantity' => 10]);

        $group = app(CreatePurchaseRequestGroupAction::class)->execute($purchasing, new CreateGroupInput(
            warehouseId: $warehouse->id,
            notes: 'Test group',
            allocations: [new AllocationInput($prItem->id, 10)],
        ));

        $po = app(CreatePurchaseOrderAction::class)->execute($purchasing, new CreatePurchaseOrderInput(
            warehouseId: $warehouse->id,
            groupId: $group->id,
            supplierId: $supplier->id,
            notes: 'Test PO',
            items: [['item_id' => $item->id, 'unit_cost' => 5000]],
        ));

        app(SendPurchaseOrderAction::class)->execute($purchasing, $po);

        $poStatus = InboxNotification::forRecipient($prCreator->id)
            ->where('type', NotificationType::PoStatus->value)
            ->first();
        $this->assertNotNull($poStatus);

        $receiptRequired = InboxNotification::forRecipient($purchasing->id)
            ->where('type', NotificationType::ReceiptRequired->value)
            ->first();
        $this->assertNotNull($receiptRequired);
    }
}
