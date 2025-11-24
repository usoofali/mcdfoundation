<?php

namespace App\Services;

use App\Models\Contribution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    /**
     * Generate PDF for a contribution receipt.
     */
    public function generatePdf(Contribution $contribution): \Barryvdh\DomPDF\PDF
    {
        $contribution->load(['member', 'contributionPlan', 'collector', 'uploader', 'verifier']);

        $pdf = Pdf::loadView('receipts.contribution', [
            'contribution' => $contribution,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('margin-top', 12);
        $pdf->setOption('margin-bottom', 12);
        $pdf->setOption('margin-left', 12);
        $pdf->setOption('margin-right', 12);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', false);

        return $pdf;
    }

    /**
     * Generate PDF and return as download response.
     */
    public function downloadPdf(Contribution $contribution): Response
    {
        $pdf = $this->generatePdf($contribution);

        $filename = "RECEIPT_{$contribution->receipt_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate PDF and return as stream response (for viewing in browser).
     */
    public function streamPdf(Contribution $contribution): Response
    {
        $pdf = $this->generatePdf($contribution);

        $filename = "RECEIPT_{$contribution->receipt_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Generate PDF and save to storage.
     */
    public function savePdf(Contribution $contribution): string
    {
        $pdf = $this->generatePdf($contribution);

        $filename = "receipts/RECEIPT_{$contribution->receipt_number}.pdf";
        $content = $pdf->output();

        Storage::disk('public')->put($filename, $content);

        return $filename;
    }
}
