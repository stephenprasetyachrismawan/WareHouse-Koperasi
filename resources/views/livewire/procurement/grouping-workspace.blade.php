<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Grouping & Purchase Order</h2>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    @if ($step === 1)
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">1. Pilih Purchase Request Item</h3>
                <p class="mt-1 text-sm text-gray-500">Hanya Purchase Request berstatus APPROVED yang masih memiliki sisa kuantitas alokasi.</p>
            </div>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PR Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sisa Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alokasikan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($candidates as $candidate)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $candidate->purchaseRequest->request_number }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $candidate->item->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $candidate->remaining_quantity }} {{ $candidate->item->unit }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <input type="number" min="0" max="{{ $candidate->remaining_quantity }}"
                                        wire:model="selected.{{ $candidate->id }}"
                                        class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada Purchase Request yang siap dialokasikan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('selected') <p class="px-6 py-3 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-4">
            <label class="block text-sm font-medium text-gray-700">Catatan Grup (opsional)</label>
            <textarea wire:model="groupNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
        </div>

        <button wire:click="proceedToSupplierStep" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Lanjut ke Pemilihan Supplier
        </button>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">2. Ringkasan & Supplier</h3>
            </div>
            <div class="border-t border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($cartItems as $row)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['item_name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $row['total_quantity'] }} {{ $row['item_unit'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <input type="number" min="0" step="0.01"
                                        wire:model="unitCosts.{{ $row['item_id'] }}"
                                        class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Supplier</label>
                <select wire:model="supplierId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplierId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Catatan PO (opsional)</label>
                <textarea wire:model="poNotes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
        </div>

        <div class="space-x-2">
            <button wire:click="backToSelection" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                Kembali
            </button>
            <button wire:click="createPurchaseOrder" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Buat Purchase Order
            </button>
        </div>
    @endif
</div>
