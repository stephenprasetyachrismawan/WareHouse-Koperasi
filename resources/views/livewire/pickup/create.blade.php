<div>
    <h2 class="text-2xl font-bold mb-4">Buat Request Pengambilan Baru</h2>
    
    @if($errors->has('general'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ $errors->first('general') }}
        </div>
    @endif

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Cari Barang</label>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama barang..." />
        
        @if(!empty($searchResults))
            <ul class="border mt-1 rounded bg-white shadow-sm absolute z-10 w-full max-w-md">
                @foreach($searchResults as $item)
                    <li class="p-2 hover:bg-gray-100 cursor-pointer flex justify-between items-center" wire:click="addItem({{ $item->id }})">
                        <span>{{ $item->name }} (Stok: {{ $item->stockBalance->quantity ?? 0 }})</span>
                        <flux:button size="sm">Tambah</flux:button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if(count($items) > 0)
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">Daftar Barang ({{ count($items) }})</h3>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="p-2">Nama Barang</th>
                        <th class="p-2">Qty</th>
                        <th class="p-2">Catatan</th>
                        <th class="p-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr class="border-b">
                            <td class="p-2">{{ $item['name'] }}</td>
                            <td class="p-2">
                                <flux:input type="number" wire:model="items.{{ $index }}.quantity" min="1" class="w-20" />
                            </td>
                            <td class="p-2">
                                <flux:input type="text" wire:model="items.{{ $index }}.notes" placeholder="Catatan..." />
                            </td>
                            <td class="p-2">
                                <flux:button variant="danger" size="sm" wire:click="removeItem({{ $index }})">Hapus</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700">Catatan Tambahan (Opsional)</label>
        <flux:input wire:model="notes" placeholder="Catatan untuk kepala gudang..." />
    </div>

    <flux:button variant="primary" wire:click="submit" :disabled="count($items) === 0">
        Submit Request
    </flux:button>
</div>
