<div>
    <h2 class="text-2xl font-bold mb-4">Penyiapan & Pickup (Fulfilment)</h2>

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

    <div class="bg-white p-4 rounded shadow-sm mb-6 flex gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium mb-1">Cari Nomor Request (Status: APPROVED / READY_FOR_PICKUP)</label>
            <flux:input wire:model.defer="searchRequestNumber" placeholder="Contoh: REQ-20231010-ABC12345" />
            @error('searchRequestNumber') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        <flux:button variant="primary" wire:click="search">Cari</flux:button>
    </div>

    @if($currentRequest)
        <div class="bg-white p-6 rounded shadow-sm">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold">Request: {{ $currentRequest->request_number }}</h3>
                    <p class="text-gray-600">Pemohon: {{ $currentRequest->user->name }}</p>
                    @if ($currentRequest->source === \App\Enums\PickupRequestSource::ReturnReplacement)
                        <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">Penggantian Retur</span>
                    @endif
                </div>
                <div>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                        {{ $currentRequest->status->value === 'READY_FOR_PICKUP' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                        {{ $currentRequest->status->value === 'READY_FOR_PICKUP' ? 'Siap Diambil' : 'Disetujui' }}
                    </span>
                </div>
            </div>

            <table class="w-full text-left border-collapse mb-6">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="p-2">Barang</th>
                        <th class="p-2">Qty Diminta</th>
                        <th class="p-2">Shortage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($currentRequest->items as $item)
                        <tr class="border-b">
                            <td class="p-2">{{ $item->item->name }}</td>
                            <td class="p-2">{{ $item->requested_quantity }}</td>
                            <td class="p-2 text-red-600 font-semibold">{{ $item->shortage_quantity > 0 ? $item->shortage_quantity : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex space-x-3">
                @if($currentRequest->status->value === 'APPROVED')
                    <flux:button variant="primary" wire:click="markReady">Tandai Siap Diambil</flux:button>
                @endif
                
                @if(in_array($currentRequest->status->value, ['APPROVED', 'READY_FOR_PICKUP']))
                    <flux:button variant="primary" wire:click="fulfill" wire:confirm="Selesaikan dan potong stok?">Selesaikan Pickup (Fulfill)</flux:button>
                @endif
            </div>
        </div>
    @endif
</div>
