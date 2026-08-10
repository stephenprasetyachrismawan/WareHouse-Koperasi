<?php

use App\Actions\Pickup\ApprovePickupRequestAction;
use App\Domain\Pickup\Events\PickupRequestApproved;
use App\Enums\ApprovalStatus;
use App\Enums\Permission;
use App\Enums\PickupRequestStatus;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('approves a pickup request', function () {
    Event::fake([PickupRequestApproved::class]);

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

    $action = app(ApprovePickupRequestAction::class);
    $result = $action->execute($user, $pickupRequest);

    expect($result->status)->toBe(PickupRequestStatus::Approved)
        ->and($result->approved_at)->not->toBeNull();

    $approval = $result->approvals()->first();
    expect($approval->status)->toBe(ApprovalStatus::Approved)
        ->and($approval->approver_id)->toBe($user->id);

    Event::assertDispatched(PickupRequestApproved::class);
});

it('fails to approve if unauthorized', function () {
    $user = User::factory()->create();
    $pickupRequest = PickupRequest::factory()->create([
        'status' => PickupRequestStatus::WaitingApproval,
    ]);

    $action = app(ApprovePickupRequestAction::class);

    $this->expectException(AuthorizationException::class);
    $action->execute($user, $pickupRequest);
});
