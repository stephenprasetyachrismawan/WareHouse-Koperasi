<?php

namespace App\Models;

use Database\Factories\PickupRequestItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read PickupRequest|null $pickupRequest
 * @property-read Item|null $item
 * @property int $eligible_quantity Computed and assigned by EligibleReturnItemsQuery; not a database column.
 */
class PickupRequestItem extends Model
{
    /** @use HasFactory<PickupRequestItemFactory> */
    use HasFactory;

    protected $fillable = [
        'pickup_request_id',
        'item_id',
        'requested_quantity',
        'fulfilled_quantity',
        'shortage_quantity',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'fulfilled_quantity' => 'integer',
        'shortage_quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<PickupRequest, $this>
     */
    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<ReturnRequestItem, $this>
     */
    public function returnRequestItems(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
}
