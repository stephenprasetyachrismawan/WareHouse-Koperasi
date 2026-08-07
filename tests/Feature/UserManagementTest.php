<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\UserManagement\CreateCompanyUserAction;
use App\Actions\UserManagement\ToggleCompanyUserStatusAction;
use App\Actions\UserManagement\UpdateCompanyUserAction;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Warehouse $warehouseA;

    protected User $adminA;

    protected Company $companyB;

    protected Warehouse $warehouseB;

    protected User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Setup Company A and its Admin
        $createNewUserAction = new CreateNewUser;
        $this->adminA = $createNewUserAction->create([
            'name' => 'Admin Company A',
            'email' => 'admin@comp-a.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Company Alpha',
            'company_code' => 'COMP-A',
        ]);
        $this->companyA = $this->adminA->activeCompany();
        $this->warehouseA = $this->adminA->activeWarehouse();

        // Setup Company B and its Admin
        $this->adminB = $createNewUserAction->create([
            'name' => 'Admin Company B',
            'email' => 'admin@comp-b.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Company Beta',
            'company_code' => 'COMP-B',
        ]);
        $this->companyB = $this->adminB->activeCompany();
        $this->warehouseB = $this->adminB->activeWarehouse();
    }

    public function test_app_admin_can_create_internal_user_in_their_company(): void
    {
        $action = new CreateCompanyUserAction;

        $staffUser = $action->execute($this->adminA, [
            'name' => 'Staff Gudang Alpha',
            'email' => 'staff@comp-a.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'staff_admin',
        ]);

        $this->assertNotNull($staffUser);
        $this->assertEquals('Staff Gudang Alpha', $staffUser->name);

        $membership = WarehouseMembership::where('user_id', $staffUser->id)->first();
        $this->assertNotNull($membership);
        $this->assertEquals($this->companyA->id, $membership->company_id);
        $this->assertEquals('staff_admin', $membership->role);

        setPermissionsTeamId($this->companyA->id);
        $this->assertTrue($staffUser->hasRole('staff_admin'));
    }

    public function test_app_admin_cannot_create_user_with_super_admin_role(): void
    {
        $action = new CreateCompanyUserAction;

        $this->expectException(ValidationException::class);

        $action->execute($this->adminA, [
            'name' => 'Fake Super Admin',
            'email' => 'hacker@comp-a.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'super_admin',
        ]);
    }

    public function test_app_admin_cannot_view_or_manage_user_in_another_company(): void
    {
        $action = new CreateCompanyUserAction;
        $staffUserB = $action->execute($this->adminB, [
            'name' => 'Staff Beta',
            'email' => 'staff@comp-b.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'staff_admin',
        ]);

        // Attempt updating Staff Beta using Admin A credentials
        $updateAction = new UpdateCompanyUserAction;

        $this->expectException(AuthorizationException::class);

        $updateAction->execute($this->adminA, $staffUserB, [
            'name' => 'Hacked Staff Beta',
            'email' => 'staff@comp-b.com',
            'role' => 'kepala_gudang',
        ]);
    }

    public function test_app_admin_cannot_toggle_status_of_user_in_another_company(): void
    {
        $toggleAction = new ToggleCompanyUserStatusAction;

        $this->expectException(AuthorizationException::class);

        $toggleAction->execute($this->adminA, $this->adminB);
    }

    public function test_cannot_deactivate_self(): void
    {
        $toggleAction = new ToggleCompanyUserStatusAction;

        $this->expectException(AuthorizationException::class);

        $toggleAction->execute($this->adminA, $this->adminA);
    }

    public function test_cannot_deactivate_last_active_app_admin(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'status' => 'active']);

        // Create a membership as staff_admin so activeCompany() is available for superAdmin
        WarehouseMembership::create([
            'company_id' => $this->companyA->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $superAdmin->id,
            'role' => 'staff_admin',
            'status' => 'active',
        ]);

        $toggleAction = new ToggleCompanyUserStatusAction;

        // adminA is the only active app_admin in companyA.
        // SuperAdmin attempts to deactivate adminA -> triggers ValidationException because active app_admin count <= 1.
        $this->expectException(ValidationException::class);

        $toggleAction->execute($superAdmin, $this->adminA);
    }

    public function test_authorized_app_admin_can_toggle_user_status_active_and_suspended(): void
    {
        $createAction = new CreateCompanyUserAction;
        $staffUser = $createAction->execute($this->adminA, [
            'name' => 'Staff Alpha',
            'email' => 'staff-toggle@comp-a.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'staff_admin',
        ]);

        $this->assertEquals('active', $staffUser->status);

        $toggleAction = new ToggleCompanyUserStatusAction;
        $toggledUser = $toggleAction->execute($this->adminA, $staffUser);

        $this->assertEquals('suspended', $toggledUser->status);
        $this->assertEquals('suspended', $staffUser->fresh()->warehouseMemberships()->first()->status);

        // Toggle back to active
        $toggledUserBack = $toggleAction->execute($this->adminA, $staffUser);
        $this->assertEquals('active', $toggledUserBack->status);
    }
}
