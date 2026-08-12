<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Domain\Reports\Queries\OperationalReportQuery;
use App\Domain\Reports\ValueObjects\OperationalReportRow;
use App\Domain\Reports\ValueObjects\ReportFilters;
use App\Enums\OperationalReportType;
use App\Enums\ReportExportStatus;
use App\Jobs\GenerateOperationalReportExportJob;
use App\Models\ReportExport;
use App\Models\Warehouse;
use DateTimeZone;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CreateOperationalReportExportAction
{
    public function __construct(private readonly OperationalReportQuery $reports) {}

    public function create(Warehouse $warehouse, int $userId, ReportFilters $filters): ReportExport
    {
        $payload = $this->payload($warehouse, $filters);
        $existing = ReportExport::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('user_id', $userId)
            ->where('report_type', $filters->type->value)
            ->whereIn('status', [ReportExportStatus::Pending, ReportExportStatus::Completed])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->get()
            ->first(function (ReportExport $export) use ($payload): bool {
                if ($export->filters !== $payload) {
                    return false;
                }

                return $export->status === ReportExportStatus::Pending
                    || ! ($export->expires_at?->isPast() ?? true);
            });

        if ($existing !== null) {
            return $existing;
        }

        $export = ReportExport::create([
            'warehouse_id' => $warehouse->id,
            'user_id' => $userId,
            'report_type' => $filters->type->value,
            'filters' => $payload,
            'status' => ReportExportStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);

        GenerateOperationalReportExportJob::dispatch($export->id, $userId, $warehouse->id, $payload);

        return $export->refresh();
    }

    public function generate(ReportExport $export, Warehouse $warehouse, ReportFilters $filters): void
    {
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create export stream.');
        }

        fputcsv($handle, $this->headers($filters->type));

        foreach ($this->reports->export($warehouse, $filters) as $row) {
            fputcsv($handle, $this->csvValues($row, $filters->type));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $path = 'reports/'.$warehouse->id.'/'.Str::uuid().'.csv';
        Storage::disk('private')->put($path, $csv);

        $export->update([
            'path' => $path,
            'filename' => 'laporan-'.$filters->type->value.'-'.$warehouse->code.'-'.now($warehouse->timezone)->format('Y-m-d').'.csv',
            'status' => ReportExportStatus::Completed,
            'generated_at' => now(),
            'error_message' => null,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Warehouse $warehouse, ReportFilters $filters): array
    {
        return [
            'type' => $filters->type->value,
            'item_id' => $filters->itemId,
            'status' => $filters->status,
            'source' => $filters->source,
            'movement_type' => $filters->movementType,
            'from' => $filters->from?->setTimezone(new DateTimeZone($warehouse->timezone))->format('Y-m-d'),
            'to' => $filters->to?->setTimezone(new DateTimeZone($warehouse->timezone))->format('Y-m-d'),
        ];
    }

    /** @return list<string> */
    private function headers(OperationalReportType $type): array
    {
        return match ($type) {
            OperationalReportType::Stock => ['Kode', 'Item', 'Saldo', 'Minimum', 'Kritis'],
            OperationalReportType::StockMovements => ['Waktu', 'Item', 'Tipe', 'Jumlah', 'Sumber', 'Referensi', 'Aktor'],
            OperationalReportType::PurchaseRequests => ['Nomor PR', 'Item', 'Jumlah', 'Sumber', 'Urgensi', 'Status', 'Dibuat', 'Terminal'],
            OperationalReportType::PurchaseOrders => ['Nomor PO', 'Supplier', 'Item', 'Jumlah', 'Status', 'Dibuat', 'Dikirim', 'Diterima'],
            OperationalReportType::Pickups => ['Nomor Pickup', 'Koperasi', 'Item', 'Jumlah', 'Status', 'Diminta', 'Siap/Jadwal', 'Selesai'],
            OperationalReportType::Returns => ['Nomor Return', 'Koperasi', 'Item', 'Jumlah', 'Status', 'Diajukan', 'Keputusan', 'Replacement'],
            OperationalReportType::QualityControl => ['Nomor Receipt', 'Nomor PO', 'Supplier', 'Item', 'Hasil QC', 'Inspektor', 'Diperiksa'],
        };
    }

    /** @return list<int|string|null> */
    private function csvValues(OperationalReportRow $row, OperationalReportType $type): array
    {
        $keys = match ($type) {
            OperationalReportType::Stock => ['code', 'item', 'quantity', 'minimum_stock', 'critical'],
            OperationalReportType::StockMovements => ['occurred_at', 'item', 'movement_type', 'signed_quantity', 'source', 'reference', 'actor'],
            OperationalReportType::PurchaseRequests => ['request_number', 'item', 'quantity', 'source', 'urgency', 'status', 'created_at', 'terminal_at'],
            OperationalReportType::PurchaseOrders => ['po_number', 'supplier', 'item', 'quantity', 'status', 'created_at', 'sent_at', 'received'],
            OperationalReportType::Pickups => ['request_number', 'koperasi', 'item', 'quantity', 'status', 'requested_at', 'scheduled_at', 'completed_at'],
            OperationalReportType::Returns => ['return_number', 'koperasi', 'item', 'quantity', 'status', 'submitted_at', 'decision_at', 'replacement'],
            OperationalReportType::QualityControl => ['receipt_number', 'po_number', 'supplier', 'item', 'result', 'inspector', 'inspected_at'],
        };

        return array_map(fn (string $key): int|string|null => $row->values[$key] ?? null, $keys);
    }
}
