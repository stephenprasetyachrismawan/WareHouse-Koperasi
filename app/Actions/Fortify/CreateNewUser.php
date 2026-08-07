<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Spatie\Permission\Models\Role;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user with their company/tenant context.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'company_name' => ['required', 'string', 'max:255'],
            'company_code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('companies', 'code')],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:30'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $companyCode = ! empty($input['company_code'])
                ? Str::upper($input['company_code'])
                : Str::upper(Str::slug($input['company_name']).'-'.Str::random(4));

            $originalCode = $companyCode;
            $counter = 1;
            while (Company::where('code', $companyCode)->exists()) {
                $companyCode = $originalCode.'-'.$counter++;
            }

            // 1. Create Company
            $company = Company::create([
                'name' => $input['company_name'],
                'code' => $companyCode,
                'address' => $input['company_address'] ?? null,
                'phone' => $input['company_phone'] ?? null,
                'status' => 'active',
            ]);

            // 2. Create Default Warehouse
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'name' => 'Gudang Utama '.$company->name,
                'code' => 'WH-MAIN',
                'status' => 'active',
            ]);

            // 3. Create User
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'status' => 'active',
                'is_super_admin' => false,
            ]);

            // 4. Create WarehouseMembership (Role: app_admin)
            WarehouseMembership::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'role' => 'app_admin',
                'status' => 'active',
            ]);

            // 5. Assign Spatie Team-scoped Role
            setPermissionsTeamId($company->id);
            $role = Role::findOrCreate('app_admin', 'web');
            $user->assignRole($role);

            return $user;
        });
    }
}
