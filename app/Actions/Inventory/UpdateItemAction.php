<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateItemAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Item $item, array $data): Item
    {
        $membership = $actor->activeMembership();
        if (! $membership || $item->warehouse_id !== $membership->warehouse_id) {
            throw new \InvalidArgumentException('Item does not belong to the user active warehouse.');
        }

        return DB::transaction(function () use ($item, $data, $membership, $actor) {
            $item->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'unit' => $data['unit'],
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'updated_by' => $actor->id,
            ]);

            if (isset($data['barcode']) && $data['barcode'] !== '') {
                $primaryBarcode = $item->barcodes()->where('is_primary', true)->first();
                if ($primaryBarcode) {
                    $primaryBarcode->update(['barcode' => $data['barcode']]);
                } else {
                    ItemBarcode::create([
                        'warehouse_id' => $membership->warehouse_id,
                        'item_id' => $item->id,
                        'barcode' => $data['barcode'],
                        'is_primary' => true,
                    ]);
                }
            }

            return $item;
        });
    }
}
