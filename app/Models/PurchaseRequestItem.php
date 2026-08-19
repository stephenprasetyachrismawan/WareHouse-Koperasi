<?php

namespace App\Models;

use Database\Factories\PurchaseRequestItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read PurchaseRequest|null $purchaseRequest
 * @property-read Item|null $item
 * @property-read int|null $allocated_quantity_sum Only present when loaded via withSum('allocations as allocated_quantity_sum', ...), e.g. ApprovedAllocatablePurchaseRequestsQuery.
 * @property int $remaining_quantity Computed and assigned by ApprovedAllocatablePurchaseRequestsQuery; not a database column.
 * @property-read int|null $total_quantity Only present when loaded via selectRaw('... SUM(requested_quantity) as total_quantity'), e.g. PurchaseRequestInProgressByItemQuery.
 */
class PurchaseRequestItem extends Model
{
    /** @use HasFactory<PurchaseRequestItemFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'requested_quantity',
        'notes',
    ];

    /**
     * @return BelongsTo<PurchaseRequest, $this>
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
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
        return $this->hasMany(PurchaseRequestAllocation::class);
    }
}
