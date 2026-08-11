<?php

namespace Tests\Feature\Notifications;

use App\Actions\Procurement\ApprovePurchaseRequestAction;
use App\Actions\Procurement\RejectPurchaseRequestAction;
use App\Actions\Procurement\SubmitPurchaseForApprovalAction;
use App\Domain\Procurement\Events\PurchaseRequestSubmitted;
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

class PurchaseRequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $creator;

    private User $kepalaGudang;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->warehouse = Warehouse::factory()->create();

        $this->creator = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->creator->id,
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

    private function draftPurchaseRequest(bool $duplicateOverride = false): PurchaseRequest
    {
        return PurchaseRequest::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $this->creator->id,
            'status' => PurchaseRequestStatus::Draft->value,
            'is_duplicate_override' => $duplicateOverride,
        ]);
    }

    public function test_kepala_gudang_receives_approval_required_when_submitted(): void
    {
        $pr = $this->draftPurchaseRequest();

        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $pr);

        $notification = InboxNotification::forRecipient($this->kepalaGudang->id)
            ->where('type', NotificationType::ApprovalRequired->value)
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals($this->warehouse->id, $notification->warehouse_id);
        $this->assertEquals(PurchaseRequest::class, $notification->subject_type);
        $this->assertEquals($pr->id, $notification->subject_id);
    }

    public function test_kepala_gudang_from_another_warehouse_receives_nothing(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        $otherHead = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $otherHead->id,
            'warehouse_id' => $otherWarehouse->id,
            'role' => WarehouseRole::KepalaGudang->value,
            'status' => 'active',
        ]);

        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $this->draftPurchaseRequest());

        $this->assertSame(0, InboxNotification::forRecipient($otherHead->id)->count());
    }

    public function test_duplicate_override_also_notifies_duplicate_warning(): void
    {
        $pr = $this->draftPurchaseRequest(duplicateOverride: true);

        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $pr);

        $this->assertNotNull(InboxNotification::forRecipient($this->kepalaGudang->id)
            ->where('type', NotificationType::DuplicatePurchaseWarning->value)->first());
    }

    public function test_non_duplicate_submission_does_not_create_duplicate_warning(): void
    {
        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $this->draftPurchaseRequest());

        $this->assertSame(0, InboxNotification::forRecipient($this->kepalaGudang->id)
            ->where('type', NotificationType::DuplicatePurchaseWarning->value)->count());
    }

    public function test_creator_receives_status_notification_when_approved(): void
    {
        $pr = $this->draftPurchaseRequest();
        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $pr);

        app(ApprovePurchaseRequestAction::class)->execute($this->kepalaGudang, $pr->fresh());

        $notification = InboxNotification::forRecipient($this->creator->id)
            ->where('type', NotificationType::ApprovalApproved->value)
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_creator_receives_status_notification_when_rejected(): void
    {
        $pr = $this->draftPurchaseRequest();
        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $pr);

        app(RejectPurchaseRequestAction::class)->execute($this->kepalaGudang, $pr->fresh(), 'Anggaran tidak cukup');

        $notification = InboxNotification::forRecipient($this->creator->id)
            ->where('type', NotificationType::ApprovalRejected->value)
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Anggaran tidak cukup', $notification->message);
    }

    public function test_retrying_the_submitted_event_does_not_duplicate_the_notification(): void
    {
        $pr = $this->draftPurchaseRequest();
        app(SubmitPurchaseForApprovalAction::class)->execute($this->creator, $pr);

        // Simulate the domain event being replayed (e.g. queue retry).
        event(new PurchaseRequestSubmitted($pr->fresh(), $this->creator));

        $this->assertSame(1, InboxNotification::forRecipient($this->kepalaGudang->id)
            ->where('type', NotificationType::ApprovalRequired->value)->count());
    }
}
