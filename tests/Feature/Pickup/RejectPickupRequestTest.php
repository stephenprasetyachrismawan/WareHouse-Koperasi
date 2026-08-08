<?php

use App\Actions\Pickup\RejectPickupRequestAction;
use App\Domain\Pickup\Events\PickupRequestRejected;
use App\Enums\ApprovalStatus;
use App\Enums\Permission;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('rejects a pickup request', function () {
    Event::fake([PickupRequestRejected::class]);

    $user = User::factory()->create();
    $pickupRequest = PickupRequest::factory()->create([
        'status' => PickupRequestStatus::WaitingApproval,
    ]);

    setPermissionsTeamId($pickupRequest->warehouse->company_id);

    $role = Role::firstOrCreate(['name' => 'kepala_gudang', 'company_id' => $pickupRequest->warehouse->company_id]);
    $permission = SpatiePermission::firstOrCreate(['name' => Permission::PickupRequestApprove->value]);
    $role->givePermissionTo($permission);

    WarehouseMembership::factory()->create([
        'user_id' => $user->id,
        'warehouse_id' => $pickupRequest->warehouse_id,
        'company_id' => $pickupRequest->warehouse->company_id,
        'role' => 'kepala_gudang',
        'status' => 'active',
    ]);

    $user->assignRole($role);

    $action = app(RejectPickupRequestAction::class);
    $result = $action->execute($user, $pickupRequest, 'Stock unavailable');

    expect($result->status)->toBe(PickupRequestStatus::Rejected);

    $approval = $result->approvals()->first();
    expect($approval->status)->toBe(ApprovalStatus::Rejected)
        ->and($approval->approver_id)->toBe($user->id)
        ->and($approval->reason)->toBe('Stock unavailable');

    Event::assertDispatched(PickupRequestRejected::class);
});

it('fails to reject if reason is empty', function () {
    $user = User::factory()->create();
    $pickupRequest = PickupRequest::factory()->create([
        'status' => PickupRequestStatus::WaitingApproval,
    ]);

    setPermissionsTeamId($pickupRequest->warehouse->company_id);

    $role = Role::firstOrCreate(['name' => 'kepala_gudang', 'company_id' => $pickupRequest->warehouse->company_id]);
    $permission = SpatiePermission::firstOrCreate(['name' => Permission::PickupRequestApprove->value]);
    $role->givePermissionTo($permission);

    WarehouseMembership::factory()->create([
        'user_id' => $user->id,
        'warehouse_id' => $pickupRequest->warehouse_id,
        'company_id' => $pickupRequest->warehouse->company_id,
        'role' => 'kepala_gudang',
        'status' => 'active',
    ]);

    $user->assignRole($role);

    $action = app(RejectPickupRequestAction::class);

    $this->expectException(InvalidArgumentException::class);
    $action->execute($user, $pickupRequest, '  ');
});
