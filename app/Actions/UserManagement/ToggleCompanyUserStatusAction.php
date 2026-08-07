<?php

namespace App\Actions\UserManagement;

use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ToggleCompanyUserStatusAction
{
    /**
     * Toggle status (active/suspended) of a company user.
     */
    public function execute(User $actor, User $target): User
    {
        Gate::forUser($actor)->authorize('toggleStatus', $target);

        $company = $actor->activeCompany();

        if (! $company) {
            abort(403, 'Actor has no active company context.');
        }

        $membership = WarehouseMembership::where('user_id', $target->id)
            ->where('company_id', $company->id)
            ->first();

        if (! $membership) {
            abort(403, 'Target user does not belong to actor company.');
        }

        $newStatus = $membership->status === 'active' ? 'suspended' : 'active';

        // Check if suspending the last active app_admin
        if ($newStatus === 'suspended' && $membership->role === 'app_admin') {
            $activeAdminCount = WarehouseMembership::where('company_id', $company->id)
                ->where('role', 'app_admin')
                ->where('status', 'active')
                ->count();

            if ($activeAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'status' => ['Tidak dapat menonaktifkan Super App Admin / Admin terakhir pada Perusahaan ini.'],
                ]);
            }
        }

        return DB::transaction(function () use ($target, $membership, $newStatus) {
            $membership->update(['status' => $newStatus]);
            $target->update(['status' => $newStatus]);

            return $target;
        });
    }
}
