<?php

namespace App\Actions\Fortify;

use App\Concerns\CompanyValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use CompanyValidationRules, PasswordValidationRules;

    /**
     * Create a new SaaS company, primary warehouse, user, and membership atomically.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'company_name' => $this->companyNameRules(),
            'company_code' => ['nullable', 'string', 'max:50'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $companyCode = ! empty($input['company_code'])
                ? Str::upper(Str::slug($input['company_code']))
                : Str::upper(Str::slug($input['company_name']).'-'.rand(100, 999));

            /** @var Company $company */
            $company = Company::create([
                'name' => $input['company_name'],
                'code' => $companyCode,
                'status' => 'active',
            ]);

            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'name' => 'Gudang '.$input['company_name'],
                'code' => 'WH-MAIN-'.rand(100, 999),
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ]);

            /** @var User $user */
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'status' => 'active',
                'is_super_admin' => false,
            ]);

            WarehouseMembership::create([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $user->id,
                'role' => 'app_admin',
                'status' => 'active',
            ]);

            setPermissionsTeamId($company->id);
            $user->assignRole('app_admin');

            return $user;
        });
    }
}
