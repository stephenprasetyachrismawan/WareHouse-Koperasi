<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Queries;

use App\Enums\WarehouseRole;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformDashboardQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return [
            'updatedAt' => Carbon::now(),
            'activeWarehouseCount' => Warehouse::query()->where('status', 'active')->count(),
            'suspendedWarehouseCount' => Warehouse::query()->where('status', 'suspended')->count(),
            'appAdminCoverageCount' => WarehouseMembership::query()
                ->where('role', WarehouseRole::AppAdmin->value)
                ->where('status', 'active')
                ->distinct('warehouse_id')
                ->count('warehouse_id'),
            'platformUserCount' => DB::table('users')->count(),
        ];
    }
}
