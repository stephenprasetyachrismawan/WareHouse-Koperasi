<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\MarkNotificationReadAction;
use App\Models\InboxNotification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkNotificationReadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipient_can_mark_their_own_notification_read(): void
    {
        $user = User::factory()->create();
        $notification = InboxNotification::factory()->unread()->create(['recipient_id' => $user->id, 'warehouse_id' => null]);

        $result = app(MarkNotificationReadAction::class)->execute($user, $notification);

        $this->assertNotNull($result->read_at);
    }

    public function test_marking_an_already_read_notification_again_is_safe(): void
    {
        $user = User::factory()->create();
        $notification = InboxNotification::factory()->read()->create(['recipient_id' => $user->id, 'warehouse_id' => null]);
        $originalReadAt = $notification->read_at;

        $result = app(MarkNotificationReadAction::class)->execute($user, $notification);

        $this->assertEquals($originalReadAt->timestamp, $result->read_at->timestamp);
    }

    public function test_another_user_cannot_mark_it_read(): void
    {
        $notification = InboxNotification::factory()->unread()->create();
        $otherUser = User::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(MarkNotificationReadAction::class)->execute($otherUser, $notification);
    }
}
