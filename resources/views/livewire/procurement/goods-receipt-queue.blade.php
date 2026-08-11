<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Penerimaan Barang</h2>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <h3 class="text-lg font-medium text-gray-900 mb-3">Menunggu Diterima</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($receivable as $po)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $po->po_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $po->supplier?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $po->items->count() }} item</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('procurement.receipts.create', $po->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Catat Penerimaan</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada Purchase Order yang menunggu diterima.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mb-8">{{ $receivable->links() }}</div>

    <h3 class="text-lg font-medium text-gray-900 mb-3">Riwayat Penerimaan</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($received as $receipt)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $receipt->receipt_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receipt->purchaseOrder?->po_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receipt->status->label() }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('procurement.receipts.show', $receipt->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada penerimaan tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $received->links() }}</div>
</div>
