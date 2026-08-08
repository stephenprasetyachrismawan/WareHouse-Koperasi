<?php

namespace Tests\Feature\Pickup;

use App\Enums\PickupRequestStatus;
use App\Livewire\Pickup\Create;
use App\Livewire\Pickup\MyRequests;
use App\Models\Item;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PickupLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $kepalaGudang;

    protected User $staffAdmin;

    protected Warehouse $warehouse;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::factory()->create();

        $this->user = User::factory()->create();
        $this->kepalaGudang = User::factory()->create();
        $this->staffAdmin = User::factory()->create();

        // Assign memberships implicitly or directly via DB depending on app architecture
        $this->user->warehouseMemberships()->create([
            'warehouse_id' => $this->warehouse->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->item = Item::factory()->create();
        // Give it some stock
        $this->item->stockBalance()->create([
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);

        // Ensure tenant scope
        session(['tenant_id' => $this->warehouse->id]);
    }

    public function test_can_render_create_page()
    {
        $this->actingAs($this->user)
            ->get(route('pickup.create'))
            ->assertSuccessful();
    }

    public function test_can_submit_new_pickup_request()
    {
        Livewire::actingAs($this->user)
            ->test(Create::class)
            ->set('items', [
                ['id' => $this->item->id, 'name' => $this->item->name, 'quantity' => 5, 'notes' => 'Test'],
            ])
            ->call('submit')
            ->assertRedirect(route('pickup.my-requests'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pickup_requests', [
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_my_requests_shows_user_requests()
    {
        $request = PickupRequest::factory()->create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => PickupRequestStatus::Submitted,
        ]);

        Livewire::actingAs($this->user)
            ->test(MyRequests::class)
            ->assertSee($request->request_number);
    }
}
