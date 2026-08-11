<?php

namespace App\Livewire\Returns;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalQueue extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', ReturnRequest::class);
    }

    public function render()
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $returns = ReturnRequest::forWarehouse($warehouseId)
            ->where('status', ReturnStatus::WaitingApproval->value)
            ->with(['items.item', 'cooperativeMembership.user', 'evidence'])
            ->oldest('waiting_approval_at')
            ->paginate(10);

        return view('livewire.returns.approval-queue', [
            'returns' => $returns,
        ]);
    }
}
