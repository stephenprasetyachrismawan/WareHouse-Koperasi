<div>
    <h2 class="text-2xl font-bold mb-4">Retur Menunggu Keputusan</h2>

    <div class="space-y-3">
        @forelse ($returns as $return)
            <a href="{{ route('returns.show', $return->uuid) }}" class="block bg-white rounded shadow-sm p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start gap-3">
                    <div>
                        <p class="font-semibold">{{ $return->return_number }}</p>
                        <p class="text-sm text-gray-600">{{ $return->cooperativeMembership?->user?->name }}</p>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ $return->waiting_approval_at?->diffForHumans() }}
                    </span>
                </div>

                <div class="mt-3 text-sm">
                    <p>{{ $return->items->first()?->item?->name }} &middot; Jumlah {{ $return->items->first()?->return_quantity }}</p>
                    <p class="text-gray-500">Alasan: {{ $return->reason_code->label() }}</p>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ $return->evidence->count() }} foto bukti</span>
                    <span class="text-indigo-600 text-sm font-medium">Tinjau &rarr;</span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded shadow-sm p-4 text-center text-sm text-gray-500">
                Tidak ada retur yang menunggu keputusan.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $returns->links() }}</div>
</div>
