<?php

use App\Enums\MembershipStatus;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\Gate;

test('app_admin can manage users in their own warehouse', function () {
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    WarehouseMembership::factory()
        ->for($user)
        ->for($warehouse)
        ->role(WarehouseRole::AppAdmin)
        ->create();

    expect(Gate::forUser($user)->allows('manage-warehouse-users', $warehouse))->toBeTrue();
});

test('app_admin cannot manage users in a warehouse they do not belong to', function () {
    $warehouse = Warehouse::factory()->create();
    $otherWarehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    WarehouseMembership::factory()
        ->for($user)
        ->for($warehouse)
        ->role(WarehouseRole::AppAdmin)
        ->create();

    expect(Gate::forUser($user)->allows('manage-warehouse-users', $otherWarehouse))->toBeFalse();
});

test('suspended app_admin membership loses access', function () {
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    WarehouseMembership::factory()
        ->for($user)
        ->for($warehouse)
        ->role(WarehouseRole::AppAdmin)
        ->suspended()
        ->create();

    expect(Gate::forUser($user)->allows('manage-warehouse-users', $warehouse))->toBeFalse();
});

test('kepala_gudang cannot manage users', function () {
    $warehouse = Warehouse::factory()->create();
    $user = User::factory()->create();
    WarehouseMembership::factory()
        ->for($user)
        ->for($warehouse)
        ->role(WarehouseRole::KepalaGudang)
        ->create();

    expect(Gate::forUser($user)->allows('manage-warehouse-users', $warehouse))->toBeFalse();
});

test('membership permissions narrow but never widen the role template', function () {
    $membership = WarehouseMembership::factory()->make([
        'role' => WarehouseRole::AppAdmin,
        'status' => MembershipStatus::Active,
        'permissions' => [Permission::UsersManage->value],
    ]);

    expect($membership->hasPermission(Permission::UsersManage))->toBeTrue()
        ->and($membership->hasPermission(Permission::RolesManage))->toBeFalse();
});

test('koperasi role defaults do not include internal management permissions', function () {
    expect(WarehouseRole::Koperasi->defaultPermissions())
        ->not->toContain(Permission::UsersManage)
        ->not->toContain(Permission::StockManage);
});
