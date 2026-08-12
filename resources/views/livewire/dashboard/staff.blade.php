<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Dashboard Staff Admin') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse?->name }}</p>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" :warehouse="$warehouse" />
    </div>

    @if ($pickupTasks !== null)
    <div>
        <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">{{ __('Tugas Pengambilan') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Request Baru'),
                value: $pickupTasks['new'],
                route: route('pickup.fulfilment'),
                severity: $pickupTasks['new'] > 0 ? 'info' : 'neutral',
                emptyStateText: __('Tidak ada request pengambilan baru.'),
            )" />
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Menunggu Stok (Backorder)'),
                value: $pickupTasks['backordered'],
                route: route('pickup.fulfilment'),
                severity: $pickupTasks['backordered'] > 0 ? 'warning' : 'neutral',
                emptyStateText: __('Tidak ada pengambilan yang menunggu stok.'),
            )" />
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Perlu Disiapkan'),
                value: $pickupTasks['toPrepare'],
                route: route('pickup.fulfilment'),
                severity: $pickupTasks['toPrepare'] > 0 ? 'info' : 'neutral',
                emptyStateText: __('Tidak ada barang yang perlu disiapkan.'),
            )" />
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Siap Diserahkan'),
                value: $pickupTasks['readyForFulfilment'],
                route: route('pickup.fulfilment'),
                severity: 'neutral',
                emptyStateText: __('Tidak ada pengambilan yang siap diserahkan.'),
            )" />
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @if ($qcPendingCount !== null)
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('QC Menunggu'),
            value: $qcPendingCount,
            route: route('procurement.qc-queue'),
            severity: $qcPendingCount > 0 ? 'warning' : 'neutral',
            emptyStateText: __('Tidak ada barang yang menunggu QC.'),
        )" />
        @endif
        @if ($returnVerificationCount !== null)
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Retur Perlu Diverifikasi'),
            value: $returnVerificationCount,
            route: route('returns.verification-queue'),
            severity: $returnVerificationCount > 0 ? 'warning' : 'neutral',
            emptyStateText: __('Tidak ada retur yang perlu diverifikasi.'),
        )" />
        @endif
        @if ($criticalStockCount !== null)
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Stok Kritis'),
            value: $criticalStockCount,
            route: route('inventory.stock.overview'),
            severity: $criticalStockCount > 0 ? 'critical' : 'neutral',
            emptyStateText: __('Tidak ada stok kritis.'),
        )" />
        @endif
    </div>

    @if ($inProgressByItem !== null)
    <div>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('Purchase Request Berjalan per Barang') }}
            </h3>
            <a href="{{ route('procurement.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                {{ __('Lihat Semua') }} &rarr;
            </a>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm overflow-hidden">
            @forelse ($inProgressByItem as $row)
                <div class="flex items-center justify-between px-4 py-3 {{ ! $loop->last ? 'border-b border-zinc-100 dark:border-zinc-700' : '' }}">
                    <span class="text-sm font-medium">{{ $row->item_name }}</span>
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $row->total_quantity }} unit</span>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Tidak ada Purchase Request yang sedang berjalan.') }}
                </div>
            @endforelse
        </div>
    </div>
    @endif
</div>
