<?php

namespace App\Models;

use Database\Factories\ReturnRequestItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read ReturnRequest|null $returnRequest
 * @property-read Item|null $item
 */
class ReturnRequestItem extends Model
{
    /** @use HasFactory<ReturnRequestItemFactory> */
    use HasFactory;

    protected $fillable = [
        'return_request_id',
        'pickup_request_item_id',
        'item_id',
        'return_quantity',
        'barcode_verified',
        'verified_barcode',
        'notes',
    ];

    protected $casts = [
        'barcode_verified' => 'boolean',
    ];

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<PickupRequestItem, $this>
     */
    public function pickupRequestItem(): BelongsTo
    {
        return $this->belongsTo(PickupRequestItem::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasOne<ReturnDisposal, $this>
     */
    public function disposal(): HasOne
    {
        return $this->hasOne(ReturnDisposal::class);
    }
}
