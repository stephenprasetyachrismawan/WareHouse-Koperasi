<?php

declare(strict_types=1);

namespace App\Domain\Reports\ValueObjects;

use App\Enums\OperationalReportType;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

readonly class ReportFilters
{
    public function __construct(
        public OperationalReportType $type,
        public ?int $itemId = null,
        public ?string $status = null,
        public ?string $source = null,
        public ?string $movementType = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
    ) {}

    public static function fromInput(
        OperationalReportType $type,
        ?int $itemId,
        ?string $status,
        ?string $source,
        ?string $movementType,
        ?string $from,
        ?string $to,
        string $timezone,
    ): self {
        return new self(
            type: $type,
            itemId: $itemId,
            status: self::nullable($status),
            source: self::nullable($source),
            movementType: self::nullable($movementType),
            from: self::boundary($from, $timezone, false),
            to: self::boundary($to, $timezone, true),
        );
    }

    private static function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function boundary(?string $date, string $timezone, bool $endOfDay): ?DateTimeImmutable
    {
        $date = self::nullable($date);

        if ($date === null) {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

            if ($parsed->format('Y-m-d') !== $date) {
                return null;
            }

            return ($endOfDay ? $parsed->endOfDay() : $parsed->startOfDay())->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
