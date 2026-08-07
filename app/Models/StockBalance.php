<?php

namespace App\Models;

use Database\Factories\StockBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $warehouse_id
 * @property int $item_id
 * @property int $quantity
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StockBalance extends Model
{
    /** @use HasFactory<StockBalanceFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'quantity',
        'version',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'version' => 'integer',
    ];

    public $timestamps = true;

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

    public function isCritical(): bool
    {
        return $this->quantity < $this->item->minimum_stock;
    }
}
