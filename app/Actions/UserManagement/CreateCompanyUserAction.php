<?php

namespace App\Actions\UserManagement;

use App\Models\User;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateCompanyUserAction
{
    /**
     * Allowed internal roles for company user creation.
     */
    public const ALLOWED_ROLES = [
        'app_admin',
        'kepala_gudang',
        'staff_admin',
        'purchasing',
        'koperasi',
    ];

    /**
     * Create an internal user within the actor's company.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, array $data): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $company = $actor->activeCompany();

        if (! $company) {
            abort(403, 'Actor has no active company context.');
        }

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(self::ALLOWED_ROLES)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $company->id)],
        ])->validate();

        $defaultWarehouse = $company->warehouses()->first();
        $activeWarehouse = $actor->activeWarehouse();
        $warehouseId = $data['warehouse_id'] ?? ($activeWarehouse ? $activeWarehouse->id : ($defaultWarehouse ? $defaultWarehouse->id : null));

        if (! $warehouseId) {
            abort(422, 'Company has no warehouse configured.');
        }

        return DB::transaction(function () use ($company, $warehouseId, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
                'is_super_admin' => false,
            ]);

            WarehouseMembership::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                'role' => $data['role'],
                'status' => 'active',
            ]);

            setPermissionsTeamId($company->id);
            $user->assignRole($data['role']);

            return $user;
        });
    }
}
