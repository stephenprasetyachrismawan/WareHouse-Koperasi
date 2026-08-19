<?php

namespace App\Models;

use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use Database\Factories\QualityInspectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read GoodsReceiptItem|null $goodsReceiptItem
 * @property-read User|null $inspector
 */
class QualityInspection extends Model
{
    /** @use HasFactory<QualityInspectionFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'warehouse_id',
        'goods_receipt_item_id',
        'result',
        'condition',
        'notes',
        'evidence_path',
        'evidence_mime',
        'inspected_by',
        'inspected_at',
        'stock_transaction_id',
    ];

    protected $casts = [
        'result' => QualityInspectionResult::class,
        'condition' => QualityInspectionCondition::class,
        'inspected_at' => 'datetime',
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
     * @return BelongsTo<GoodsReceiptItem, $this>
     */
    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * @return BelongsTo<StockTransaction, $this>
     */
    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function isPass(): bool
    {
        return $this->result === QualityInspectionResult::Pass;
    }
}
