<?php

namespace App\Livewire\Pickup;

use App\Actions\Pickup\CreatePickupRequestAction;
use App\Actions\Pickup\SubmitPickupRequestAction;
use App\Domain\Pickup\ValueObjects\PickupRequestInput;
use App\Domain\Pickup\ValueObjects\PickupRequestItemInput;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Create extends Component
{
    public $search = '';

    public $items = []; // Selected items

    public $notes = '';

    public function addItem($itemId)
    {
        $item = Item::with('stockBalance')->find($itemId);
        if ($item) {
            foreach ($this->items as $i => $existing) {
                if ($existing['id'] == $item->id) {
                    $this->items[$i]['quantity']++;

                    return;
                }
            }
            $this->items[] = [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => 1,
                'notes' => '',
            ];
        }
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function submit(CreatePickupRequestAction $createAction, SubmitPickupRequestAction $submitAction)
    {
        $this->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $user = Auth::user();
            $warehouseId = session('tenant_id', 1);

            $itemInputs = array_map(function ($item) {
                return new PickupRequestItemInput($item['id'], $item['quantity'], $item['notes'] ?? null);
            }, $this->items);

            $input = new PickupRequestInput($warehouseId, $user->id, $this->notes, $itemInputs);

            $request = $createAction->execute($user, $input);
            $submitAction->execute($user, $request);

            session()->flash('status', 'Request successfully created and submitted!');

            return redirect()->route('pickup.my-requests');
        } catch (\Exception $e) {
            Log::error('Pickup Submit Error: '.$e->getMessage());
            $this->addError('general', 'Failed to create request: '.$e->getMessage());
        }
    }

    public function render()
    {
        $searchResults = [];
        if (strlen($this->search) >= 2) {
            $searchResults = Item::with('stockBalance')
                ->where('name', 'like', '%'.$this->search.'%')
                ->limit(5)->get();
        }

        return view('livewire.pickup.create', [
            'searchResults' => $searchResults,
        ]);
    }
}
