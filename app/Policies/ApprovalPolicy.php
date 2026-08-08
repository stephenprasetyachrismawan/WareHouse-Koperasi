<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    private function isSameWarehouse(User $user, Approval $approval): bool
    {
        return $user->activeMembership()?->warehouse_id === $approval->warehouse_id;
    }

    public function viewAny(User $user): bool
    {
        // Anyone with an active membership can theoretically view approvals for their warehouse
        // but typically it's scoped by the resource it belongs to.
        return $user->activeMembership() !== null;
    }

    public function view(User $user, Approval $approval): bool
    {
        return $this->viewAny($user) && $this->isSameWarehouse($user, $approval);
    }
}
