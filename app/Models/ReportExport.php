<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportExportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    use HasUuids;

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'report_type',
        'filters',
        'path',
        'filename',
        'status',
        'error_message',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'status' => ReportExportStatus::class,
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
