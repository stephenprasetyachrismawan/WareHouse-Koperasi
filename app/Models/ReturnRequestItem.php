<?php

namespace App\Models;

use Database\Factories\ReturnRequestItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function pickupRequestItem(): BelongsTo
    {
        return $this->belongsTo(PickupRequestItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(ReturnDisposal::class);
    }
}
