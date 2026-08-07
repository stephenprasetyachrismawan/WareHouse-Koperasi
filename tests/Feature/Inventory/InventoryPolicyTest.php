<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Company;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\WarehouseMembership;

/**
 * Helper to create a user with specific permissions in a warehouse.
 */
function createUserWithPermissions(
    Warehouse $warehouse,
    array $permissions,
    string $role = 'staff_admin',
): array {
    $user = User::factory()->create(['status' => 'active']);
    $membership = WarehouseMembership::factory()->create([
        'company_id' => $warehouse->company_id,
        'warehouse_id' => $warehouse->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => 'active',
        'permissions' => array_map(
            fn ($p) => $p instanceof Permission ? $p->value : $p,
            $permissions
        ),
    ]);

    return compact('user', 'membership');
}

// =====================================================================
// ItemPolicy Tests
// =====================================================================

describe('ItemPolicy', function () {
    beforeEach(function () {
        $company = Company::factory()->create(['status' => 'active']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    });

    test('user with item.viewAny can view items in their warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemViewAny],
        );

        expect($user->can('viewAny', Item::class))->toBeTrue();
    });

    test('user without item.viewAny cannot view items', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [], // no permissions
        );

        expect($user->can('viewAny', Item::class))->toBeFalse();
    });

    test('user can view item in their warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemViewAny],
        );
        $item = Item::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $user->id,
        ]);

        expect($user->can('view', $item))->toBeTrue();
    });

    test('user CANNOT view item in different warehouse (tenant isolation)', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemViewAny],
        );
        $otherItem = Item::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('view', $otherItem))->toBeFalse();
    });

    test('user with item.create can create items', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemCreate],
        );

        expect($user->can('create', Item::class))->toBeTrue();
    });

    test('user without item.create cannot create items', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemViewAny],
        );

        expect($user->can('create', Item::class))->toBeFalse();
    });

    test('user with item.update can update item in their warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemUpdate],
        );
        $item = Item::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'created_by' => $user->id,
        ]);

        expect($user->can('update', $item))->toBeTrue();
    });

    test('user CANNOT update item in different warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::ItemUpdate],
        );
        $otherItem = Item::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('update', $otherItem))->toBeFalse();
    });

    test('user with inactive membership is denied', function () {
        $user = User::factory()->create(['status' => 'active']);
        WarehouseMembership::factory()->create([
            'company_id' => $this->warehouse->company_id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $user->id,
            'role' => 'staff_admin',
            'status' => 'suspended',
            'permissions' => [Permission::ItemViewAny->value],
        ]);

        expect($user->can('viewAny', Item::class))->toBeFalse();
    });
});

// =====================================================================
// SupplierPolicy Tests
// =====================================================================

describe('SupplierPolicy', function () {
    beforeEach(function () {
        $company = Company::factory()->create(['status' => 'active']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    });

    test('user with supplier.viewAny can view suppliers', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::SupplierViewAny],
        );

        expect($user->can('viewAny', Supplier::class))->toBeTrue();
    });

    test('user CANNOT view supplier in different warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::SupplierViewAny],
        );
        $otherSupplier = Supplier::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('view', $otherSupplier))->toBeFalse();
    });

    test('user with supplier.manage can create suppliers', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::SupplierManage],
        );

        expect($user->can('create', Supplier::class))->toBeTrue();
    });
});

// =====================================================================
// WarehouseLocationPolicy Tests
// =====================================================================

describe('WarehouseLocationPolicy', function () {
    beforeEach(function () {
        $company = Company::factory()->create(['status' => 'active']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    });

    test('user with location.viewAny can view locations', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::LocationViewAny],
        );

        expect($user->can('viewAny', WarehouseLocation::class))->toBeTrue();
    });

    test('user CANNOT view location in different warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::LocationViewAny],
        );
        $otherLocation = WarehouseLocation::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('view', $otherLocation))->toBeFalse();
    });

    test('user with location.manage can create locations', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::LocationManage],
        );

        expect($user->can('create', WarehouseLocation::class))->toBeTrue();
    });
});
// =====================================================================
// StockBalancePolicy Tests
// =====================================================================

describe('StockBalancePolicy', function () {
    beforeEach(function () {
        $company = Company::factory()->create(['status' => 'active']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    });

    test('user with stock.view can view stock balances', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockView],
        );

        expect($user->can('viewAny', StockBalance::class))->toBeTrue();
    });

    test('user CANNOT view stock balance in different warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockView],
        );
        $otherBalance = StockBalance::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('view', $otherBalance))->toBeFalse();
    });

    test('user with stock.adjust can adjust stock', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockAdjust],
        );

        expect($user->can('adjust', StockBalance::class))->toBeTrue();
    });
});

// =====================================================================
// StockTransactionPolicy Tests
// =====================================================================

describe('StockTransactionPolicy', function () {
    beforeEach(function () {
        $company = Company::factory()->create(['status' => 'active']);
        $this->warehouse = Warehouse::factory()->create(['company_id' => $company->id]);
        $this->otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);
    });

    test('user with stock.ledger.view can view transactions', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockLedgerView],
        );

        expect($user->can('viewAny', StockTransaction::class))->toBeTrue();
    });

    test('user CANNOT view transaction in different warehouse', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockLedgerView],
        );
        $otherTransaction = StockTransaction::factory()->create([
            'warehouse_id' => $this->otherWarehouse->id,
        ]);

        expect($user->can('view', $otherTransaction))->toBeFalse();
    });

    test('user with stock.scanIn can record stock in', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [Permission::StockScanIn],
        );

        expect($user->can('recordIn', StockTransaction::class))->toBeTrue();
    });

    test('user without stock.scanIn cannot record stock in', function () {
        ['user' => $user] = createUserWithPermissions(
            $this->warehouse,
            [],
        );

        expect($user->can('recordIn', StockTransaction::class))->toBeFalse();
    });
});
