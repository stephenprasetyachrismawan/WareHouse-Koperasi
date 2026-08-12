<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    public function download(User $user, ReportExport $export): bool
    {
        if ($export->user_id !== $user->id || $user->isSuperAdmin()) {
            return false;
        }

        $membership = $user->activeMembership();
        if ($membership === null || $membership->warehouse_id !== $export->warehouse_id) {
            return false;
        }

        $role = $membership->role instanceof WarehouseRole ? $membership->role : WarehouseRole::tryFrom((string) $membership->role);

        return $role === WarehouseRole::AppAdmin
            ? in_array(Permission::ReportsExport->value, $membership->permissions ?? [], true)
            : $membership->hasPermission(Permission::ReportsExport);
    }
}
