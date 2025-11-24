<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Services\ReceiptService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * Download receipt as PDF.
     */
    public function download(Contribution $contribution): Response
    {
        Gate::authorize('view', $contribution);

        return $this->receiptService->downloadPdf($contribution);
    }

    /**
     * View receipt in browser.
     */
    public function view(Contribution $contribution): Response
    {
        Gate::authorize('view', $contribution);

        return $this->receiptService->streamPdf($contribution);
    }

    /**
     * Print receipt (same as view but with print-friendly headers).
     */
    public function print(Contribution $contribution): Response
    {
        Gate::authorize('view', $contribution);

        return $this->receiptService->streamPdf($contribution);
    }
}
