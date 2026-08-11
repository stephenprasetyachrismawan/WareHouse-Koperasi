<?php

namespace Tests\Feature\Notifications;

use App\Actions\Procurement\ApprovePurchaseCancellationAction;
use App\Actions\Procurement\RejectPurchaseCancellationAction;
use App\Actions\Procurement\RequestPurchaseCancellationAction;
use App\Enums\NotificationType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CancellationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $staff;

    private User $kepalaGudang;

    private PurchaseRequest $purchaseRequest;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->warehouse = Warehouse::factory()->create();

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

        $this->purchaseRequest = PurchaseRequest::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->staff->id,
            'status' => PurchaseRequestStatus::WaitingApproval->value,
        ]);
    }

    public function test_kepala_gudang_receives_cancellation_required(): void
    {
        app(RequestPurchaseCancellationAction::class)->execute($this->staff, $this->purchaseRequest, 'Tidak jadi dibutuhkan.');

        $notification = InboxNotification::forRecipient($this->kepalaGudang->id)
            ->where('type', NotificationType::CancellationRequired->value)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_requester_receives_status_when_cancellation_approved(): void
    {
        $cancellation = app(RequestPurchaseCancellationAction::class)->execute($this->staff, $this->purchaseRequest, 'Tidak jadi.');

        app(ApprovePurchaseCancellationAction::class)->execute($this->kepalaGudang, $cancellation);

        $notification = InboxNotification::forRecipient($this->staff->id)
            ->where('type', NotificationType::CancellationStatus->value)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_requester_receives_status_when_cancellation_rejected(): void
    {
        $cancellation = app(RequestPurchaseCancellationAction::class)->execute($this->staff, $this->purchaseRequest, 'Tidak jadi.');

        app(RejectPurchaseCancellationAction::class)->execute($this->kepalaGudang, $cancellation, 'Barang sudah diproses.');

        $notification = InboxNotification::forRecipient($this->staff->id)
            ->where('type', NotificationType::CancellationStatus->value)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_cross_tenant_head_receives_nothing(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherHead->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        app(RequestPurchaseCancellationAction::class)->execute($this->staff, $this->purchaseRequest, 'Tidak jadi.');

        $this->assertSame(0, InboxNotification::forRecipient($otherHead->id)->count());
    }
}
