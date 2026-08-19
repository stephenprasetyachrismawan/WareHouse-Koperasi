<?php

namespace App\View\Components\Dashboard;

use App\Models\Warehouse;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

class Freshness extends Component
{
    public function __construct(
        public Carbon $updatedAt,
        public ?Warehouse $warehouse = null,
    ) {}

    public function displayTime(): string
    {
        $timezone = $this->warehouse->timezone ?? config('app.timezone');

        return $this->updatedAt->clone()->timezone($timezone)->translatedFormat('d M Y, H.i');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.freshness');
    }
}
