<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Request Pengambilan Saya</h2>
        <flux:button variant="primary" href="{{ route('pickup.create') }}">Buat Request</flux:button>
    </div>

    @if(session('status'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('status') }}
        </div>
    @endif
    
    @if($errors->has('general'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            {{ $errors->first('general') }}
        </div>
    @endif

    <div class="bg-white rounded shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    <th class="p-3">No. Request</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">{{ $req->request_number }}</td>
                        <td class="p-3">{{ $req->created_at->format('d M Y H:i') }}</td>
                        <td class="p-3">
                            @php
                                $statusLabels = [
                                    'SUBMITTED' => 'Menunggu Check',
                                    'WAITING_APPROVAL' => 'Menunggu Approval',
                                    'APPROVED' => 'Disetujui',
                                    'REJECTED' => 'Ditolak',
                                    'READY_FOR_PICKUP' => 'Siap Diambil',
                                    'COMPLETED' => 'Selesai',
                                    'BACKORDERED' => 'Menunggu Stok',
                                    'CANCELLED' => 'Dibatalkan',
                                ];
                                $label = $statusLabels[$req->status->value] ?? $req->status->value;
                                $colorClass = match($req->status->value) {
                                    'APPROVED', 'READY_FOR_PICKUP', 'COMPLETED' => 'bg-green-100 text-green-800',
                                    'REJECTED', 'CANCELLED' => 'bg-red-100 text-red-800',
                                    'BACKORDERED' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-blue-100 text-blue-800'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $colorClass }}">{{ $label }}</span>
                        </td>
                        <td class="p-3 space-x-2">
                            <flux:button size="sm" href="{{ route('pickup.show', $req->uuid) }}">Detail</flux:button>
                            @if(in_array($req->status->value, ['SUBMITTED', 'WAITING_APPROVAL', 'BACKORDERED']))
                                <flux:button size="sm" variant="danger" wire:click="cancelRequest({{ $req->id }})" wire:confirm="Yakin ingin membatalkan request ini?">Batal</flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-3 text-center text-gray-500">Belum ada request.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">
            {{ $requests->links() }}
        </div>
    </div>
</div>
