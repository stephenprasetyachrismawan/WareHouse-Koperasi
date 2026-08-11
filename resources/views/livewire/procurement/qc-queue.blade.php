<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Antrean QC</h2>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($pendingItems as $receiptItem)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $receiptItem->goodsReceipt->receipt_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receiptItem->goodsReceipt->purchaseOrder->po_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receiptItem->goodsReceipt->purchaseOrder->supplier?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receiptItem->item->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $receiptItem->received_quantity }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('procurement.receipts.show', $receiptItem->goodsReceipt->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Inspeksi</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada item yang menunggu QC.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pendingItems->links() }}</div>
</div>
