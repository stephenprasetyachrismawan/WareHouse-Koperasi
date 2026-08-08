<?php

namespace App\Livewire\Pickup;

use App\Models\PickupRequest;
use Livewire\Component;

class Show extends Component
{
    public PickupRequest $pickupRequest;

    public function mount(PickupRequest $pickupRequest)
    {
        $this->pickupRequest = $pickupRequest->load('items.item', 'approvals.actor', 'user');
    }

    public function render()
    {
        return view('livewire.pickup.show');
    }
}
