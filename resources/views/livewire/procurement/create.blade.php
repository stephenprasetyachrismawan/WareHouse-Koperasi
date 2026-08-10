<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Create Purchase Request</h2>

    <form wire:submit.prevent="save">
        @if($duplicateWarning)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Warning!</strong> Terdapat Purchase Request yang sedang berjalan (In-Progress) untuk item yang sama:
                        </p>
                        <ul class="list-disc pl-5 mt-2 text-sm text-yellow-700">
                            @foreach($duplicateInfo as $info)
                                <li>{{ $info['request_number'] }} - {{ $info['item_name'] }} (Qty: {{ $info['quantity'] }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="mb-4 bg-white p-4 border rounded shadow-sm">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="is_duplicate_override" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Tetap lanjutkan (Override Duplicate)</span>
                </label>
                @if($is_duplicate_override)
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700">Alasan Override</label>
                        <textarea wire:model.live="duplicate_override_reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                    </div>
                @endif
                @error('is_duplicate_override') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="bg-white p-6 border rounded shadow-sm mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Urgency</label>
                    <select wire:model="urgency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="NORMAL">Normal</option>
                        <option value="URGENT">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes (Opsional)</label>
                    <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <h3 class="text-lg font-medium text-gray-900 mb-2">Items</h3>
            <table class="min-w-full divide-y divide-gray-200 mb-4">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left">Item</th>
                        <th class="px-4 py-2 text-left">Quantity</th>
                        <th class="px-4 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                        <tr>
                            <td class="px-4 py-2">
                                <select wire:model.live="items.{{ $index }}.item_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Pilih Item --</option>
                                    @foreach($availableItems as $ai)
                                        <option value="{{ $ai->id }}">{{ $ai->name }}</option>
                                    @endforeach
                                </select>
                                @error('items.'.$index.'.item_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" wire:model.live="items.{{ $index }}.quantity" min="1" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('items.'.$index.'.quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" wire:click="removeItem({{ $index }})" class="text-red-600 hover:text-red-900">Hapus</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="button" wire:click="addItem" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Tambah Item</button>
            @error('items') <span class="text-red-500 text-xs block mt-2">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit Request</button>
        </div>
    </form>
</div>
