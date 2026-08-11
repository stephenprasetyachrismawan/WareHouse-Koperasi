<?php

namespace Tests\Feature\Notifications;

use App\Actions\Pickup\ApprovePickupRequestAction;
use App\Actions\Pickup\CreatePickupRequestAction;
use App\Actions\Pickup\MarkPickupReadyAction;
use App\Actions\Pickup\SubmitPickupRequestAction;
use App\Actions\Returns\ApproveReturnAction;
use App\Actions\Returns\PrepareReplacementPickupAction;
use App\Actions\Returns\RejectReturnAction;
use App\Domain\Pickup\ValueObjects\PickupRequestInput;
use App\Domain\Pickup\ValueObjects\PickupRequestItemInput;
use App\Domain\Returns\Events\ReturnSubmitted;
use App\Enums\NotificationType;
use App\Enums\ReturnFaultAttribution;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\Item;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PickupAndReturnNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $koperasi;

    private User $staff;

    private User $kepalaGudang;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->warehouse = Warehouse::factory()->create();
        $this->item = Item::factory()->create(['warehouse_id' => $this->warehouse->id]);
        StockBalance::factory()->create(['warehouse_id' => $this->warehouse->id, 'item_id' => $this->item->id, 'quantity' => 20]);

        $this->koperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->koperasi->id,
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

        $this->kepalaGudang = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);
    }

    public function test_staff_receives_pickup_requested_notification(): void
    {
        $pickup = app(CreatePickupRequestAction::class)->execute($this->koperasi, new PickupRequestInput(
            warehouseId: $this->warehouse->id,
            userId: $this->koperasi->id,
            items: [new PickupRequestItemInput($this->item->id, 2)],
        ));

        app(SubmitPickupRequestAction::class)->execute($this->koperasi, $pickup);

        $notification = InboxNotification::where('type', NotificationType::PickupRequested->value)
            ->forRecipient($this->staff->id)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_koperasi_receives_ready_for_pickup_and_other_koperasi_does_not(): void
    {
        $otherKoperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherKoperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $pickup = app(CreatePickupRequestAction::class)->execute($this->koperasi, new PickupRequestInput(
            warehouseId: $this->warehouse->id,
            userId: $this->koperasi->id,
            items: [new PickupRequestItemInput($this->item->id, 2)],
        ));
        app(SubmitPickupRequestAction::class)->execute($this->koperasi, $pickup);
        app(ApprovePickupRequestAction::class)->execute($this->kepalaGudang, $pickup);
        app(MarkPickupReadyAction::class)->execute($this->staff, $pickup->fresh());

        $notification = InboxNotification::where('type', NotificationType::ReadyForPickup->value)
            ->forRecipient($this->koperasi->id)
            ->first();
        $this->assertNotNull($notification);

        $this->assertSame(0, InboxNotification::where('type', NotificationType::ReadyForPickup->value)
            ->forRecipient($otherKoperasi->id)->count());
    }

    public function test_staff_receives_return_submitted_notification(): void
    {
        $returnRequest = ReturnRequest::factory()->submitted()->create(['warehouse_id' => $this->warehouse->id]);
        ReturnSubmitted::dispatch($returnRequest);

        $notification = InboxNotification::where('type', NotificationType::ReturnSubmitted->value)
            ->forRecipient($this->staff->id)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_owning_koperasi_receives_return_status_without_fault_attribution_leak(): void
    {
        $membership = WarehouseMembership::where('user_id', $this->koperasi->id)->first();
        $returnRequest = ReturnRequest::factory()->waitingApproval()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
        ]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'item_id' => $this->item->id]);

        app(ApproveReturnAction::class)->execute($this->kepalaGudang, $returnRequest);

        $notification = InboxNotification::where('type', NotificationType::ReturnStatus->value)
            ->forRecipient($this->koperasi->id)
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringNotContainsString(ReturnFaultAttribution::Warehouse->value, $notification->message);
        $this->assertStringNotContainsString('SUPPLIER', $notification->message);
        $this->assertStringNotContainsString('WAREHOUSE', $notification->message);
    }

    public function test_owning_koperasi_receives_return_status_on_rejection_with_reason(): void
    {
        $membership = WarehouseMembership::where('user_id', $this->koperasi->id)->first();
        $returnRequest = ReturnRequest::factory()->waitingApproval()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
        ]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'item_id' => $this->item->id]);

        app(RejectReturnAction::class)->execute($this->kepalaGudang, $returnRequest, 'Bukti tidak jelas.');

        $notification = InboxNotification::where('type', NotificationType::ReturnStatus->value)
            ->forRecipient($this->koperasi->id)
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Bukti tidak jelas.', $notification->message);
    }

    public function test_owning_koperasi_receives_replacement_ready_and_others_do_not(): void
    {
        $otherKoperasi = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherKoperasi->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        $membership = WarehouseMembership::where('user_id', $this->koperasi->id)->first();
        $returnRequest = ReturnRequest::factory()->replacementPending()->create([
            'warehouse_id' => $this->warehouse->id,
            'cooperative_membership_id' => $membership->id,
        ]);
        ReturnRequestItem::factory()->create(['return_request_id' => $returnRequest->id, 'item_id' => $this->item->id, 'return_quantity' => 2]);

        app(PrepareReplacementPickupAction::class)->execute($returnRequest);

        $notification = InboxNotification::where('type', NotificationType::ReplacementReady->value)
            ->forRecipient($this->koperasi->id)
            ->first();
        $this->assertNotNull($notification);

        $this->assertSame(0, InboxNotification::where('type', NotificationType::ReplacementReady->value)
            ->forRecipient($otherKoperasi->id)->count());
    }
}
