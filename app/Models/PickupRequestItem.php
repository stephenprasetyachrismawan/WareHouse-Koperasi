<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupRequestItem extends Model
{
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

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
