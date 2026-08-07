<?php

namespace App\Actions\Fortify;

use App\Concerns\CompanyValidationRules;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\MembershipStatus;
use App\Enums\WarehouseRole;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use CompanyValidationRules, PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate the input and create the company (tenant) together with its
     * first user, who becomes the company's `app_admin`. Manual registration
     * is the only way a new company enters the system — every other internal
     * user is provisioned by that `app_admin` via invitation.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'company_name' => $this->companyNameRules(),
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $warehouse = Warehouse::create([
                'name' => $input['company_name'],
                'slug' => $this->uniqueSlug($input['company_name']),
            ]);

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            WarehouseMembership::create([
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'role' => WarehouseRole::AppAdmin,
                'status' => MembershipStatus::Active,
                'permissions' => null,
            ]);

            return $user;
        });
    }

    private function uniqueSlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        $slug = $base;
        $suffix = 1;

        while (Warehouse::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
