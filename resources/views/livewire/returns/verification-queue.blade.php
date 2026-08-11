<div>
    <h2 class="text-2xl font-bold mb-4">Retur Menunggu Verifikasi</h2>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Retur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Koperasi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($returns as $return)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $return->return_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $return->cooperativeMembership?->user?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $return->items->first()?->item?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $return->items->first()?->return_quantity }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $return->reason_code->label() }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <a href="{{ route('returns.show', $return->uuid) }}" class="text-indigo-600 hover:text-indigo-900">Verifikasi</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada retur yang menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $returns->links() }}</div>
</div>
