<div class="space-y-6" wire:poll.60s>
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">{{ __('Dashboard Purchasing') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $warehouse?->name }}</p>
        </div>
        <x-dashboard.freshness :updated-at="$updatedAt" :warehouse="$warehouse" />
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @if (isset($attention['approvedAwaitingProcurementCount']))
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Request Disetujui Belum Diproses'),
            value: $attention['approvedAwaitingProcurementCount'],
            route: route('procurement.approved-queue'),
            severity: $attention['approvedAwaitingProcurementCount'] > 0 ? 'info' : 'neutral',
            emptyStateText: __('Tidak ada request yang menunggu diproses.'),
        )" />
        @endif
        @if (isset($attention['groupingCandidateCount']))
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('Kandidat Grouping'),
            value: $attention['groupingCandidateCount'],
            route: route('procurement.grouping'),
            severity: $attention['groupingCandidateCount'] > 0 ? 'info' : 'neutral',
            emptyStateText: __('Tidak ada kandidat grouping saat ini.'),
        )" />
        @endif
        @if (isset($attention['draftPoCount']))
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('PO Draft'),
            value: $attention['draftPoCount'],
            route: route('procurement.purchase-orders.index'),
            severity: 'neutral',
            emptyStateText: __('Tidak ada PO draft.'),
        )" />
        @endif
        @if (isset($attention['sentPoAwaitingReceiptCount']))
        <x-dashboard.metric-card :metric="new \App\Domain\Dashboard\ValueObjects\DashboardMetric(
            label: __('PO Dikirim, Menunggu Barang'),
            value: $attention['sentPoAwaitingReceiptCount'],
            route: route('procurement.receipts.index'),
            severity: $attention['sentPoAwaitingReceiptCount'] > 0 ? 'warning' : 'neutral',
            emptyStateText: __('Tidak ada PO yang menunggu penerimaan barang.'),
        )" />
        @endif
    </div>

    <div>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                {{ __('Penerimaan Terbaru') }}
            </h3>
            <a href="{{ route('procurement.receipts.index') }}" wire:navigate class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                {{ __('Lihat Semua') }} &rarr;
            </a>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm overflow-hidden">
            @forelse ($recentGoodsReceipts as $receipt)
                <a href="{{ route('procurement.receipts.show', $receipt->uuid) }}" wire:navigate
                   class="flex items-center justify-between px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 {{ ! $loop->last ? 'border-b border-zinc-100 dark:border-zinc-700' : '' }}">
                    <div>
                        <p class="text-sm font-medium">{{ $receipt->receipt_number }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $receipt->purchaseOrder?->po_number }} &middot; {{ $receipt->purchaseOrder?->supplier?->name }}
                        </p>
                    </div>
                    <span class="text-xs text-zinc-400">{{ $receipt->received_at?->diffForHumans() }}</span>
                </a>
            @empty
                <div class="p-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Belum ada penerimaan barang.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
