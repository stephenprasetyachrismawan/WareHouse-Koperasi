<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Retur Saya</h2>
        @can('create', \App\Models\ReturnRequest::class)
            <flux:button variant="primary" href="{{ route('returns.create') }}">Ajukan Retur</flux:button>
        @endcan
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    <th class="p-3">No. Retur</th>
                    <th class="p-3">Barang</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">{{ $return->return_number }}</td>
                        <td class="p-3">{{ $return->items->first()?->item?->name }}</td>
                        <td class="p-3">{{ $return->created_at->format('d M Y H:i') }}</td>
                        <td class="p-3">
                            @php
                                $statusLabels = [
                                    'SUBMITTED' => 'Diajukan',
                                    'ADMIN_VERIFIED' => 'Diverifikasi Staff',
                                    'WAITING_APPROVAL' => 'Menunggu Keputusan',
                                ];
                                $label = $statusLabels[$return->status->value] ?? $return->status->label();
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $label }}</span>
                        </td>
                        <td class="p-3">
                            <flux:button size="sm" href="{{ route('returns.show', $return->uuid) }}">Detail</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-3 text-center text-gray-500">Belum ada retur.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">
            {{ $returns->links() }}
        </div>
    </div>
</div>
