<?php

namespace App\Livewire\Returns;

use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyReturns extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', ReturnRequest::class);
    }

    public function render()
    {
        $membershipId = Auth::user()->activeMembership()?->id;

        $returns = ReturnRequest::where('cooperative_membership_id', $membershipId)
            ->with(['items.item'])
            ->latest()
            ->paginate(10);

        return view('livewire.returns.my-returns', [
            'returns' => $returns,
        ]);
    }
}
