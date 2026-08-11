<?php

namespace App\Models;

use App\Enums\ReturnEvidencePurpose;
use Database\Factories\ReturnEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnEvidence extends Model
{
    /** @use HasFactory<ReturnEvidenceFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'return_request_id',
        'warehouse_id',
        'purpose',
        'uploaded_by',
        'path',
        'mime',
    ];

    protected $casts = [
        'purpose' => ReturnEvidencePurpose::class,
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
