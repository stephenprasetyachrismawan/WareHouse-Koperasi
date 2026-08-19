<?php

namespace App\Domain\Procurement\Exceptions;

use App\Models\PurchaseRequest;
use Exception;
use Illuminate\Support\Collection;

class DuplicatePurchaseRequestException extends Exception
{
    /**
     * @param  Collection<int, PurchaseRequest>  $candidates
     */
    public function __construct(
        public readonly int $inProgressQty,
        public readonly Collection $candidates,
        string $message = 'A duplicate purchase request is already in progress.'
    ) {
        parent::__construct($message);
    }
}
