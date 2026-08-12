<?php

namespace App\Livewire\Dashboard;

use App\Domain\Dashboard\Queries\AppAdminDashboardQuery;
use App\Domain\Dashboard\Queries\CooperativeDashboardQuery;
use App\Domain\Dashboard\Queries\HeadDashboardQuery;
use App\Domain\Dashboard\Queries\PlatformDashboardQuery;
use App\Domain\Dashboard\Queries\PurchasingDashboardQuery;
use App\Domain\Dashboard\Queries\StaffDashboardQuery;
use App\Enums\Permission;
use App\Enums\WarehouseRole;
use App\Models\WarehouseMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The single, stable /dashboard entry point. Resolves which role dashboard
 * to render from the actor's active membership — never from a client-
 * supplied role/warehouse parameter. Each role gets a dedicated Query
 * Object and view rather than one widget engine trying to be generic for
 * every actor at once.
 */
class Index extends Component
{
    #[On('inbox-notification-received')]
    public function refreshFromInboxSignal(): void
    {
        // The next render re-queries the authorized read model. No KPI payload
        // is broadcast to the browser; the persistent Inbox remains the source
        // of truth and polling covers a disconnected Reverb connection.
    }

    public function render(): View
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            return view('livewire.dashboard.platform', app(PlatformDashboardQuery::class)->execute());
        }

        $membership = $user->activeMembership();

        // EnsureTenantContext middleware already blocks a non-super-admin
        // user with no active membership from reaching this route — this
        // is defense in depth, not the primary control.
        abort_if($membership === null, 403);
        abort_unless($membership->hasPermission(Permission::DashboardView), 403);

        return $this->renderForMembership($membership);
    }

    private function renderForMembership(WarehouseMembership $membership): View
    {
        $roleEnum = $membership->role instanceof WarehouseRole
            ? $membership->role
            : WarehouseRole::tryFrom((string) $membership->role);

        return match ($roleEnum) {
            WarehouseRole::AppAdmin => view('livewire.dashboard.app-admin', app(AppAdminDashboardQuery::class)->execute($membership)),
            WarehouseRole::KepalaGudang => view('livewire.dashboard.head', app(HeadDashboardQuery::class)->execute($membership)),
            WarehouseRole::StaffAdmin => view('livewire.dashboard.staff', app(StaffDashboardQuery::class)->execute($membership)),
            WarehouseRole::Purchasing => view('livewire.dashboard.purchasing', app(PurchasingDashboardQuery::class)->execute($membership)),
            WarehouseRole::Koperasi => view('livewire.dashboard.cooperative', app(CooperativeDashboardQuery::class)->execute($membership)),
            default => abort(403),
        };
    }
}
