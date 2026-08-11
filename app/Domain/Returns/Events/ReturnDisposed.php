<?php

namespace App\Domain\Returns\Events;

use App\Models\ReturnDisposal;
use Illuminate\Foundation\Events\Dispatchable;

class ReturnDisposed
{
    use Dispatchable;

    public function __construct(
        public readonly ReturnDisposal $disposal,
    ) {}
}
