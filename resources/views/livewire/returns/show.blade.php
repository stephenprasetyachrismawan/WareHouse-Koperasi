<div>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Retur {{ $returnRequest->return_number }}</h2>
        <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">{{ $returnRequest->status->label() }}</span>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if ($errors->has('verify'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first('verify') }}</div>
    @endif
    @if ($errors->has('submitForApproval'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first('submitForApproval') }}</div>
    @endif
    @if ($errors->has('decision'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first('decision') }}</div>
    @endif

    @if ($returnRequest->status === \App\Enums\ReturnStatus::Rejected)
        <div class="bg-red-50 border border-red-200 p-4 rounded mb-6">
            <p class="font-semibold text-red-800">Status: Ditolak</p>
            <p class="text-sm text-red-700 mt-1">Alasan: {{ $returnRequest->decision_notes }}</p>
        </div>
    @elseif (in_array($returnRequest->status, [\App\Enums\ReturnStatus::Approved, \App\Enums\ReturnStatus::ReplacementPending], true))
        <div class="bg-green-50 border border-green-200 p-4 rounded mb-6">
            <p class="font-semibold text-green-800">
                Status: {{ $returnRequest->status === \App\Enums\ReturnStatus::ReplacementPending ? 'Disetujui — Menunggu Penggantian' : 'Disetujui' }}
            </p>
        </div>
    @endif

    @if ($canSeeAttribution && $returnRequest->fault_attribution)
        <div class="bg-slate-50 border border-slate-200 p-4 rounded mb-6 text-sm">
            <p><span class="font-semibold">Fault Attribution:</span> {{ $returnRequest->fault_attribution->label() }}</p>
            <p><span class="font-semibold">Rule:</span> {{ $returnRequest->fault_rule_version }}</p>
            <p><span class="font-semibold">Basis:</span> {{ $returnRequest->fault_attribution === \App\Enums\ReturnFaultAttribution::Warehouse ? 'Traceable QC evidence found' : 'No traceable QC evidence' }}</p>
        </div>
    @endif

    <div class="bg-white p-4 rounded shadow-sm mb-6">
        <h3 class="font-semibold mb-3">Detail Pengambilan Asal</h3>
        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-500">No. Pengambilan</dt>
            <dd>{{ $returnRequest->pickupRequest->request_number }}</dd>
            <dt class="text-gray-500">Koperasi</dt>
            <dd>{{ $returnRequest->cooperativeMembership?->user?->name }}</dd>
            <dt class="text-gray-500">Alasan</dt>
            <dd>{{ $returnRequest->reason_code->label() }}</dd>
            @if ($returnRequest->reason_notes)
                <dt class="text-gray-500">Catatan Koperasi</dt>
                <dd>{{ $returnRequest->reason_notes }}</dd>
            @endif
        </dl>
    </div>

    <div class="bg-white p-4 rounded shadow-sm mb-6">
        <h3 class="font-semibold mb-3">Barang Diretur</h3>
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b">
                    <th class="p-2">Barang</th>
                    <th class="p-2">Jumlah</th>
                    <th class="p-2">Verifikasi Barcode</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($returnRequest->items as $item)
                    <tr class="border-b">
                        <td class="p-2">{{ $item->item->name }}</td>
                        <td class="p-2">{{ $item->return_quantity }}</td>
                        <td class="p-2">
                            @if ($item->barcode_verified)
                                <span class="text-green-700">Terverifikasi ({{ $item->verified_barcode }})</span>
                            @else
                                <span class="text-gray-400">Belum diverifikasi</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white p-4 rounded shadow-sm mb-6">
        <h3 class="font-semibold mb-3">Bukti Foto</h3>
        <div class="flex flex-wrap gap-4">
            @foreach ($returnRequest->evidence as $evidence)
                <div>
                    <p class="text-xs text-gray-500 mb-1">{{ $evidence->purpose === \App\Enums\ReturnEvidencePurpose::ReturnSubmission ? 'Foto Koperasi' : 'Foto Staff' }}</p>
                    <img src="{{ route('returns.evidence', $evidence->uuid) }}" class="h-32 rounded border" />
                </div>
            @endforeach
        </div>
    </div>

    @if ($canVerify)
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">Verifikasi Barang</h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Scan / Input Barcode</label>
                <flux:input type="text" wire:model="scannedBarcode" placeholder="Scan atau ketik barcode..." class="w-full max-w-sm" autofocus />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Terverifikasi</label>
                <flux:input type="number" wire:model="verifiedQuantity" min="1" class="w-32" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto Kondisi Barang (wajib)</label>
                <input type="file" wire:model="staffPhoto" accept="image/*" capture="environment" class="block" />
                @if ($staffPhoto)
                    <img src="{{ $staffPhoto->temporaryUrl() }}" class="mt-2 h-32 rounded border" />
                @endif
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Verifikasi</label>
                <flux:input type="text" wire:model="verificationNotes" placeholder="Catatan..." class="w-full max-w-md" />
            </div>

            <flux:button variant="primary" wire:click="verify">Verifikasi</flux:button>
        </div>
    @endif

    @if ($canSubmitForApproval)
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">Barang Sudah Diverifikasi</h3>
            <p class="text-sm text-gray-600 mb-4">Teruskan retur ini untuk keputusan Kepala Gudang.</p>
            <flux:button variant="primary" wire:click="submitForApproval">Teruskan untuk Keputusan</flux:button>
        </div>
    @endif

    @if ($canApprove)
        <div class="bg-white p-4 rounded shadow-sm mb-6">
            <h3 class="font-semibold mb-3">Keputusan</h3>
            <p class="text-sm text-gray-600 mb-4">Tinjau foto Koperasi dan Staff di atas sebelum memutuskan.</p>

            @if (! $showRejectForm)
                <div class="flex flex-col sm:flex-row gap-3">
                    <flux:button variant="primary" wire:click="approve" wire:confirm="Setujui retur ini? Barang lama akan dicatat sebagai disposed." class="w-full sm:w-auto">
                        Setujui
                    </flux:button>
                    <flux:button variant="danger" wire:click="showReject" class="w-full sm:w-auto">
                        Tolak
                    </flux:button>
                </div>
            @else
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan (wajib)</label>
                    <flux:input type="text" wire:model="rejectReason" placeholder="Jelaskan alasan penolakan..." class="w-full max-w-md" />
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <flux:button variant="danger" wire:click="reject" class="w-full sm:w-auto">Konfirmasi Tolak</flux:button>
                    <flux:button wire:click="$set('showRejectForm', false)" class="w-full sm:w-auto">Batal</flux:button>
                </div>
            @endif
        </div>
    @endif
</div>
