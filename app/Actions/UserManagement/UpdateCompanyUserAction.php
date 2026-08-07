<?php

namespace App\Actions\UserManagement;

use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateCompanyUserAction
{
    /**
     * Allowed internal roles.
     */
    public const ALLOWED_ROLES = [
        'app_admin',
        'kepala_gudang',
        'staff_admin',
        'purchasing',
        'koperasi',
    ];

    /**
     * Update an internal user within the actor's company.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, User $target, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $target);

        $company = $actor->activeCompany();

        if (! $company) {
            abort(403, 'Actor has no active company context.');
        }

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $company->id)],
        ])->validate();

        $membership = WarehouseMembership::where('user_id', $target->id)
            ->where('company_id', $company->id)
            ->first();

        if (! $membership) {
            abort(403, 'Target user does not belong to actor company.');
        }

        return DB::transaction(function () use ($company, $target, $membership, $data) {
            $target->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $warehouseId = $data['warehouse_id'] ?? $membership->warehouse_id;

            $oldRole = $membership->role;
            $newRole = $data['role'];

            $membership->update([
                'role' => $newRole,
                'warehouse_id' => $warehouseId,
            ]);

            setPermissionsTeamId($company->id);

            if ($oldRole !== $newRole) {
                $target->removeRole($oldRole);
                $target->assignRole($newRole);
            }

            return $target;
        });
    }
}
