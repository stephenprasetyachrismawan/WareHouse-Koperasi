<?php

namespace App\Models;

use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read Item|null $item
 */
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'ordered_quantity',
        'unit_cost',
        'notes',
    ];

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<PurchaseRequestAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PurchaseRequestAllocation::class, 'purchase_order_item_id');
    }

    /**
     * @return HasOne<GoodsReceiptItem, $this>
     */
    public function goodsReceiptItem(): HasOne
    {
        return $this->hasOne(GoodsReceiptItem::class);
    }
}
