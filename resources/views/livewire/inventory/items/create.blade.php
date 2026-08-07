<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ __('Tambah Barang Master') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Daftarkan barang baru ke dalam katalog master gudang aktif Anda.') }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-6 bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-800">
        <div class="space-y-4">
            <flux:input wire:model="code" label="{{ __('Kode Barang / SKU') }}" required placeholder="Contoh: SKU-001" />

            <flux:input wire:model="name" label="{{ __('Nama Barang') }}" required placeholder="Contoh: Minyak Goreng 1L" />

            <flux:textarea wire:model="description" label="{{ __('Deskripsi') }}" rows="3" placeholder="{{ __('Keterangan atau spesifikasi barang...') }}" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="unit" label="{{ __('Satuan Utu (Unit)') }}" required placeholder="PCS / BOX / PACK" />

                <flux:input wire:model="minimum_stock" type="number" label="{{ __('Stok Minimum Alert') }}" required min="0" />
            </div>

            <flux:input wire:model="barcode" label="{{ __('Barcode Utama (Opsional)') }}" icon="qr-code" placeholder="Scan atau masukkan nomor barcode..." />
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button href="{{ route('inventory.items.index') }}" variant="ghost" wire:navigate>
                {{ __('Batal') }}
            </flux:button>
            <flux:button type="submit" variant="primary">
                {{ __('Simpan Barang') }}
            </flux:button>
        </div>
    </form>
</div>
