<div>
    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Approval Inbox</h2>

    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('purchase_requests')" class="{{ $tab === 'purchase_requests' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending Purchase Requests
            </button>
            <button wire:click="switchTab('cancellations')" class="{{ $tab === 'cancellations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Pending Cancellations
            </button>
        </nav>
    </div>

    @if($tab === 'purchase_requests')
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PR Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemohon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items (Stock Info)</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($purchaseRequests as $pr)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $pr->request_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $pr->creator?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <ul class="list-disc pl-4">
                                @foreach($pr->items as $item)
                                    <li>
                                        {{ $item->item->name }} (Diminta: {{ $item->requested_quantity }})
                                        <br>
                                        <span class="text-xs text-blue-600">
                                            Stock: {{ $item->item->stockBalance->quantity ?? 0 }} | Min: {{ $item->item->min_stock_level }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                            <button wire:click="openActionModal('approve_pr', '{{ $pr->id }}')" class="text-green-600 hover:text-green-900">Approve</button>
                            <button wire:click="openActionModal('reject_pr', '{{ $pr->id }}')" class="text-red-600 hover:text-red-900">Reject</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada pending purchase requests.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchaseRequests->links() }}</div>
    @else
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PR Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pemohon Batal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alasan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($cancellations as $cr)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cr->purchaseRequest->request_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $cr->requester?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $cr->reason }}</td>
                        <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                            <button wire:click="openActionModal('approve_cancel', '{{ $cr->id }}')" class="text-green-600 hover:text-green-900">Approve</button>
                            <button wire:click="openActionModal('reject_cancel', '{{ $cr->id }}')" class="text-red-600 hover:text-red-900">Reject</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada pending cancellations.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $cancellations->links() }}</div>
    @endif

    @if($actionModal)
    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="submitAction">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            @if($actionType === 'approve_pr') Approve Purchase Request
                            @elseif($actionType === 'reject_pr') Reject Purchase Request
                            @elseif($actionType === 'approve_cancel') Approve Cancellation
                            @else Reject Cancellation
                            @endif
                        </h3>
                        <div class="mt-2">
                            <label class="block text-sm font-medium text-gray-700">Notes / Reason (Wajib jika reject)</label>
                            <textarea wire:model="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" rows="3" {{ in_array($actionType, ['reject_pr', 'reject_cancel']) ? 'required' : '' }}></textarea>
                            @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Submit
                        </button>
                        <button type="button" wire:click="closeActionModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
