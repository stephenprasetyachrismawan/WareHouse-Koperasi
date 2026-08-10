<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Purchasing Queue</h2>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PR Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source / Urgency</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Approved At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $req)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $req->request_number }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $req->source?->value }}<br>
                        <span class="text-xs font-bold {{ $req->urgency?->value === 'URGENT' ? 'text-red-600' : 'text-gray-600' }}">{{ $req->urgency?->value }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $req->approved_at }}</td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('procurement.show', $req->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Detail / Proses PO</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada PR yang approved di antrean.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</div>
