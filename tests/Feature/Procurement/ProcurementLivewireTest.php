<?php

namespace Tests\Feature\Procurement;

use App\Enums\PurchaseRequestSource;
use App\Enums\PurchaseRequestStatus;
use App\Enums\WarehouseRole;
use App\Livewire\Procurement\ApprovalInbox;
use App\Livewire\Procurement\Create;
use App\Livewire\Procurement\Index;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\CompanyAndWarehouseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcurementLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(WarehouseRole $role, Warehouse $warehouse): User
    {
        $user = User::factory()->create();
        setPermissionsTeamId($warehouse->company_id);
        $user->assignRole($role->value);
        $user->warehouseMemberships()->create([
            'warehouse_id' => $warehouse->id,
            'company_id' => $warehouse->company_id,
            'role' => $role->value,
            'status' => 'active',
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, CompanyAndWarehouseSeeder::class]);
    }

    public function test_index_component_renders()
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::KepalaGudang, $warehouse);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertStatus(200);
    }

    public function test_create_component_duplicate_warning()
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);

        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::Draft,
        ]);
        $pr->items()->create([
            'item_id' => $item->id,
            'requested_quantity' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('items.0.item_id', $item->id)
            ->set('items.0.quantity', 5)
            ->assertSet('duplicateWarning', true);
    }

    public function test_create_component_save_creates_purchase_request()
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::StaffAdmin, $warehouse);
        $item = Item::factory()->create(['warehouse_id' => $warehouse->id]);

        Livewire::actingAs($user)
            ->test(Create::class)
            ->set('items.0.item_id', $item->id)
            ->set('items.0.quantity', 3)
            ->set('urgency', 'NORMAL')
            ->call('save')
            ->assertRedirect(route('procurement.index'));

        $this->assertDatabaseHas('purchase_requests', [
            'warehouse_id' => $warehouse->id,
            'created_by' => $user->id,
            'source' => PurchaseRequestSource::ManualStaff->value,
            'status' => PurchaseRequestStatus::WaitingApproval->value,
        ]);
    }

    public function test_approval_inbox_can_approve()
    {
        $warehouse = Warehouse::first();
        $user = $this->createAuthorizedUser(WarehouseRole::KepalaGudang, $warehouse);

        $pr = PurchaseRequest::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => PurchaseRequestStatus::WaitingApproval,
        ]);

        Livewire::actingAs($user)
            ->test(ApprovalInbox::class)
            ->call('openActionModal', 'approve_pr', $pr->id)
            ->call('submitAction')
            ->assertHasNoErrors();

        $this->assertEquals(PurchaseRequestStatus::Approved, $pr->fresh()->status);
    }
}
