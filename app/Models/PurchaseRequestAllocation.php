<?php

namespace App\Models;

use Database\Factories\PurchaseRequestAllocationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestAllocation extends Model
{
    /** @use HasFactory<PurchaseRequestAllocationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'purchase_request_item_id',
        'purchase_request_group_id',
        'purchase_order_item_id',
        'allocated_quantity',
        'allocated_by',
    ];

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<PurchaseRequestItem, $this>
     */
    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    /**
     * @return BelongsTo<PurchaseRequestGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestGroup::class, 'purchase_request_group_id');
    }

    /**
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
