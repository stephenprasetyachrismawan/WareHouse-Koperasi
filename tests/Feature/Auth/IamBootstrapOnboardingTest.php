<?php

namespace Tests\Feature\Auth;

use App\Enums\WarehouseRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for the deployment gap where `RoleAndPermissionSeeder`
 * was never invoked outside of tests, so every deployed environment reached
 * `SetupCompanyForUser`/`CreateNewUser` with zero roles and threw
 * `RoleDoesNotExist: app_admin`. These tests deliberately avoid manually
 * seeding IAM state in setUp() so they exercise the same starting point a
 * fresh deployment has: migrations only.
 */
class IamBootstrapOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrations_bootstrap_required_iam_roles_and_permissions(): void
    {
        $requiredRoles = [
            'super_admin',
            WarehouseRole::AppAdmin->value,
            WarehouseRole::KepalaGudang->value,
            WarehouseRole::StaffAdmin->value,
            WarehouseRole::Purchasing->value,
            WarehouseRole::Koperasi->value,
        ];

        foreach ($requiredRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->where('guard_name', 'web')->exists(),
                "Expected role [{$roleName}] to exist for guard [web] immediately after migrations."
            );
        }

        $this->assertTrue(Permission::where('guard_name', 'web')->exists());
    }

    public function test_google_company_completion_succeeds_on_a_freshly_migrated_database(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'google_id' => 'google-123',
            'status' => 'active',
        ]);

        session(['google_signin.user_id' => $user->id]);

        $this->post(route('auth.google.complete.store'), [
            'company_name' => 'Koperasi Mandiri Sejahtera',
            'company_code' => 'KOP-MANDIRI',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('companies', ['code' => 'KOP-MANDIRI']);
        $this->assertDatabaseHas('warehouse_memberships', [
            'user_id' => $user->id,
            'role' => 'app_admin',
            'status' => 'active',
        ]);
        $this->assertTrue($user->fresh()->hasRole('app_admin'));
    }

    public function test_normal_registration_completion_succeeds_on_a_freshly_migrated_database(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $this->post(route('register.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'company_name' => 'Koperasi Sumber Makmur',
            'company_code' => 'KOP-SUMBER',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('companies', ['code' => 'KOP-SUMBER']);
    }

    public function test_failed_role_assignment_rolls_back_company_warehouse_and_membership(): void
    {
        // Simulate a broken/partial deployment (e.g. the role template was
        // dropped or renamed) to prove SetupCompanyForUser's transaction
        // still leaves no orphaned tenant records behind.
        Role::where('name', WarehouseRole::AppAdmin->value)->where('guard_name', 'web')->delete();

        $user = User::factory()->create([
            'email' => 'orphan-check@example.com',
            'google_id' => 'google-999',
            'status' => 'active',
        ]);

        session(['google_signin.user_id' => $user->id]);

        $this->withoutExceptionHandling();

        try {
            $this->post(route('auth.google.complete.store'), [
                'company_name' => 'Gudang Koperasi Jateng',
                'company_code' => 'GUD-BBA',
            ]);
            $this->fail('Expected RoleDoesNotExist to propagate when the app_admin role template is missing.');
        } catch (RoleDoesNotExist) {
            // expected
        }

        $this->assertDatabaseMissing('companies', ['code' => 'GUD-BBA']);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('warehouses', 0);
        $this->assertDatabaseCount('warehouse_memberships', 0);
    }

    public function test_iam_bootstrap_is_idempotent_when_rerun(): void
    {
        $rolesBefore = Role::count();
        $permissionsBefore = Permission::count();

        DB::transaction(function () {
            (new RoleAndPermissionSeeder)->run();
        });

        $this->assertSame($rolesBefore, Role::count());
        $this->assertSame($permissionsBefore, Permission::count());
    }
}
