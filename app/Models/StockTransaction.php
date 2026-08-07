<?php

namespace App\Models;

use App\Enums\MovementType;
use Database\Factories\StockTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @property int $id
 * @property string $uuid
 * @property int $warehouse_id
 * @property int $item_id
 * @property MovementType $movement_type
 * @property int $signed_quantity
 * @property int $balance_before
 * @property int $balance_after
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string|null $reason
 * @property int|null $performed_by
 * @property string $idempotency_key
 * @property Carbon $occurred_at
 * @property int|null $reversal_of_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
class StockTransaction extends Model
{
    /** @use HasFactory<StockTransactionFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'item_id',
        'movement_type',
        'signed_quantity',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'reason',
        'performed_by',
        'idempotency_key',
        'occurred_at',
        'reversal_of_id',
        'metadata',
    ];

    protected $casts = [
        'movement_type' => MovementType::class,
        'signed_quantity' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StockTransaction $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });

        static::updating(function () {
            throw new RuntimeException('Stock transactions are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('Stock transactions are immutable and cannot be deleted.');
        });
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
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return BelongsTo<StockTransaction, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
