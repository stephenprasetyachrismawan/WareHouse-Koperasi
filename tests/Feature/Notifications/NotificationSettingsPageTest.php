<?php

namespace Tests\Feature\Notifications;

use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_the_users_active_devices(): void
    {
        $user = User::factory()->create();
        $device = DeviceToken::factory()->for($user)->create(['device_name' => 'Chrome on Windows']);
        DeviceToken::factory()->for($user)->revoked()->create(['device_name' => 'Old Phone']);
        DeviceToken::factory()->create(['device_name' => 'Someone Elses Laptop']);

        $component = Livewire::actingAs($user)->test('pages::settings.notifications');

        $component->assertSee('Chrome on Windows');
        $component->assertDontSee('Old Phone');
        $component->assertDontSee('Someone Elses Laptop');
    }

    public function test_the_owner_can_revoke_a_device_from_the_page(): void
    {
        $user = User::factory()->create();
        $device = DeviceToken::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test('pages::settings.notifications')
            ->call('revokeDevice', $device->uuid);

        $this->assertFalse($device->fresh()->isActive());
    }

    public function test_a_user_cannot_revoke_another_users_device_from_the_page(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $device = DeviceToken::factory()->for($owner)->create();

        Livewire::actingAs($attacker)
            ->test('pages::settings.notifications')
            ->call('revokeDevice', $device->uuid);

        $this->assertTrue($device->fresh()->isActive());
    }

    public function test_toggling_push_off_persists_a_disabled_wildcard_preference(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::settings.notifications')
            ->set('pushEnabled', false);

        $preference = NotificationPreference::where('user_id', $user->id)->first();
        $this->assertNotNull($preference);
        $this->assertFalse($preference->enabled);
        $this->assertNull($preference->warehouse_id);
        $this->assertNull($preference->notification_type);
        $this->assertSame('push', $preference->channel);
    }

    public function test_the_page_defaults_to_push_enabled_with_no_preference_row(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test('pages::settings.notifications');

        $component->assertSet('pushEnabled', true);
        $this->assertSame(0, NotificationPreference::count());
    }
}
