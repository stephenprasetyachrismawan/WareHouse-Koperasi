<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Purchase Orders</h2>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat Oleh</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($purchaseOrders as $po)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $po->po_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $po->supplier?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $po->status->label() }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $po->creator?->name }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('procurement.purchase-orders.show', $po->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada Purchase Order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $purchaseOrders->links() }}</div>
</div>
