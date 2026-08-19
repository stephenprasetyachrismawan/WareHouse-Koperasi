<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WarehouseRole;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ValidateIamBootstrapCommand extends Command
{
    protected $signature = 'ops:validate-iam';

    protected $description = 'Validate that core IAM role/permission templates are bootstrapped, without mutating anything';

    public function handle(): int
    {
        $guard = 'web';
        $requiredRoles = [
            'super_admin',
            ...array_map(fn (WarehouseRole $role) => $role->value, WarehouseRole::cases()),
        ];

        $existingRoles = Role::query()->where('guard_name', $guard)->pluck('name')->all();
        $missingRoles = array_values(array_diff($requiredRoles, $existingRoles));

        $this->line('GUARD='.$guard);
        foreach ($requiredRoles as $roleName) {
            $status = in_array($roleName, $existingRoles, true) ? 'PASS' : 'FAIL';
            $this->line("ROLE[{$roleName}]={$status}");
        }

        $permissionCount = Permission::query()->where('guard_name', $guard)->count();
        $this->line('PERMISSIONS_SEEDED='.($permissionCount > 0 ? 'PASS' : 'FAIL').' (count='.$permissionCount.')');

        if ($missingRoles !== [] || $permissionCount === 0) {
            $this->error('IAM bootstrap is incomplete. Run `php artisan migrate` (the core IAM migration seeds required roles/permissions).');

            return self::FAILURE;
        }

        $this->info('IAM bootstrap is present.');

        return self::SUCCESS;
    }
}
