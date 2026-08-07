<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Items;

use App\Actions\Inventory\UpdateItemAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;

    public Item $item;

    public string $name = '';

    public string $description = '';

    public string $unit = '';

    public int $minimum_stock = 0;

    public string $barcode = '';

    public function mount(Item $item): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $membership = $actor->activeMembership();

        if ($item->warehouse_id !== $membership?->warehouse_id) {
            abort(403);
        }

        $this->authorize('update', $item);

        $this->item = $item;
        $this->name = $item->name;
        $this->description = $item->description ?? '';
        $this->unit = $item->unit;
        $this->minimum_stock = $item->minimum_stock;
        $this->barcode = $item->primaryBarcode()?->barcode ?? '';
    }

    public function rules(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        $primaryBarcode = $this->item->primaryBarcode();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('item_barcodes', 'barcode')
                    ->where('warehouse_id', $warehouseId)
                    ->ignore($primaryBarcode?->id),
            ],
        ];
    }

    public function save(): mixed
    {
        $this->authorize('update', $this->item);
        $validated = $this->validate();

        /** @var User $actor */
        $actor = Auth::user();

        $action = new UpdateItemAction;
        $action->execute($actor, $this->item, $validated);

        session()->flash('status', __('Barang master berhasil diperbarui.'));

        return redirect()->route('inventory.items.index');
    }

    public function render(): View
    {
        return view('livewire.inventory.items.edit');
    }
}
