<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Saldo & Pemantauan Stok') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Pantau jumlah saldo fisik, stok kritis, dan penyesuaian untuk ') }} <strong>{{ $warehouse?->name }}</strong>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('adjust', App\Models\StockBalance::class)
                <flux:button href="{{ route('inventory.stock.movement') }}" variant="primary" icon="arrows-right-left" wire:navigate>
                    {{ __('Catat Pergerakan Stok') }}
                </flux:button>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Jenis Barang') }}</div>
            <div class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mt-2">{{ number_format($totalItems) }}</div>
        </div>
        <div class="p-5 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Barang Stok Kritis (< Min)') }}</div>
            <div class="text-3xl font-bold {{ $criticalCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }} mt-2">
                {{ number_format($criticalCount) }}
            </div>
        </div>
        <div class="p-5 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Barang Backorder (< 0)') }}</div>
            <div class="text-3xl font-bold {{ $negativeCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }} mt-2">
                {{ number_format($negativeCount) }}
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari barang atau barcode...') }}" />
        </div>
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="filter">
                <flux:select.option value="all">{{ __('Semua Stok') }}</flux:select.option>
                <flux:select.option value="critical">{{ __('Kritis / Di bawah Minimum') }}</flux:select.option>
                <flux:select.option value="negative">{{ __('Backorder / Negatif') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('Kode SKU') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Nama Barang') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Satuan') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Minimum') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Saldo Stok Physical') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Status Alert') }}</th>
                    <th class="px-6 py-3 font-semibold text-right">{{ __('Riwayat') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                @forelse ($items as $item)
                    @php
                        $qty = $item->stockBalance?->quantity ?? 0;
                        $isCritical = $qty < $item->minimum_stock;
                        $isNegative = $qty < 0;
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ $isNegative ? 'bg-red-50/70 dark:bg-red-950/30' : ($isCritical ? 'bg-amber-50/50 dark:bg-amber-950/20' : '') }}">
                        <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $item->code }}
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $item->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ strtoupper($item->unit) }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($item->minimum_stock) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-base font-bold {{ $isNegative ? 'text-red-600 dark:text-red-400' : ($isCritical ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                {{ number_format($qty) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($isNegative)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('BACKORDER') }}
                                </span>
                            @elseif ($isCritical)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                    {{ __('STOK KRITIS') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">
                                    {{ __('CUKUP') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <flux:button href="{{ route('inventory.stock.ledger', ['item_id' => $item->id]) }}" size="sm" variant="ghost" icon="document-text" wire:navigate>
                                {{ __('Ledger') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Tidak ada data stok ditemukan.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $items->links() }}
    </div>
</div>
