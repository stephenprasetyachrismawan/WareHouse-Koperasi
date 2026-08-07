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
        $permissions = [
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

            // Catalog & Suppliers
            'items.view',
            'items.create',
            'items.update',
            'items.archive',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',

            // Stock & Inventory
            'stock.view',
            'stock.adjust',
            'stock.scan_in',
            'stock.scan_out',
            'stock.reconcile',

            // Purchase Requests
            'purchase_requests.view',
            'purchase_requests.create',
            'purchase_requests.approve',
            'purchase_requests.reject',
            'purchase_requests.cancel',

            // Purchase Orders
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.send',

            // Receipts & QC
            'receipts.view',
            'receipts.create',
            'receipts.qc',

            // Pickup Requests
            'pickup_requests.view',
            'pickup_requests.create',
            'pickup_requests.prepare',
            'pickup_requests.approve',

            // Returns
            'returns.view',
            'returns.create',
            'returns.verify',
            'returns.approve',

            // Machine Learning / Predictions
            'predictions.view',
            'predictions.run',

            // Audit
            'audit.view',
        ];

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
                'purchase_requests.view',
                'purchase_requests.create',
                'purchase_requests.approve',
                'purchase_requests.reject',
                'purchase_requests.cancel',
                'pickup_requests.view',
                'pickup_requests.approve',
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
                'purchase_requests.view',
                'purchase_requests.create',
                'receipts.view',
                'receipts.create',
                'receipts.qc',
                'pickup_requests.view',
                'pickup_requests.prepare',
                'returns.view',
                'returns.verify',
            ],

            'purchasing' => [
                'items.view',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'purchase_requests.view',
                'purchase_orders.view',
                'purchase_orders.create',
                'purchase_orders.send',
                'receipts.view',
                'receipts.create',
            ],

            'koperasi' => [
                'pickup_requests.view',
                'pickup_requests.create',
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
