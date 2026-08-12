<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Platform Dashboard') }}</h2>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" />
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Warehouse Aktif'),
            value: $activeWarehouseCount,
            severity: 'info',
            emptyStateText: __('Belum ada warehouse aktif.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Warehouse Suspended'),
            value: $suspendedWarehouseCount,
            severity: $suspendedWarehouseCount > 0 ? 'warning' : 'neutral',
            emptyStateText: __('Tidak ada warehouse suspended.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Coverage App Admin'),
            value: $appAdminCoverageCount,
            severity: 'info',
            emptyStateText: __('Belum ada coverage App Admin.'),
        )" />
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Pengguna Platform'),
            value: $platformUserCount,
            severity: 'neutral',
            emptyStateText: __('Belum ada pengguna platform.'),
        )" />
    </div>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Dashboard ini hanya menampilkan kesehatan platform. Detail operasional tenant tersedia melalui support access yang terkontrol.') }}
    </p>
</div>
