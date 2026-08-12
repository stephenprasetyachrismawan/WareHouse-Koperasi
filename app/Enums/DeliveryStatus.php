<?php

namespace App\Enums;

/**
 * Deliberately small: no larger delivery state machine than this. A
 * permanent failure (invalid/unregistered token, malformed request) must
 * never be retried; a retryable failure may be retried a bounded number of
 * times before it, too, becomes permanent.
 */
enum DeliveryStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case FailedRetryable = 'FAILED_RETRYABLE';
    case FailedPermanent = 'FAILED_PERMANENT';
}
