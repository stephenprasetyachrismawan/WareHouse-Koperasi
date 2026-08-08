<div>
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="text-blue-600 hover:underline">&larr; Kembali</a>
    </div>
    
    <div class="bg-white p-6 rounded shadow-sm">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold mb-1">Detail Request: {{ $pickupRequest->request_number }}</h2>
                <p class="text-gray-600">Pemohon: {{ $pickupRequest->user->name }} | Tanggal: {{ $pickupRequest->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
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
                    $label = $statusLabels[$pickupRequest->status->value] ?? $pickupRequest->status->value;
                    $colorClass = match($pickupRequest->status->value) {
                        'APPROVED', 'READY_FOR_PICKUP', 'COMPLETED' => 'bg-green-100 text-green-800',
                        'REJECTED', 'CANCELLED' => 'bg-red-100 text-red-800',
                        'BACKORDERED' => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-blue-100 text-blue-800'
                    };
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $colorClass }}">{{ $label }}</span>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-3 border-b pb-2">Daftar Barang</h3>
        <table class="w-full text-left border-collapse mb-6">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="p-2">Nama Barang</th>
                    <th class="p-2">Diminta</th>
                    <th class="p-2">Dipenuhi</th>
                    <th class="p-2">Shortage</th>
                    <th class="p-2">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pickupRequest->items as $item)
                    <tr class="border-b">
                        <td class="p-2">{{ $item->item->name }}</td>
                        <td class="p-2">{{ $item->requested_quantity }}</td>
                        <td class="p-2">{{ $item->fulfilled_quantity }}</td>
                        <td class="p-2">
                            @if($item->shortage_quantity > 0)
                                <span class="text-red-600 font-bold">{{ $item->shortage_quantity }}</span>
                            @else
                                0
                            @endif
                        </td>
                        <td class="p-2">{{ $item->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($pickupRequest->approvals->isNotEmpty())
            <h3 class="text-lg font-semibold mb-3 border-b pb-2">Riwayat Approval</h3>
            <ul class="space-y-3">
                @foreach($pickupRequest->approvals as $approval)
                    <li class="bg-gray-50 p-3 rounded border">
                        <div class="flex justify-between">
                            <span class="font-semibold">{{ $approval->actor->name }} ({{ $approval->type }})</span>
                            <span class="text-sm text-gray-500">{{ $approval->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <p class="mt-1">
                            Status: <span class="font-semibold {{ $approval->status === 'APPROVED' ? 'text-green-600' : 'text-red-600' }}">{{ $approval->status }}</span>
                        </p>
                        @if($approval->reason)
                            <p class="text-gray-700 mt-1">Alasan: {{ $approval->reason }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
