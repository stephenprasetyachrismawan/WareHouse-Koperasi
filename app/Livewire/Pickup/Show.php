<?php

namespace App\Livewire\Pickup;

use App\Models\PickupRequest;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public PickupRequest $pickupRequest;

    public function mount(PickupRequest $pickupRequest): void
    {
        $this->pickupRequest = $pickupRequest->load('items.item', 'approvals.requester', 'approvals.approver', 'user');
    }

    public function render(): View
    {
        return view('livewire.pickup.show');
    }
}
