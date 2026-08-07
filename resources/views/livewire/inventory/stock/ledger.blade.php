<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Ledger Transaksi Stok (Append-Only)') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Riwayat audit mutasi stok fisik yang tidak dapat diubah (immutable) untuk ') }} <strong>{{ $warehouse?->name }}</strong>
            </p>
        </div>
        <div>
            <flux:button href="{{ route('inventory.stock.overview') }}" variant="subtle" icon="arrow-left" wire:navigate>
                {{ __('Kembali ke Saldo Stok') }}
            </flux:button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari barang, alasan, atau key...') }}" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:select wire:model.live="item_id">
                <flux:select.option value="">{{ __('-- Semua Barang --') }}</flux:select.option>
                @foreach ($items as $itm)
                    <flux:select.option value="{{ $itm->id }}">{{ $itm->code }} - {{ $itm->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="movement_type">
                <flux:select.option value="">{{ __('-- Semua Tipe Movement --') }}</flux:select.option>
                @foreach (App\Enums\MovementType::cases() as $type)
                    <flux:select.option value="{{ $type->value }}">{{ $type->label() }} ({{ $type->value }})</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-xs sm:text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('Waktu (UTC)') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Barang SKU') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Tipe Transaksi') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Jumlah') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Sebelum') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Sesudah') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Operator') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('Alasan / Detail') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950 font-mono">
                @forelse ($transactions as $tx)
                    @php
                        $isInbound = $tx->signed_quantity > 0;
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">
                            {{ $tx->occurred_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-4 py-3 font-sans">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $tx->item?->name }}</div>
                            <div class="text-xs font-mono text-zinc-500">{{ $tx->item?->code }}</div>
                        </td>
                        <td class="px-4 py-3 font-sans">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                {{ $tx->movement_type->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-bold text-base {{ $isInbound ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $isInbound ? '+'.$tx->signed_quantity : $tx->signed_quantity }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($tx->balance_before) }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ number_format($tx->balance_after) }}
                        </td>
                        <td class="px-4 py-3 font-sans text-zinc-600 dark:text-zinc-400">
                            {{ $tx->performer?->name ?? 'System' }}
                        </td>
                        <td class="px-4 py-3 font-sans text-xs text-zinc-500 dark:text-zinc-400 max-w-xs truncate">
                            {{ $tx->reason ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400 font-sans">
                            {{ __('Tidak ada riwayat ledger transaksi ditemukan.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $transactions->links() }}
    </div>
</div>
