<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\QualityInspection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityInspectionEvidenceController extends Controller
{
    public function __invoke(QualityInspection $qualityInspection): Response|StreamedResponse
    {
        Gate::authorize('view', $qualityInspection);

        abort_unless($qualityInspection->evidence_path, 404);
        abort_unless(Storage::disk('private')->exists($qualityInspection->evidence_path), 404);

        return Storage::disk('private')->response(
            $qualityInspection->evidence_path,
            headers: ['Content-Type' => $qualityInspection->evidence_mime ?? 'application/octet-stream']
        );
    }
}
