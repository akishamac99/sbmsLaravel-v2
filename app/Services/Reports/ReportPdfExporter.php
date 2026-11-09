<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;

/** Turns report figures into a PDF file's bytes. */
final class ReportPdfExporter
{
    public function render(array $report): string
    {
        // Remote files are switched off so a report can never pull in outside content.
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->loadHtml(view('portal.report-document', $report)->render());
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return $pdf->output();
    }
}
