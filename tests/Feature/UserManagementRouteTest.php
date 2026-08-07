<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\UserManagement\CreateCompanyUserAction;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementRouteTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Warehouse $warehouseA;

    protected User $adminA;

    protected User $staffA;

    protected Company $companyB;

    protected Warehouse $warehouseB;

    protected User $adminB;

    protected User $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $createNewUserAction = new CreateNewUser;

        // Company A setup
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

        $createStaffAction = new CreateCompanyUserAction;
        $this->staffA = $createStaffAction->execute($this->adminA, [
            'name' => 'Staff Alpha',
            'email' => 'staff@alpha.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'staff_admin',
        ]);

        // Company B setup
        $this->adminB = $createNewUserAction->create([
            'name' => 'Admin Beta',
            'email' => 'admin@beta.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Company Beta',
            'company_code' => 'BETA',
        ]);
        $this->companyB = $this->adminB->activeCompany();
        $this->warehouseB = $this->adminB->activeWarehouse();

        $this->staffB = $createStaffAction->execute($this->adminB, [
            'name' => 'Staff Beta',
            'email' => 'staff@beta.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'staff_admin',
        ]);
    }

    public function test_guest_is_redirected_from_user_management_routes(): void
    {
        $this->get(route('company.users.index'))->assertRedirect(route('login'));
        $this->get(route('company.users.create'))->assertRedirect(route('login'));
        $this->get(route('company.users.edit', $this->adminA))->assertRedirect(route('login'));
    }

    public function test_authorized_app_admin_can_access_user_management_pages(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('company.users.index'))
            ->assertOk()
            ->assertSee('Admin Alpha')
            ->assertSee('Staff Alpha')
            ->assertDontSee('Admin Beta')
            ->assertDontSee('Staff Beta');

        $this->actingAs($this->adminA)
            ->get(route('company.users.create'))
            ->assertOk();

        $this->actingAs($this->adminA)
            ->get(route('company.users.edit', $this->staffA))
            ->assertOk()
            ->assertSee('Staff Alpha');
    }

    public function test_app_admin_cannot_access_edit_page_of_user_in_another_company(): void
    {
        // Admin A trying to access edit route of Staff B (Company B) via direct URL
        $this->actingAs($this->adminA)
            ->get(route('company.users.edit', $this->staffB))
            ->assertForbidden();
    }

    public function test_non_admin_role_cannot_access_user_management_routes(): void
    {
        $this->actingAs($this->staffA)
            ->get(route('company.users.index'))
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->get(route('company.users.create'))
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->get(route('company.users.edit', $this->adminA))
            ->assertForbidden();
    }

    public function test_suspended_user_is_denied_access(): void
    {
        // Suspend Staff A
        $this->staffA->update(['status' => 'suspended']);
        $this->staffA->warehouseMemberships()->update(['status' => 'suspended']);

        $this->actingAs($this->staffA)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
