<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read PurchaseOrderStatus $status
 * @property-read Supplier|null $supplier
 * @property-read GoodsReceipt|null $goodsReceipt
 */
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'supplier_id',
        'po_number',
        'status',
        'created_by',
        'sent_by',
        'sent_at',
        'notes',
        'purchase_request_group_id',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'sent_at' => 'datetime',
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
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * @return BelongsTo<PurchaseRequestGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestGroup::class, 'purchase_request_group_id');
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * @return HasOne<GoodsReceipt, $this>
     */
    public function goodsReceipt(): HasOne
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    /**
     * @return HasManyThrough<PurchaseRequestAllocation, PurchaseOrderItem, $this>
     */
    public function allocations(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseRequestAllocation::class, PurchaseOrderItem::class);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     * @return Builder<PurchaseOrder>
     */
    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     * @return Builder<PurchaseOrder>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PurchaseOrderStatus::Draft->value);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     * @return Builder<PurchaseOrder>
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PurchaseOrderStatus::SentToSupplier->value,
            PurchaseOrderStatus::GoodsReceived->value,
            PurchaseOrderStatus::Completed->value,
        ]);
    }
}
