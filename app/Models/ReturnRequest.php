<?php

namespace App\Models;

use App\Enums\ReturnFaultAttribution;
use App\Enums\ReturnReasonCode;
use App\Enums\ReturnStatus;
use Database\Factories\ReturnRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReturnRequest extends Model
{
    /** @use HasFactory<ReturnRequestFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'cooperative_membership_id',
        'pickup_request_id',
        'return_number',
        'status',
        'reason_code',
        'reason_notes',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'verification_notes',
        'waiting_approval_at',
        'version',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'decision_notes',
        'fault_attribution',
        'fault_rule_version',
        'disposed_at',
        'replacement_pickup_request_id',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'reason_code' => ReturnReasonCode::class,
        'fault_attribution' => ReturnFaultAttribution::class,
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'waiting_approval_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disposed_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cooperativeMembership(): BelongsTo
    {
        return $this->belongsTo(WarehouseMembership::class, 'cooperative_membership_id');
    }

    public function pickupRequest(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function replacementPickup(): BelongsTo
    {
        return $this->belongsTo(PickupRequest::class, 'replacement_pickup_request_id');
    }

    public function replacementPurchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(ReturnDisposal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ReturnEvidence::class);
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
