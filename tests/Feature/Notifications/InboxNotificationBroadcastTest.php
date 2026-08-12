<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\CreateInboxNotificationAction;
use App\Domain\Notifications\Events\InboxNotificationCreated;
use App\Domain\Notifications\ValueObjects\CreateInboxNotificationInput;
use App\Enums\NotificationType;
use App\Models\InboxNotification;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InboxNotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function input(User $user, string $correlationKey): CreateInboxNotificationInput
    {
        return new CreateInboxNotificationInput(
            recipientId: $user->id,
            warehouseId: null,
            type: NotificationType::SecurityAlert,
            title: 'Test',
            message: 'Test message',
            correlationKey: $correlationKey,
        );
    }

    public function test_creating_a_new_notification_dispatches_the_delivery_event(): void
    {
        Event::fake([InboxNotificationCreated::class]);
        $user = User::factory()->create();

        $notification = app(CreateInboxNotificationAction::class)->execute($this->input($user, 'key-1'));

        Event::assertDispatched(InboxNotificationCreated::class, function ($event) use ($notification) {
            return $event->inboxNotificationId === $notification->id;
        });
    }

    public function test_idempotent_resolution_does_not_redispatch_the_delivery_event(): void
    {
        $user = User::factory()->create();
        $action = app(CreateInboxNotificationAction::class);
        $action->execute($this->input($user, 'key-2'));

        Event::fake([InboxNotificationCreated::class]);
        $action->execute($this->input($user, 'key-2'));

        Event::assertNotDispatched(InboxNotificationCreated::class);
    }

    public function test_event_is_not_dispatched_if_the_outer_transaction_rolls_back(): void
    {
        Event::fake([InboxNotificationCreated::class]);
        $user = User::factory()->create();

        try {
            DB::transaction(function () use ($user) {
                app(CreateInboxNotificationAction::class)->execute($this->input($user, 'key-3'));
                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(0, InboxNotification::count());
        Event::assertNotDispatched(InboxNotificationCreated::class);
    }

    public function test_broadcast_channel_is_the_recipients_private_notification_channel(): void
    {
        $user = User::factory()->create();
        $notification = InboxNotification::factory()->create(['recipient_id' => $user->id]);

        $event = new InboxNotificationCreated($notification->id);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals(
            "private-user.{$user->id}.warehouse.{$notification->warehouse_id}.notifications",
            $channels[0]->name
        );
    }

    public function test_broadcast_payload_is_minimal_and_safe(): void
    {
        $user = User::factory()->create();
        $notification = InboxNotification::factory()->create([
            'recipient_id' => $user->id,
            'metadata' => ['secret_internal_note' => 'should-not-leak'],
        ]);

        $event = new InboxNotificationCreated($notification->id);
        $payload = $event->broadcastWith();

        $this->assertEqualsCanonicalizing(
            ['uuid', 'type', 'title', 'message', 'action_route', 'warehouse_id', 'created_at'],
            array_keys($payload)
        );
        $this->assertArrayNotHasKey('metadata', $payload);
        $this->assertStringNotContainsString('should-not-leak', json_encode($payload));
    }

    public function test_broadcast_resolves_to_no_channels_if_notification_was_deleted(): void
    {
        $event = new InboxNotificationCreated(999999);

        $this->assertSame([], $event->broadcastOn());
    }
}
