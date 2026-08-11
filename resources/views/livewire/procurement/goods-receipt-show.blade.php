<div>
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Penerimaan: {{ $goodsReceipt->receipt_number }}</h2>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Utama</h3>
            <p class="mt-1 text-sm text-gray-500">Status: {{ $goodsReceipt->status->label() }}</p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Purchase Order</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <a href="{{ route('procurement.purchase-orders.show', $goodsReceipt->purchaseOrder->uuid) }}" class="text-indigo-600 hover:text-indigo-900">{{ $goodsReceipt->purchaseOrder->po_number }}</a>
                        — {{ $goodsReceipt->purchaseOrder->supplier?->name }}
                    </dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Diterima Oleh</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $goodsReceipt->receiver?->name }} ({{ $goodsReceipt->received_at }})</dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $goodsReceipt->notes ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <h3 class="text-xl font-semibold mb-4">Item & Status QC</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Diterima</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hasil QC</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($goodsReceipt->items as $item)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->item->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->received_quantity }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if ($item->inspection)
                                @if ($item->inspection->isPass())
                                    <span class="text-green-700 font-medium">Lolos — Stok Masuk</span>
                                @else
                                    <span class="text-red-700 font-medium">QC Gagal — Stok-In Diblokir</span>
                                @endif
                                <div class="text-xs text-gray-500">oleh {{ $item->inspection->inspector?->name }}</div>
                            @else
                                <span class="text-yellow-700">Menunggu QC</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            @if (! $item->inspection && auth()->user()->can('create', [\App\Models\QualityInspection::class, $item]))
                                <button wire:click="openQcModal({{ $item->id }})" class="text-indigo-600 hover:text-indigo-900">Inspeksi QC</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showQcModal)
    <div class="fixed z-10 inset-0 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="submitQc">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quality Inspection</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hasil</label>
                            <select wire:model.live="result" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="PASS">Lolos (PASS)</option>
                                <option value="FAIL">Gagal (FAIL)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Kondisi</label>
                            <select wire:model="condition" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="GOOD">Baik</option>
                                <option value="DAMAGED">Rusak</option>
                                <option value="EXPIRED">Kedaluwarsa</option>
                                <option value="WRONG_ITEM">Barang Salah</option>
                                <option value="OTHER">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Catatan @if ($result === 'FAIL') <span class="text-red-600">(wajib)</span> @endif
                            </label>
                            <textarea wire:model="qcNotes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            @error('qcNotes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Foto Bukti (opsional)</label>
                            <input type="file" wire:model="evidence" accept="image/jpeg,image/png" class="mt-1 block w-full text-sm" />
                            @error('evidence') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if ($result === 'FAIL')
                            <p class="text-sm text-red-700 font-medium">QC Failed — Stock-In Blocked. Barang belum masuk ke inventaris.</p>
                        @endif
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Konfirmasi
                        </button>
                        <button type="button" wire:click="closeQcModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
