<?php

namespace Tests\Feature\Notifications;

use App\Livewire\Notifications\Inbox;
use App\Livewire\Notifications\UnreadBadge;
use App\Models\InboxNotification;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InboxLivewireTest extends TestCase
{
    use RefreshDatabase;

    private Warehouse $warehouse;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();
        $this->user = User::factory()->create();
        WarehouseMembership::factory()->create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'active',
        ]);
    }

    public function test_user_sees_their_own_inbox_newest_first(): void
    {
        $older = InboxNotification::factory()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'title' => 'Older notification',
            'created_at' => now()->subDay(),
        ]);
        $newer = InboxNotification::factory()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'title' => 'Newer notification',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)->test(Inbox::class);

        $component->assertSeeInOrder(['Newer notification', 'Older notification']);
    }

    public function test_unread_filter_shows_only_unread(): void
    {
        InboxNotification::factory()->read()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'title' => 'Already read',
        ]);
        InboxNotification::factory()->unread()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'title' => 'Still unread',
        ]);

        Livewire::actingAs($this->user)
            ->test(Inbox::class)
            ->call('setFilter', 'unread')
            ->assertSee('Still unread')
            ->assertDontSee('Already read');
    }

    public function test_mark_read_updates_unread_count(): void
    {
        $notification = InboxNotification::factory()->unread()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(Inbox::class)
            ->call('markRead', $notification->id)
            ->assertSet('filter', 'all');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_repeated_mark_read_is_safe(): void
    {
        $notification = InboxNotification::factory()->unread()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $component = Livewire::actingAs($this->user)->test(Inbox::class);
        $component->call('markRead', $notification->id);
        $component->call('markRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_other_users_notification_is_not_shown_and_cannot_be_marked_read(): void
    {
        $otherUser = User::factory()->create();
        $notification = InboxNotification::factory()->unread()->create([
            'recipient_id' => $otherUser->id,
            'title' => 'Not for me',
        ]);

        Livewire::actingAs($this->user)
            ->test(Inbox::class)
            ->assertDontSee('Not for me')
            ->call('markRead', $notification->id);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_unread_badge_shows_correct_count(): void
    {
        InboxNotification::factory()->unread()->count(3)->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(UnreadBadge::class)
            ->assertSee('3');
    }

    public function test_mark_all_read_clears_the_badge(): void
    {
        InboxNotification::factory()->unread()->count(3)->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(Inbox::class)
            ->call('markAllRead');

        $this->assertSame(0, InboxNotification::forRecipient($this->user->id)->unread()->count());
    }

    public function test_unread_badge_reflects_a_notification_created_after_mount_once_echo_event_arrives(): void
    {
        $component = Livewire::actingAs($this->user)->test(UnreadBadge::class);
        $component->assertDontSeeHtml('bg-red-500');

        InboxNotification::factory()->unread()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Simulates the browser dispatching Livewire.dispatch('inbox-notification-received')
        // after Echo receives the broadcast — the component re-renders from the
        // database rather than trusting anything carried on the event itself.
        $component->dispatch('inbox-notification-received', uuid: 'irrelevant')
            ->assertSeeHtml('bg-red-500');
    }

    public function test_inbox_reflects_a_notification_created_after_mount_once_echo_event_arrives(): void
    {
        $component = Livewire::actingAs($this->user)->test(Inbox::class);
        $component->assertDontSee('Arrived via realtime');

        InboxNotification::factory()->unread()->create([
            'recipient_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'title' => 'Arrived via realtime',
        ]);

        $component->dispatch('inbox-notification-received', uuid: 'irrelevant')
            ->assertSee('Arrived via realtime');
    }
}
