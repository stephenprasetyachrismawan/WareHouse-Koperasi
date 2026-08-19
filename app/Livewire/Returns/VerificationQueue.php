<?php

namespace App\Livewire\Returns;

use App\Enums\ReturnStatus;
use App\Models\ReturnRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VerificationQueue extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', ReturnRequest::class);
    }

    public function render(): View
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        $returns = ReturnRequest::forWarehouse($warehouseId)
            ->where('status', ReturnStatus::Submitted->value)
            ->with(['items.item', 'cooperativeMembership.user'])
            ->latest('submitted_at')
            ->paginate(10);

        return view('livewire.returns.verification-queue', [
            'returns' => $returns,
        ]);
    }
}
