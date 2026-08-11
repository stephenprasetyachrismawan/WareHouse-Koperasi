<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Catat Penerimaan: {{ $purchaseOrder->po_number }}</h2>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-4">
        <p class="text-sm text-gray-500">Supplier: <span class="text-gray-900">{{ $purchaseOrder->supplier?->name }}</span></p>
        <p class="text-sm text-gray-500 mt-2">Versi ini tidak mendukung penerimaan sebagian. Jumlah diterima wajib sama persis dengan jumlah dipesan.</p>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Dipesan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Diterima</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($purchaseOrder->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->item->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->ordered_quantity }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <input type="number" min="0" wire:model="receivedQuantities.{{ $item->id }}"
                                class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-4">
        <label class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
        <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
    </div>

    @error('receivedQuantities') <p class="mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

    <button wire:click="save" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        Konfirmasi Penerimaan
    </button>
</div>
