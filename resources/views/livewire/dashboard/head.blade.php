<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Dashboard Kepala Gudang') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse?->name }}</p>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" :warehouse="$warehouse" />
    </div>

    <div>
        <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">{{ __('Menunggu Persetujuan Anda') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @if (isset($pendingApprovals['purchaseRequests']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Purchase Request'),
                value: $pendingApprovals['purchaseRequests'],
                route: route('procurement.approval-inbox'),
                severity: $pendingApprovals['purchaseRequests'] > 0 ? 'critical' : 'neutral',
                emptyStateText: __('Tidak ada Purchase Request yang menunggu persetujuan.'),
            )" />
            @endif
            @if (isset($pendingApprovals['pickupRequests']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Pengambilan (Pickup)'),
                value: $pendingApprovals['pickupRequests'],
                route: route('pickup.approval-inbox'),
                severity: $pendingApprovals['pickupRequests'] > 0 ? 'critical' : 'neutral',
                emptyStateText: __('Tidak ada pengambilan yang menunggu persetujuan.'),
            )" />
            @endif
            @if (isset($pendingApprovals['returns']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Retur'),
                value: $pendingApprovals['returns'],
                route: route('returns.approval-queue'),
                severity: $pendingApprovals['returns'] > 0 ? 'critical' : 'neutral',
                emptyStateText: __('Tidak ada retur yang menunggu keputusan.'),
            )" />
            @endif
            @if (isset($pendingApprovals['cancellations']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Pembatalan'),
                value: $pendingApprovals['cancellations'],
                route: route('procurement.approval-inbox'),
                severity: $pendingApprovals['cancellations'] > 0 ? 'critical' : 'neutral',
                emptyStateText: __('Tidak ada permintaan pembatalan.'),
            )" />
            @endif
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">{{ __('Status Operasional') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @if ($criticalStockCount !== null)
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Stok Kritis'),
                value: $criticalStockCount,
                route: route('inventory.stock.overview'),
                severity: $criticalStockCount > 0 ? 'critical' : 'neutral',
                emptyStateText: __('Tidak ada stok kritis.'),
            )" />
            @endif
            @if ($backorderedPickupCount !== null)
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Pengambilan Menunggu Stok'),
                value: $backorderedPickupCount,
                route: route('pickup.fulfilment'),
                severity: $backorderedPickupCount > 0 ? 'warning' : 'neutral',
                emptyStateText: __('Tidak ada pengambilan yang menunggu stok.'),
            )" />
            @endif
            @if (isset($procurementAttention['inProgressCount']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Purchase Request Berjalan'),
                value: $procurementAttention['inProgressCount'],
                route: route('procurement.index'),
                severity: 'info',
                emptyStateText: __('Tidak ada Purchase Request yang berjalan.'),
            )" />
            @endif
            @if (isset($procurementAttention['poSentAwaitingReceiptCount']))
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('PO Dikirim, Menunggu Barang'),
                value: $procurementAttention['poSentAwaitingReceiptCount'],
                route: route('procurement.receipts.index'),
                severity: 'info',
                emptyStateText: __('Tidak ada PO yang menunggu penerimaan barang.'),
            )" />
            @endif
            @if ($replacementPendingCount !== null)
            <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
                label: __('Penggantian Retur Menunggu'),
                value: $replacementPendingCount,
                route: route('returns.replacement-tasks'),
                severity: $replacementPendingCount > 0 ? 'warning' : 'neutral',
                emptyStateText: __('Tidak ada penggantian retur yang menunggu.'),
            )" />
            @endif
        </div>
    </div>
</div>
