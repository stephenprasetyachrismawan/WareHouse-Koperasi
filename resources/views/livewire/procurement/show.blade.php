<div>
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-semibold text-gray-800">Detail Purchase Request: {{ $purchaseRequest->request_number }}</h2>
        <div class="space-x-2">
            @if($purchaseRequest->status->value === 'DRAFT')
                <button wire:click="submitForApproval" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Submit for Approval</button>
            @endif
            @if($purchaseRequest->status->value === 'WAITING_APPROVAL' && auth()->user()->can('cancel', $purchaseRequest))
                <button wire:click="openCancelModal(false)" class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">Request Cancellation Modal</button>
                <button wire:click="openCancelModal(true)" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Direct Cancel Modal</button>
            @endif
        </div>
    </div>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 flex justify-between">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Utama</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Status: {{ $purchaseRequest->status->label() }}</p>
            </div>
            @if($purchaseRequest->is_duplicate_override)
                <div class="bg-red-50 p-2 rounded text-sm text-red-700 border border-red-200">
                    <strong>Duplicate Override Audit</strong><br>
                    Alasan: {{ $purchaseRequest->duplicate_override_reason }}<br>
                    Waktu: {{ $purchaseRequest->duplicate_overridden_at }}
                </div>
            @endif
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Pemohon</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseRequest->creator?->name }}</dd>
                </div>
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Source & Urgency</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseRequest->source?->value }} / {{ $purchaseRequest->urgency?->value }}</dd>
                </div>
                <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $purchaseRequest->notes ?? '-' }}</dd>
                </div>
                @if($purchaseRequest->status->value === 'CANCELLED')
                <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-red-500">Alasan Batal</dt>
                    <dd class="mt-1 text-sm text-red-900 sm:mt-0 sm:col-span-2">{{ $purchaseRequest->cancellation_reason }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <h3 class="text-xl font-semibold mb-4">Item Breakdown</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Diminta</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($purchaseRequest->items as $item)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item->name ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->requested_quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($purchaseRequest->approvals->isNotEmpty())
    <h3 class="text-xl font-semibold mb-4">Approval History</h3>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-4">
        <ul class="space-y-4">
            @foreach($purchaseRequest->approvals as $approval)
            <li class="border-b pb-2">
                <strong>{{ $approval->user->name }}</strong> - 
                <span class="{{ $approval->status === 'APPROVED' ? 'text-green-600' : 'text-red-600' }}">{{ $approval->status }}</span> 
                ({{ $approval->created_at }})
                @if($approval->notes)
                <p class="text-sm text-gray-600">Catatan: {{ $approval->notes }}</p>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($showCancelModal)
    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="submitCancel">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ $directCancel ? 'Direct Cancel Purchase Request' : 'Request Cancellation' }}
                        </h3>
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Alasan</label>
                            <textarea wire:model="cancellation_reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" rows="3" required></textarea>
                            @error('cancellation_reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Submit
                        </button>
                        <button type="button" wire:click="closeCancelModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
