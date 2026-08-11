<?php

namespace App\Models;

use App\Enums\QualityInspectionCondition;
use App\Enums\QualityInspectionResult;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
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

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function isPass(): bool
    {
        return $this->result === QualityInspectionResult::Pass;
    }
}
