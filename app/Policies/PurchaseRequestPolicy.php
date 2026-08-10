<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\PurchaseRequest;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PurchaseRequestPolicy
{
    private function hasPermission(User $user, string $permission): bool
    {
        if ($user->activeMembership() === null) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseRequestViewAny->value);
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestView->value)) {
            return false;
        }

        return $user->activeWarehouse()?->id === $purchaseRequest->warehouse_id;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, Permission::PurchaseRequestCreate->value);
    }

    public function approve(User $user, mixed $purchaseRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestApprove->value)) {
            return false;
        }

        if ($purchaseRequest instanceof PurchaseRequest) {
            return $user->activeWarehouse()?->id === $purchaseRequest->warehouse_id;
        }

        return true;
    }

    public function reject(User $user, mixed $purchaseRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestReject->value)) {
            return false;
        }

        if ($purchaseRequest instanceof PurchaseRequest) {
            return $user->activeWarehouse()?->id === $purchaseRequest->warehouse_id;
        }

        return true;
    }

    public function cancel(User $user, mixed $purchaseRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestCancel->value)) {
            return false;
        }

        if ($purchaseRequest instanceof PurchaseRequest) {
            return $user->activeWarehouse()?->id === $purchaseRequest->warehouse_id;
        }

        return true;
    }

    public function requestCancellation(User $user, mixed $purchaseRequest = null): bool
    {
        if (! $this->hasPermission($user, Permission::PurchaseRequestRequestCancellation->value)) {
            return false;
        }

        if ($purchaseRequest instanceof PurchaseRequest) {
            return $user->activeWarehouse()?->id === $purchaseRequest->warehouse_id;
        }

        return true;
    }
}
