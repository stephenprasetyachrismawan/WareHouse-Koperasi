<div>
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-800">Purchase Order: {{ $purchaseOrder->po_number }}</h2>
        <div class="space-x-2">
            @if ($purchaseOrder->status->value === 'DRAFT' && auth()->user()->can('send', $purchaseOrder))
                <button wire:click="send" wire:confirm="Kirim Purchase Order ini ke supplier?" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Kirim ke Supplier
                </button>
            @endif
            @if ($purchaseOrder->goodsReceipt)
                <a href="{{ route('procurement.receipts.show', $purchaseOrder->goodsReceipt->uuid) }}" class="px-4 py-2 bg-white border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                    Lihat Penerimaan
                </a>
            @elseif ($purchaseOrder->status->value === 'SENT_TO_SUPPLIER' && auth()->user()->can('create', \App\Models\GoodsReceipt::class))
                <a href="{{ route('procurement.receipts.create', $purchaseOrder->uuid) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Catat Penerimaan
                </a>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Utama</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Status: {{ $purchaseOrder->status->label() }}</p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseOrder->supplier?->name }}</dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Dibuat Oleh</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseOrder->creator?->name }}</dd>
                </div>
                @if ($purchaseOrder->sentBy)
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Dikirim Oleh</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseOrder->sentBy->name }} ({{ $purchaseOrder->sent_at }})</dd>
                    </div>
                @endif
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseOrder->notes ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <h3 class="text-xl font-semibold mb-4">Progres Penerimaan &amp; QC</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dipesan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diterima</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">QC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stok-In</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($receivingProgress as $line)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $line['item_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line['ordered_quantity'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line['received_quantity'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line['qc_result'] ?? 'Pending' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line['stock_in'] ? 'Selesai' : 'Pending' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h3 class="text-xl font-semibold mb-4">Item & Traceability Alokasi</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Dipesan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sumber Purchase Request</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($traceability as $line)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 align-top">{{ $line['item_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 align-top">{{ $line['ordered_quantity'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <ul class="space-y-1">
                                @foreach ($line['allocations'] as $allocation)
                                    <li>{{ $allocation['purchase_request_number'] }} — {{ $allocation['allocated_quantity'] }} ({{ $allocation['source']?->value }})</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
