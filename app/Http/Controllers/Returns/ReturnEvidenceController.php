<?php

namespace App\Http\Controllers\Returns;

use App\Http\Controllers\Controller;
use App\Models\ReturnEvidence;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnEvidenceController extends Controller
{
    public function __invoke(ReturnEvidence $returnEvidence): Response|StreamedResponse
    {
        Gate::authorize('view', $returnEvidence->returnRequest);

        abort_unless(Storage::disk('private')->exists($returnEvidence->path), 404);

        return Storage::disk('private')->response(
            $returnEvidence->path,
            headers: ['Content-Type' => $returnEvidence->mime ?? 'application/octet-stream']
        );
    }
}
