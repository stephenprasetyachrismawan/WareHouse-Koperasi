<?php

namespace App\Domain\Dashboard\ValueObjects;

/**
 * A single actionable dashboard KPI. Dashboards are read models — this
 * object never carries enough to mutate anything, only what's needed to
 * display a count and link to the already-authorized, already-policy-gated
 * list that shows the underlying records.
 */
readonly class DashboardMetric
{
    public function __construct(
        public string $label,
        public int $value,
        public ?string $route = null,
        public string $severity = 'neutral', // neutral|info|warning|critical
        public ?string $emptyStateText = null,
    ) {}
}
