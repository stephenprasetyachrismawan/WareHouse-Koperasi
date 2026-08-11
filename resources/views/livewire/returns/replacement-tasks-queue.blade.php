<div>
    <h2 class="text-2xl font-bold mb-4">Tugas Penggantian Retur</h2>

    <div class="space-y-3">
        @forelse ($returns as $return)
            <a href="{{ route('returns.show', $return->uuid) }}" class="block bg-white rounded shadow-sm p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start gap-3">
                    <div>
                        <p class="font-semibold">{{ $return->return_number }}</p>
                        <p class="text-sm text-gray-600">{{ $return->cooperativeMembership?->user?->name }}</p>
                    </div>
                    @php
                        $statusLabels = [
                            'REPLACEMENT_PENDING' => 'Menunggu Stok Penggantian',
                            'READY_FOR_REPICKUP' => 'Siap Diambil Kembali',
                        ];
                        $label = $statusLabels[$return->status->value] ?? $return->status->label();
                        $colorClass = $return->status->value === 'READY_FOR_REPICKUP' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800';
                    @endphp
                    <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap {{ $colorClass }}">{{ $label }}</span>
                </div>

                <div class="mt-3 text-sm">
                    <p>{{ $return->items->first()?->item?->name }} &middot; Jumlah {{ $return->items->first()?->return_quantity }}</p>
                    @if ($return->replacementPickup)
                        <p class="text-gray-500">Pickup Penggantian: {{ $return->replacementPickup->request_number }}</p>
                    @elseif ($return->replacementPurchaseRequests->isNotEmpty())
                        <p class="text-gray-500">
                            PR Penggantian: {{ $return->replacementPurchaseRequests->last()->request_number }}
                            ({{ $return->replacementPurchaseRequests->last()->status->label() }})
                        </p>
                    @endif
                </div>
            </a>
        @empty
            <div class="bg-white rounded shadow-sm p-4 text-center text-sm text-gray-500">
                Tidak ada tugas penggantian retur saat ini.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $returns->links() }}</div>
</div>
