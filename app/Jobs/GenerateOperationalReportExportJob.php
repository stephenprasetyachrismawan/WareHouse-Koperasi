<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Reports\CreateOperationalReportExportAction;
use App\Domain\Reports\ValueObjects\ReportFilters;
use App\Enums\OperationalReportType;
use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateOperationalReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<string, mixed> $filters */
    public function __construct(
        public readonly int $exportId,
        public readonly int $userId,
        public readonly int $warehouseId,
        public readonly array $filters,
    ) {}

    public function handle(CreateOperationalReportExportAction $action): void
    {
        $export = ReportExport::query()
            ->whereKey($this->exportId)
            ->where('user_id', $this->userId)
            ->where('warehouse_id', $this->warehouseId)
            ->firstOrFail();
        $warehouse = Warehouse::query()->whereKey($this->warehouseId)->firstOrFail();

        $action->generate($export, $warehouse, ReportFilters::fromInput(
            type: OperationalReportType::from((string) $this->filters['type']),
            itemId: isset($this->filters['item_id']) ? (int) $this->filters['item_id'] : null,
            status: $this->filters['status'] ?? null,
            source: $this->filters['source'] ?? null,
            movementType: $this->filters['movement_type'] ?? null,
            from: $this->filters['from'] ?? null,
            to: $this->filters['to'] ?? null,
            timezone: $warehouse->timezone,
        ));
    }

    public function failed(Throwable $exception): void
    {
        ReportExport::query()
            ->whereKey($this->exportId)
            ->where('user_id', $this->userId)
            ->where('warehouse_id', $this->warehouseId)
            ->update([
                'status' => ReportExportStatus::Failed,
                'error_message' => 'Export generation failed.',
            ]);
    }
}
