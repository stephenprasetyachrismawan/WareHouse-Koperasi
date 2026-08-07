<?php

namespace App\Actions\UserManagement;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateCompanyUserAction
{
    use ProfileValidationRules;

    /**
     * Allowed internal roles for company user management.
     */
    public const ALLOWED_ROLES = [
        'app_admin',
        'kepala_gudang',
        'staff_admin',
        'purchasing',
        'koperasi',
    ];

    /**
     * Update an internal user's details and role within the actor's company.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, User $targetUser, array $data): User
    {
        Gate::forUser($actor)->authorize('update', $targetUser);

        $company = $actor->activeCompany();

        if (! $company) {
            abort(403, 'Actor has no active company context.');
        }

        Validator::make($data, [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($targetUser->id),
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
        ])->validate();

        return DB::transaction(function () use ($company, $targetUser, $data) {
            $targetUser->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            $membership = WarehouseMembership::where('user_id', $targetUser->id)
                ->where('company_id', $company->id)
                ->first();

            if ($membership) {
                $oldRole = $membership->role;
                $membership->update([
                    'role' => $data['role'],
                ]);

                setPermissionsTeamId($company->id);
                if ($oldRole) {
                    $targetUser->removeRole($oldRole);
                }
                $targetUser->assignRole($data['role']);
            }

            return $targetUser;
        });
    }
}
