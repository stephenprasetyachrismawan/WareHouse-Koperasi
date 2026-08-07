<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Katalog Barang Gudang') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Kelola daftar barang master, barcode, dan batas stok minimum untuk ') }} <strong>{{ $warehouse?->name }}</strong>
            </p>
        </div>
        <div>
            @can('create', App\Models\Item::class)
                <flux:button href="{{ route('inventory.items.create') }}" variant="primary" icon="plus" wire:navigate>
                    {{ __('Tambah Barang') }}
                </flux:button>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-zinc-800 dark:text-green-400" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Cari kode, nama, atau barcode...') }}" />
        </div>
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="active">{{ __('Aktif') }}</flux:select.option>
                <flux:select.option value="archived">{{ __('Arsip') }}</flux:select.option>
                <flux:select.option value="all">{{ __('Semua Status') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('Kode / SKU') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Nama Barang') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Barcode Utama') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Satuan') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Stok Saat Ini') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('Stok Min.') }}</th>
                    <th class="px-6 py-3 font-semibold text-right">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                @forelse ($items as $item)
                    @php
                        $currentStock = $item->stockBalance?->quantity ?? 0;
                        $isCritical = $item->is_active && ($currentStock < $item->minimum_stock);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 {{ $isCritical ? 'bg-red-50/50 dark:bg-red-950/20' : '' }}">
                        <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $item->code }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</div>
                            @if ($item->description)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-zinc-600 dark:text-zinc-400">
                            {{ $item->primaryBarcode()?->barcode ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ strtoupper($item->unit) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold {{ $currentStock < 0 ? 'text-red-600 dark:text-red-400' : ($isCritical ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100') }}">
                                {{ number_format($currentStock) }}
                            </span>
                            @if ($isCritical)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('KRITIS') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($item->minimum_stock) }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            @can('update', $item)
                                <flux:button href="{{ route('inventory.items.edit', $item) }}" size="sm" variant="ghost" icon="pencil-square" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                            @endcan
                            @can('archive', $item)
                                @if ($item->is_active)
                                    <flux:button wire:click="archiveItem({{ $item->id }})" wire:confirm="{{ __('Yakin ingin mengarsipkan barang ini?') }}" size="sm" variant="danger">
                                        {{ __('Arsipkan') }}
                                    </flux:button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Tidak ada barang ditemukan.') }}
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
