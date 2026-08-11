<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
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

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestGroup::class, 'purchase_request_group_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipt(): HasOne
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    public function allocations(): HasManyThrough
    {
        return $this->hasManyThrough(PurchaseRequestAllocation::class, PurchaseOrderItem::class);
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PurchaseOrderStatus::Draft->value);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PurchaseOrderStatus::SentToSupplier->value,
            PurchaseOrderStatus::GoodsReceived->value,
            PurchaseOrderStatus::Completed->value,
        ]);
    }
}
