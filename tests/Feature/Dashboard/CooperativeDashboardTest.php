<?php

namespace Tests\Feature\Dashboard;

use App\Enums\PickupRequestStatus;
use App\Enums\WarehouseRole;
use App\Models\InboxNotification;
use App\Models\PickupRequest;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CooperativeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_has_simple_empty_states_and_safe_ctas(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->cooperativeUser($warehouse);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Request Barang')
            ->assertSee('Retur Barang')
            ->assertSee('Belum ada pengambilan yang siap diambil.')
            ->assertDontSee('Stok internal')
            ->assertDontSee('Supplier')
            ->assertDontSee('Purchase Order');
    }

    public function test_only_the_current_cooperative_own_data_is_visible(): void
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Gudang Pusat']);
        $otherWarehouse = Warehouse::factory()->create(['name' => 'Gudang Lain']);
        $user = $this->cooperativeUser($warehouse);
        $otherUser = User::factory()->create(['name' => 'Koperasi Lain']);
        $otherMembership = WarehouseMembership::factory()->create([
            'user_id' => $otherUser->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        PickupRequest::factory()->for($warehouse)->readyForPickup()->create([
            'user_id' => $user->id,
            'request_number' => 'PICKUP-MILIK-SAYA',
        ]);
        PickupRequest::factory()->for($warehouse)->readyForPickup()->create([
            'user_id' => $otherUser->id,
            'request_number' => 'PICKUP-MILIK-ORANG-LAIN',
        ]);
        PickupRequest::factory()->for($otherWarehouse)->readyForPickup()->create([
            'user_id' => $user->id,
            'request_number' => 'PICKUP-WAREHOUSE-LAIN',
        ]);
        ReturnRequest::factory()->for($warehouse)->create([
            'cooperative_membership_id' => $otherMembership->id,
            'return_number' => 'RETUR-MILIK-ORANG-LAIN',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('PICKUP-MILIK-SAYA')
            ->assertDontSee('PICKUP-MILIK-ORANG-LAIN')
            ->assertDontSee('PICKUP-WAREHOUSE-LAIN')
            ->assertDontSee('RETUR-MILIK-ORANG-LAIN');
    }

    public function test_latest_status_replacement_and_unread_inbox_are_scoped_to_the_membership(): void
    {
        $warehouse = Warehouse::factory()->create();
        $user = $this->cooperativeUser($warehouse);
        $membership = $user->activeMembership();

        PickupRequest::factory()->for($warehouse)->create([
            'user_id' => $user->id,
            'status' => PickupRequestStatus::Completed,
            'request_number' => 'PICKUP-LAMA',
            'submitted_at' => now()->subDay(),
        ]);
        PickupRequest::factory()->for($warehouse)->create([
            'user_id' => $user->id,
            'status' => PickupRequestStatus::Submitted,
            'request_number' => 'PICKUP-TERBARU',
            'submitted_at' => now(),
        ]);
        ReturnRequest::factory()->for($warehouse)->replacementPending()->create([
            'cooperative_membership_id' => $membership->id,
            'return_number' => 'RETUR-MENUNGGU-PENGGANTIAN',
        ]);
        ReturnRequest::factory()->for($warehouse)->readyForRepickup()->create([
            'cooperative_membership_id' => $membership->id,
            'return_number' => 'RETUR-SIAP-PENGAMBILAN',
            'submitted_at' => now()->addMinute(),
        ]);
        InboxNotification::factory()->for($warehouse)->unread()->count(2)->create([
            'recipient_id' => $user->id,
        ]);
        InboxNotification::factory()->for($warehouse)->unread()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('PICKUP-TERBARU')
            ->assertDontSee('PICKUP-LAMA')
            ->assertSee('RETUR-SIAP-PENGAMBILAN')
            ->assertSee('2');
    }

    private function cooperativeUser(Warehouse $warehouse): User
    {
        $user = User::factory()->create();

        WarehouseMembership::factory()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'role' => WarehouseRole::Koperasi->value,
            'status' => 'active',
        ]);

        return $user;
    }
}
