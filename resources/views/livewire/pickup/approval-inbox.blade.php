<div>
    <h2 class="text-2xl font-bold mb-4">Inbox Approval Pengambilan</h2>

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
                    <th class="p-3">Request</th>
                    <th class="p-3">Pemohon</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Shortage</th>
                    <th class="p-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    @php
                        $hasShortage = $req->items->contains(fn($i) => $i->shortage_quantity > 0);
                    @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3">
                            <a href="{{ route('pickup.show', $req->uuid) }}" class="text-blue-600 font-semibold hover:underline">
                                {{ $req->request_number }}
                            </a>
                            <div class="text-xs text-gray-500">{{ $req->created_at->format('d M Y H:i') }}</div>
                        </td>
                        <td class="p-3">{{ $req->user->name }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $req->status->value === 'BACKORDERED' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $req->status->value === 'BACKORDERED' ? 'Menunggu Stok' : 'Menunggu Approval' }}
                            </span>
                        </td>
                        <td class="p-3">
                            @if($hasShortage)
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-bold">Ada Shortage!</span>
                            @else
                                <span class="text-green-600 text-sm">Aman</span>
                            @endif
                        </td>
                        <td class="p-3 space-x-2">
                            <flux:button size="sm" variant="primary" wire:click="approve({{ $req->id }})" wire:confirm="Setujui request ini?">Approve</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="openRejectModal({{ $req->id }})">Reject</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-3 text-center text-gray-500">Tidak ada request yang menunggu approval.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3">
            {{ $requests->links() }}
        </div>
    </div>

    @if($showRejectModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                <h3 class="text-lg font-bold mb-4">Tolak Request</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Alasan Penolakan</label>
                    <flux:input wire:model="rejectReason" placeholder="Wajib diisi..." />
                    @error('rejectReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end space-x-2">
                    <flux:button wire:click="closeRejectModal">Batal</flux:button>
                    <flux:button variant="danger" wire:click="confirmReject">Konfirmasi Tolak</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
