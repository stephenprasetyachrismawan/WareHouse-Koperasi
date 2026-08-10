<div>
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-800">Purchase Requests</h2>
        @can('create', App\Models\PurchaseRequest::class)
        <a href="{{ route('procurement.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Create Request
        </a>
        @endcan
    </div>

    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select wire:model.live="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua</option>
                <option value="WAITING_APPROVAL">Menunggu Approval</option>
                <option value="APPROVED">Disetujui</option>
                <option value="REJECTED">Ditolak</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Source</label>
            <select wire:model.live="source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua</option>
                <option value="MANUAL">Manual</option>
                <option value="AUTO_STOCK_SHORTAGE">Auto Stock Shortage</option>
                <option value="PICKUP_SHORTAGE">Pickup Shortage</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Urgency</label>
            <select wire:model.live="urgency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua</option>
                <option value="NORMAL">Normal</option>
                <option value="URGENT">Urgent</option>
            </select>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Request</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemohon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source & Urgency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $req)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $req->request_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $req->creator?->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $req->source?->value }} <br>
                        <span class="text-xs {{ $req->urgency?->value === 'URGENT' ? 'text-red-600 font-bold' : '' }}">{{ $req->urgency?->value }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($req->status->value === 'WAITING_APPROVAL')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Menunggu Approval</span>
                        @elseif($req->status->value === 'APPROVED')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Disetujui</span>
                        @elseif($req->status->value === 'REJECTED')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Ditolak</span>
                        @elseif($req->status->value === 'CANCELLED')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Dibatalkan</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ $req->status->label() }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('procurement.show', $req->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Tidak ada data.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
