<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;

class DownloadReportExportController
{
    public function __invoke(ReportExport $reportExport): mixed
    {
        abort_unless(auth()->user()?->can('download', $reportExport), 403);
        abort_if($reportExport->expires_at?->isPast() ?? true, 410);
        abort_unless($reportExport->path !== null && Storage::disk('private')->exists($reportExport->path), 404);

        return Storage::disk('private')->download($reportExport->path, $reportExport->filename ?? 'report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
