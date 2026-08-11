<?php

namespace App\Domain\Procurement\Events;

use App\Models\QualityInspection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QualityInspectionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public QualityInspection $inspection,
    ) {}
}
