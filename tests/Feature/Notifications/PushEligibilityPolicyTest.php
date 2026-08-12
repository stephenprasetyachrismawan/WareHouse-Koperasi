<?php

namespace Tests\Feature\Notifications;

use App\Domain\Notifications\Support\PushEligibilityPolicy;
use App\Enums\NotificationType;
use App\Enums\WarehouseRole;
use App\Models\DeviceToken;
use App\Models\InboxNotification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PushEligibilityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $kepalaGudang;

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
        DeviceToken::factory()->for($this->kepalaGudang)->create();
    }

    private function notification(NotificationType $type): InboxNotification
    {
        return InboxNotification::factory()->create([
            'recipient_id' => $this->kepalaGudang->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => $type,
        ]);
    }

    public function test_approval_required_is_eligible_for_a_head_with_a_consented_device(): void
    {
        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertTrue($eligible);
    }

    public function test_cancellation_required_is_eligible_for_a_head_with_a_consented_device(): void
    {
        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::CancellationRequired));

        $this->assertTrue($eligible);
    }

    /**
     * @return list<array{0: NotificationType}>
     */
    public static function nonPushTypes(): array
    {
        return [
            [NotificationType::ApprovalApproved],
            [NotificationType::ApprovalRejected],
            [NotificationType::DuplicatePurchaseWarning],
            [NotificationType::PurchaseRequestStatus],
            [NotificationType::PoStatus],
            [NotificationType::ReceiptRequired],
            [NotificationType::PickupRequested],
            [NotificationType::ReadyForPickup],
            [NotificationType::ReturnSubmitted],
            [NotificationType::ReturnStatus],
            [NotificationType::ReplacementReady],
        ];
    }

    #[DataProvider('nonPushTypes')]
    public function test_non_baseline_notification_types_are_never_push_eligible(NotificationType $type): void
    {
        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification($type));

        $this->assertFalse($eligible);
    }

    public function test_a_recipient_with_no_active_device_is_not_eligible(): void
    {
        DeviceToken::where('user_id', $this->kepalaGudang->id)->delete();

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertFalse($eligible);
    }

    public function test_a_recipient_with_only_a_revoked_device_is_not_eligible(): void
    {
        DeviceToken::where('user_id', $this->kepalaGudang->id)->delete();
        DeviceToken::factory()->for($this->kepalaGudang)->revoked()->create();

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertFalse($eligible);
    }

    public function test_an_inactive_recipient_is_not_eligible(): void
    {
        $this->kepalaGudang->update(['status' => 'suspended']);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertFalse($eligible);
    }

    public function test_a_recipient_who_no_longer_holds_the_approval_permission_is_not_eligible(): void
    {
        WarehouseMembership::where('user_id', $this->kepalaGudang->id)->update(['status' => 'suspended']);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertFalse($eligible);
    }

    public function test_a_recipient_without_the_relevant_permission_role_is_not_eligible(): void
    {
        $staff = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $staff->id,
            'warehouse_id' => $this->warehouse->id,
            'role' => WarehouseRole::StaffAdmin->value,
            'status' => 'active',
        ]);
        DeviceToken::factory()->for($staff)->create();

        $notification = InboxNotification::factory()->create([
            'recipient_id' => $staff->id,
            'warehouse_id' => $this->warehouse->id,
            'type' => NotificationType::ApprovalRequired,
        ]);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($notification);

        $this->assertFalse($eligible);
    }

    public function test_an_explicit_disabled_preference_for_the_exact_type_blocks_eligibility(): void
    {
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => NotificationType::ApprovalRequired,
            'channel' => 'push',
        ]);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertFalse($eligible);
    }

    public function test_a_disabled_preference_for_a_different_type_does_not_block_this_type(): void
    {
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => NotificationType::CancellationRequired,
            'channel' => 'push',
        ]);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertTrue($eligible);
    }

    public function test_a_wildcard_disabled_preference_for_all_types_blocks_every_type(): void
    {
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => null,
            'channel' => 'push',
        ]);

        $this->assertFalse(app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired)));
        $this->assertFalse(app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::CancellationRequired)));
    }

    public function test_a_specific_type_preference_overrides_a_wildcard_preference(): void
    {
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => null,
            'channel' => 'push',
        ]);
        NotificationPreference::factory()->for($this->kepalaGudang)->create([
            'warehouse_id' => $this->warehouse->id,
            'notification_type' => NotificationType::ApprovalRequired,
            'channel' => 'push',
            'enabled' => true,
        ]);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertTrue($eligible);
    }

    public function test_a_preference_scoped_to_a_different_warehouse_does_not_apply(): void
    {
        $otherWarehouse = Warehouse::factory()->create();
        NotificationPreference::factory()->for($this->kepalaGudang)->disabled()->create([
            'warehouse_id' => $otherWarehouse->id,
            'notification_type' => NotificationType::ApprovalRequired,
            'channel' => 'push',
        ]);

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertTrue($eligible);
    }

    public function test_no_preference_row_defaults_to_eligible(): void
    {
        $this->assertSame(0, NotificationPreference::count());

        $eligible = app(PushEligibilityPolicy::class)->isEligible($this->notification(NotificationType::ApprovalRequired));

        $this->assertTrue($eligible);
    }
}
