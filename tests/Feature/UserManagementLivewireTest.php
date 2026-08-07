<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Livewire\Company\Users\Create;
use App\Livewire\Company\Users\Edit;
use App\Livewire\Company\Users\Index;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Warehouse $warehouseA;

    protected User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $createNewUserAction = new CreateNewUser;
        $this->adminA = $createNewUserAction->create([
            'name' => 'Admin Alpha',
            'email' => 'admin@alpha.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Company Alpha',
            'company_code' => 'ALPHA',
        ]);
        $this->companyA = $this->adminA->activeCompany();
        $this->warehouseA = $this->adminA->activeWarehouse();
    }

    public function test_livewire_create_user_component_saves_new_user(): void
    {
        Livewire::actingAs($this->adminA)
            ->test(Create::class)
            ->set('name', 'Budi Staff')
            ->set('email', 'budi@alpha.com')
            ->set('role', 'staff_admin')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('company.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'budi@alpha.com',
            'name' => 'Budi Staff',
        ]);

        $createdUser = User::where('email', 'budi@alpha.com')->first();
        $this->assertNotNull($createdUser);

        $membership = WarehouseMembership::where('user_id', $createdUser->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals($this->companyA->id, $membership->company_id);
        $this->assertEquals('staff_admin', $membership->role);
    }

    public function test_livewire_create_user_component_shows_validation_errors(): void
    {
        Livewire::actingAs($this->adminA)
            ->test(Create::class)
            ->set('name', '')
            ->set('email', 'invalid-email')
            ->set('password', 'short')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password']);
    }

    public function test_livewire_edit_user_component_updates_user(): void
    {
        $staffUser = User::factory()->create(['name' => 'Old Name', 'email' => 'old@alpha.com']);
        WarehouseMembership::create([
            'company_id' => $this->companyA->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $staffUser->id,
            'role' => 'staff_admin',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->adminA)
            ->test(Edit::class, ['user' => $staffUser])
            ->set('name', 'Updated Name')
            ->set('role', 'kepala_gudang')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('company.users.index'));

        $this->assertEquals('Updated Name', $staffUser->fresh()->name);
        $this->assertEquals('kepala_gudang', $staffUser->fresh()->warehouseMemberships()->first()->role);
    }

    public function test_livewire_index_user_component_can_toggle_status(): void
    {
        $staffUser = User::factory()->create(['name' => 'Toggle Target', 'email' => 'target@alpha.com', 'status' => 'active']);
        WarehouseMembership::create([
            'company_id' => $this->companyA->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $staffUser->id,
            'role' => 'staff_admin',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->adminA)
            ->test(Index::class)
            ->call('toggleStatus', $staffUser->id)
            ->assertSee(__('User status updated successfully.'));

        $this->assertEquals('suspended', $staffUser->fresh()->status);
    }
}
