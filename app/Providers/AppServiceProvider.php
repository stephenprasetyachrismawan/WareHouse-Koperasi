<?php

namespace App\Providers;

use App\Domain\Pickup\Events\StockShortageDetected;
use App\Enums\WarehouseRole;
use App\Http\Middleware\EnsureTenantContext;
use App\Listeners\Procurement\CreatePurchaseRequestForStockShortage;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMembership;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

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

        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::define('manage-warehouse-users', function (User $user, Warehouse $warehouse) {
            if (! $user->isActive()) {
                return false;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            $membership = WarehouseMembership::where('user_id', $user->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'active')
                ->first();

            if (! $membership) {
                return false;
            }

            return $membership->role === 'app_admin' || $membership->role === WarehouseRole::AppAdmin->value;
        });

        Event::listen(
            StockShortageDetected::class,
            CreatePurchaseRequestForStockShortage::class
        );

        // Without this, EnsureTenantContext (and its setPermissionsTeamId() call)
        // only runs on the initial page load. Livewire's AJAX update endpoint has
        // its own route outside the web.php group, so every wire:click-triggered
        // action would otherwise lose team-scoped permission context and fail
        // authorization checks that passed fine on the page that rendered them.
        Livewire::addPersistentMiddleware([
            EnsureTenantContext::class,
        ]);
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
