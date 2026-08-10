<?php

namespace App\Domain\Procurement\Events;

use App\Models\CancellationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CancellationRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CancellationRequest $cancellationRequest) {}
}
