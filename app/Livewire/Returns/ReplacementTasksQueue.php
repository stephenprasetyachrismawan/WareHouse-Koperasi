<?php

namespace App\Livewire\Returns;

use App\Domain\Returns\Queries\PendingReturnReplacementsQuery;
use App\Models\ReturnRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReplacementTasksQueue extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', ReturnRequest::class);
    }

    public function render(PendingReturnReplacementsQuery $query): View
    {
        $warehouseId = Auth::user()->activeWarehouse()?->id;

        return view('livewire.returns.replacement-tasks-queue', [
            'returns' => $query->execute($warehouseId),
        ]);
    }
}
