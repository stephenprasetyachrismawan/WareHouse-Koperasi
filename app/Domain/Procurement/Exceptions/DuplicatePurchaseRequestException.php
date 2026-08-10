<?php

namespace App\Domain\Procurement\Exceptions;

use Exception;
use Illuminate\Support\Collection;

class DuplicatePurchaseRequestException extends Exception
{
    public function __construct(
        public readonly int $inProgressQty,
        public readonly Collection $candidates,
        string $message = 'A duplicate purchase request is already in progress.'
    ) {
        parent::__construct($message);
    }
}
