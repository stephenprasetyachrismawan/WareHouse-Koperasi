<?php

namespace App\Models;

use Database\Factories\ReturnDisposalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable record that an approved Return's item has been disposed.
 * Deliberately never mutates StockBalance: the item was already removed
 * from stock at Pickup time, so disposal is an audit/traceability record
 * only, not an inventory movement.
 */
class ReturnDisposal extends Model
{
    /** @use HasFactory<ReturnDisposalFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'return_request_id',
        'return_request_item_id',
        'warehouse_id',
        'item_id',
        'quantity',
        'disposed_by',
        'disposed_at',
    ];

    protected $casts = [
        'disposed_at' => 'datetime',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<ReturnRequestItem, $this>
     */
    public function returnRequestItem(): BelongsTo
    {
        return $this->belongsTo(ReturnRequestItem::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function disposedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }
}
