<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_public_registration_creates_company_warehouse_user_and_app_admin_role_atomically(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Owner Person',
            'email' => 'owner@koperasi-mandiri.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Koperasi Mandiri Sejahtera',
            'company_code' => 'KOP-MANDIRI',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        // 1. Company created
        $company = Company::where('code', 'KOP-MANDIRI')->first();
        $this->assertNotNull($company);
        $this->assertEquals('Koperasi Mandiri Sejahtera', $company->name);
        $this->assertEquals('active', $company->status);

        // 2. Default Warehouse created
        $warehouse = Warehouse::where('company_id', $company->id)->first();
        $this->assertNotNull($warehouse);
        $this->assertStringContainsString('Koperasi Mandiri Sejahtera', $warehouse->name);

        // 3. User created
        $user = User::where('email', 'owner@koperasi-mandiri.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Owner Person', $user->name);

        // 4. Membership created with app_admin role
        $membership = WarehouseMembership::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertEquals('app_admin', $membership->role);
        $this->assertEquals('active', $membership->status);

        // 5. Spatie Team-scoped Role assigned
        setPermissionsTeamId($company->id);
        $this->assertTrue($user->hasRole('app_admin'));
    }

    public function test_public_registration_fails_when_company_name_is_missing(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Owner Person',
            'email' => 'owner@koperasi-mandiri.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => '',
        ]);

        $response->assertSessionHasErrors(['company_name']);
        $this->assertDatabaseMissing('users', ['email' => 'owner@koperasi-mandiri.com']);
    }

    public function test_public_registration_ignores_attempts_to_pass_privileged_roles(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Owner Person',
            'email' => 'owner@koperasi-mandiri.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'company_name' => 'Koperasi Mandiri Sejahtera',
            'role' => 'super_admin',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::where('email', 'owner@koperasi-mandiri.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_super_admin);

        $membership = WarehouseMembership::where('user_id', $user->id)->first();
        $this->assertEquals('app_admin', $membership->role);
    }
}
