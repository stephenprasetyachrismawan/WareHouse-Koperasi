<?php

declare(strict_types=1);

namespace App\Domain\Reports\ValueObjects;

readonly class OperationalReportRow
{
    /**
     * @param  array<string, int|string|null>  $values
     */
    public function __construct(public array $values) {}
}
