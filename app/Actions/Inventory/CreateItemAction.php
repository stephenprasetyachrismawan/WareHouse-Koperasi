<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateItemAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, array $data): Item
    {
        $membership = $actor->activeMembership();
        if (! $membership) {
            throw new \InvalidArgumentException('Actor must have an active warehouse membership.');
        }

        return DB::transaction(function () use ($data, $membership, $actor) {
            $item = Item::create([
                'uuid' => (string) Str::uuid(),
                'warehouse_id' => $membership->warehouse_id,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'unit' => $data['unit'],
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_active' => true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if (! empty($data['barcode'])) {
                ItemBarcode::create([
                    'warehouse_id' => $membership->warehouse_id,
                    'item_id' => $item->id,
                    'barcode' => $data['barcode'],
                    'is_primary' => true,
                ]);
            }

            return $item;
        });
    }
}
