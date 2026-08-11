<div>
    <h2 class="text-2xl font-bold mb-4">Ajukan Retur Barang</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $reviewing)
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">1. Pilih Barang yang Ingin Diretur</h3>

            @if ($eligibleItems->isEmpty())
                <p class="text-sm text-gray-500">Belum ada barang dari pengambilan yang selesai dan bisa diretur.</p>
            @else
                <div class="space-y-2">
                    @foreach ($eligibleItems as $eligible)
                        <label class="flex items-center justify-between border rounded p-3 cursor-pointer {{ $selectedPickupRequestItemId === $eligible->id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                            <div>
                                <input type="radio" name="pickup_item" class="mr-2" wire:click="selectItem({{ $eligible->id }})" @checked($selectedPickupRequestItemId === $eligible->id) />
                                <span class="font-medium">{{ $eligible->item->name }}</span>
                                <span class="text-sm text-gray-500">— Pengambilan #{{ $eligible->pickupRequest->request_number }}</span>
                            </div>
                            <span class="text-sm text-gray-600">Maks. {{ $eligible->eligible_quantity }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($selectedItem)
            <div class="bg-white p-4 rounded shadow-sm mb-6">
                <h3 class="font-semibold mb-3">2. Detail Retur</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (maks. {{ $selectedItem->eligible_quantity }})</label>
                    <flux:input type="number" wire:model="quantity" min="1" max="{{ $selectedItem->eligible_quantity }}" class="w-32" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Retur</label>
                    <select wire:model.live="reasonCode" class="border rounded p-2 w-full max-w-sm">
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($reasonCode === 'OTHER')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (wajib)</label>
                        <flux:input type="text" wire:model="reasonNotes" placeholder="Jelaskan alasan retur..." class="w-full max-w-md" />
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti (wajib)</label>
                    <input type="file" wire:model="photo" accept="image/*" capture="environment" class="block" />
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" class="mt-2 h-32 rounded border" />
                    @endif
                </div>

                <p class="text-sm text-gray-600 mb-4">Dengan mengajukan retur, saya menyatakan barang ini diserahkan untuk diperiksa oleh Staff Gudang.</p>

                <flux:button variant="primary" wire:click="goToReview">Lanjut ke Ringkasan</flux:button>
            </div>
        @endif
    @else
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">3. Ringkasan &amp; Konfirmasi</h3>

            <dl class="grid grid-cols-2 gap-y-2 text-sm mb-4">
                <dt class="text-gray-500">Barang</dt>
                <dd>{{ $selectedItem->item->name }}</dd>
                <dt class="text-gray-500">Jumlah</dt>
                <dd>{{ $quantity }}</dd>
                <dt class="text-gray-500">Alasan</dt>
                <dd>{{ \App\Enums\ReturnReasonCode::from($reasonCode)->label() }}</dd>
                @if ($reasonNotes)
                    <dt class="text-gray-500">Catatan</dt>
                    <dd>{{ $reasonNotes }}</dd>
                @endif
            </dl>

            @if ($photo)
                <img src="{{ $photo->temporaryUrl() }}" class="h-40 rounded border mb-4" />
            @endif

            <p class="text-sm text-gray-600 mb-4">Barang diserahkan untuk pemeriksaan oleh Staff Gudang.</p>

            <div class="flex gap-3">
                <flux:button wire:click="backToForm">Kembali</flux:button>
                <flux:button variant="primary" wire:click="submit">Ajukan Retur</flux:button>
            </div>
        </div>
    @endif
</div>
