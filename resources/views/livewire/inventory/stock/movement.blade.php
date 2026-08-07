<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Pencatatan Transaksi Stok') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Catat saldo awal, penyesuaian masuk, atau penyesuaian keluar barang secara eksplisit.') }}
        </p>
    </div>

    <!-- Quick Barcode Search Form -->
    <div class="p-4 bg-zinc-100 dark:bg-zinc-800/60 rounded-lg space-y-2">
        <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">{{ __('Cari via Scanner / Barcode / SKU') }}</label>
        <div class="flex gap-2">
            <flux:input wire:model="barcodeSearch" wire:keydown.enter="searchBarcode" placeholder="Scan barcode barang di sini..." icon="qr-code" class="flex-1" />
            <flux:button wire:click="searchBarcode" variant="subtle">{{ __('Cari') }}</flux:button>
        </div>
        @error('barcodeSearch')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <form wire:submit="save" class="space-y-6 bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-800">
        <div class="space-y-4">
            <flux:select wire:model.live="item_id" label="{{ __('Pilih Barang Master') }}" required>
                <flux:select.option value="">{{ __('-- Pilih Barang --') }}</flux:select.option>
                @foreach ($items as $item)
                    <flux:select.option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }} ({{ strtoupper($item->unit) }})</flux:select.option>
                @endforeach
            </flux:select>

            @if ($selectedItem)
                <div class="p-3 bg-zinc-50 dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 flex justify-between items-center text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Saldo fisik saat ini:') }}</span>
                        <strong class="ml-1 text-zinc-900 dark:text-zinc-100">{{ number_format($selectedItem->stockBalance?->quantity ?? 0) }} {{ strtoupper($selectedItem->unit) }}</strong>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Min Alert:') }}</span>
                        <span class="ml-1 font-mono text-zinc-700 dark:text-zinc-300">{{ number_format($selectedItem->minimum_stock) }}</span>
                    </div>
                </div>
            @endif

            <flux:select wire:model="movement_type" label="{{ __('Jenis Pergerakan') }}" required>
                <flux:select.option value="OPENING_BALANCE">{{ __('OPENING_BALANCE — Saldo Awal Gudang') }}</flux:select.option>
                <flux:select.option value="MANUAL_ADJUSTMENT_IN">{{ __('MANUAL_ADJUSTMENT_IN — Penyesuaian Masuk (Stok +)') }}</flux:select.option>
                <flux:select.option value="MANUAL_ADJUSTMENT_OUT">{{ __('MANUAL_ADJUSTMENT_OUT — Penyesuaian Keluar (Stok -)') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="quantity" type="number" label="{{ __('Jumlah (Quantity)') }}" required min="1" />

            <flux:textarea wire:model="reason" label="{{ __('Alasan / Catatan Transaksi') }}" required rows="3" placeholder="{{ __('Jelaskan alasan pencatatan saldo atau penyesuaian ini...') }}" />
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button href="{{ route('inventory.stock.overview') }}" variant="ghost" wire:navigate>
                {{ __('Batal') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ __('Submit Transaksi Stok') }}
            </flux:button>
        </div>
    </form>
</div>
