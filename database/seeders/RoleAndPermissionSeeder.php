<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        setPermissionsTeamId(null);

        // 1. Permissions List
        $permissions = array_unique(array_merge([
            // Company & Platform
            'company.view',
            'company.update',
            'company.manage',

            // User Management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_role',
            'users.toggle_status',

            // Warehouses
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.manage',

            // Audit
            'audit.view',
        ], \App\Enums\Permission::values()));

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // 2. Roles & Default Permission Assignments
        $rolesWithPermissions = [
            'super_admin' => $permissions,

            'app_admin' => [
                'company.view',
                'company.update',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.assign_role',
                'users.toggle_status',
                'warehouses.view',
                'warehouses.update',
                'audit.view',
            ],

            'kepala_gudang' => [
                'items.view',
                'stock.view',
                'purchase_request.viewAny',
                'purchase_request.view',
                'purchase_request.approve',
                'purchase_request.reject',
                'purchase_request.cancel',
                'purchase_order.viewAny',
                'purchase_order.view',
                'purchase_group.viewAny',
                'pickup_request.viewAny',
                'pickup_request.approve',
                'returns.view',
                'returns.approve',
                'predictions.view',
                'predictions.run',
                'audit.view',
            ],

            'staff_admin' => [
                'items.view',
                'items.create',
                'items.update',
                'stock.view',
                'stock.adjust',
                'stock.scan_in',
                'stock.scan_out',
                'purchase_request.viewAny',
                'purchase_request.view',
                'purchase_request.create',
                'purchase_request.requestCancellation',
                'receipts.viewAny',
                'receipts.view',
                'receipts.qc',
                'pickup_request.viewAny',
                'pickup_request.prepare',
                'pickup_request.fulfill',
                'pickup_request.cancel',
                'returns.view',
                'returns.verify',
            ],

            'purchasing' => [
                'items.view',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'purchase_request.viewAny',
                'purchase_request.view',
                'purchase_order.viewAny',
                'purchase_order.view',
                'purchase_order.create',
                'purchase_order.send',
                'purchase_group.viewAny',
                'purchase_group.create',
                'purchase_group.update',
                'receipts.viewAny',
                'receipts.view',
                'receipts.create',
            ],

            'koperasi' => [
                'pickup_request.viewAny',
                'pickup_request.create',
                'pickup_request.cancel',
                'returns.view',
                'returns.create',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $assignedPermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $permissionModels = Permission::whereIn('name', $assignedPermissions)->get();
            $role->syncPermissions($permissionModels);
        }
    }
}
