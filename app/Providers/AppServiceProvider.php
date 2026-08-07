<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
    }

    /**
     * Register tenant-scoped ACL gates. Every gate takes the target
     * `Warehouse` explicitly so a check always resolves against the caller's
     * membership in that specific tenant, never a global role.
     */
    protected function configureGates(): void
    {
        Gate::define('manage-warehouse-users', function (User $user, Warehouse $warehouse) {
            return $user->hasPermission($warehouse, Permission::UsersManage);
        });

        Gate::define('manage-warehouse-roles', function (User $user, Warehouse $warehouse) {
            return $user->hasPermission($warehouse, Permission::RolesManage);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
