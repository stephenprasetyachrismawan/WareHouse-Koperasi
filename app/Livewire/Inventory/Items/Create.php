<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Items;

use App\Actions\Inventory\CreateItemAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public string $unit = 'PCS';

    public int $minimum_stock = 10;

    public string $barcode = '';

    public function mount(): void
    {
        $this->authorize('create', Item::class);
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $actor */
        $actor = Auth::user();
        $warehouseId = $actor->activeMembership()?->warehouse_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'code')->where('warehouse_id', $warehouseId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit' => ['required', 'string', 'max:20'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('item_barcodes', 'barcode')->where('warehouse_id', $warehouseId),
            ],
        ];
    }

    public function save(): mixed
    {
        $this->authorize('create', Item::class);
        $validated = $this->validate();

        /** @var User $actor */
        $actor = Auth::user();

        $action = new CreateItemAction;
        $action->execute($actor, $validated);

        session()->flash('status', __('Barang master berhasil ditambahkan.'));

        return redirect()->route('inventory.items.index');
    }

    public function render(): View
    {
        return view('livewire.inventory.items.create');
    }
}
