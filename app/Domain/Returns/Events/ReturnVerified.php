<?php

namespace App\Domain\Returns\Events;

use App\Models\ReturnRequest;
use Illuminate\Foundation\Events\Dispatchable;

class ReturnVerified
{
    use Dispatchable;

    public function __construct(
        public readonly ReturnRequest $returnRequest
    ) {}
}
