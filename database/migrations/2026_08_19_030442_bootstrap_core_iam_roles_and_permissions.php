<?php

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Bootstrap the core IAM role/permission templates as part of deployment
     * (`php artisan migrate`), not as a manually-invoked seeder step.
     *
     * Without this, every fresh deployment reaches
     * `SetupCompanyForUser`/`CreateNewUser` with zero roles and fails with
     * `RoleDoesNotExist: app_admin` on the first onboarding attempt — the
     * required roles only ever existed in test databases, where
     * `RoleAndPermissionSeeder` was seeded manually in `setUp()`.
     *
     * `RoleAndPermissionSeeder` is idempotent (`Role::findOrCreate` /
     * `Permission::findOrCreate` / `syncPermissions`) and creates no demo
     * data, so running it here is safe for every environment, including one
     * that already has these roles from a prior manual seed.
     */
    public function up(): void
    {
        (new RoleAndPermissionSeeder)->run();
    }

    /**
     * Intentionally a no-op: role templates may already be referenced by
     * `warehouse_memberships`/`model_has_roles` rows, and deleting them on
     * rollback would strip access from real tenants rather than reverting a
     * schema change.
     */
    public function down(): void
    {
        //
    }
};
