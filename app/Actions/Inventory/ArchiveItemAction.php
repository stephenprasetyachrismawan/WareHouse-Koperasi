<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Item;
use App\Models\User;

final class ArchiveItemAction
{
    public function execute(User $actor, Item $item): Item
    {
        $membership = $actor->activeMembership();
        if (! $membership || $item->warehouse_id !== $membership->warehouse_id) {
            throw new \InvalidArgumentException('Item does not belong to the user active warehouse.');
        }

        $item->update([
            'is_active' => false,
            'archived_at' => now(),
            'updated_by' => $actor->id,
        ]);

        return $item;
    }
}
