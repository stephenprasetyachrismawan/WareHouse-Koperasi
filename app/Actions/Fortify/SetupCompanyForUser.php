<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetupCompanyForUser
{
    /**
     * Create a SaaS company, primary warehouse, and app_admin membership for
     * an existing user, atomically. Used to complete Google sign-in setup.
     *
     * @return array{company: Company, warehouse: Warehouse}
     */
    public function create(User $user, string $companyName, ?string $companyCode = null): array
    {
        return DB::transaction(function () use ($user, $companyName, $companyCode) {
            $companyCode = ! empty($companyCode)
                ? Str::upper(Str::slug($companyCode))
                : Str::upper(Str::slug($companyName).'-'.rand(100, 999));

            /** @var Company $company */
            $company = Company::create([
                'name' => $companyName,
                'code' => $companyCode,
                'status' => 'active',
            ]);

            /** @var Warehouse $warehouse */
            $warehouse = Warehouse::create([
                'company_id' => $company->id,
                'name' => 'Gudang '.$companyName,
                'code' => 'WH-MAIN-'.rand(100, 999),
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
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

            return ['company' => $company, 'warehouse' => $warehouse];
        });
    }
}
