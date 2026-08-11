<?php

namespace App\Domain\Returns\Events;

use App\Models\Approval;
use App\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;

class ReturnApproved
{
    use Dispatchable;

    public function __construct(
        public readonly ReturnRequest $returnRequest,
        public readonly Approval $approval,
    ) {}
}
