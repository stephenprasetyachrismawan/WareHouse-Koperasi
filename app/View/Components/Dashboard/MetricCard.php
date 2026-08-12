<?php

namespace App\View\Components\Dashboard;

use App\Domain\Dashboard\ValueObjects\DashboardMetric;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MetricCard extends Component
{
    public function __construct(
        public DashboardMetric $metric,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.metric-card');
    }
}
