<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Dashboard Administrasi') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse?->name }}</p>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" :warehouse="$warehouse" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Pengguna Aktif'),
            value: $activeUserCount,
            severity: 'info',
            emptyStateText: __('Belum ada pengguna aktif.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Membership Aktif'),
            value: $activeMembershipCount,
            severity: 'info',
            emptyStateText: __('Belum ada membership aktif.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Pengguna Suspended'),
            value: $suspendedUserCount,
            severity: $suspendedUserCount > 0 ? 'warning' : 'neutral',
            emptyStateText: __('Tidak ada pengguna suspended.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Role Terdaftar'),
            value: $roleDistribution->count(),
            severity: 'neutral',
            emptyStateText: __('Belum ada role aktif.'),
        )" />
    </div>

    <section aria-labelledby="role-distribution-heading">
        <h3 id="role-distribution-heading" class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Distribusi Role') }}</h3>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($roleDistribution as $role => $total)
                <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ \App\Enums\WarehouseRole::tryFrom((string) $role)?->label() ?? $role }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ $total }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if ($operational !== null)
        <section aria-labelledby="limited-operations-heading">
            <h3 id="limited-operations-heading" class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Operasional Terbatas') }}</h3>
            <div class="grid grid-cols-2 gap-4">
                <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                    label: __('Stok Kritis'),
                    value: $operational['criticalStockCount'],
                    route: route('inventory.stock.overview'),
                    severity: $operational['criticalStockCount'] > 0 ? 'warning' : 'neutral',
                    emptyStateText: __('Tidak ada stok kritis.'),
                )" />
                @if ($operational['inProgressPurchaseRequestCount'] !== null)
                    <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                        label: __('Purchase Request Operasional'),
                        value: $operational['inProgressPurchaseRequestCount'],
                        route: route('procurement.index'),
                        severity: 'info',
                        emptyStateText: __('Tidak ada Purchase Request operasional.'),
                    )" />
                @endif
            </div>
        </section>
    @endif
</div>
