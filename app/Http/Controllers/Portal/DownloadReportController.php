<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReportFilterRequest;
use App\Models\SystemLog;
use App\Services\Reports\ReportPdfExporter;
use App\Services\Reports\ReportSpreadsheetExporter;
use App\Services\Reports\ReportSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC33 / UC42 Download Report. One implementation serves both use cases (PDF or Excel). */
class DownloadReportController extends Controller
{
    private const FORMATS = [
        'pdf' => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function choose(ReportFilterRequest $request): View
    {
        return view('portal.download-report', [
            'role' => Auth::guard('staff')->user()->role_slug,
            'filters' => $request->filters(),
        ]);
    }

    public function download(ReportFilterRequest $request, ReportSummary $summary, ReportPdfExporter $pdf, ReportSpreadsheetExporter $xlsx)
    {
        $user = Auth::guard('staff')->user();

        $formatCheck = validator(
            $request->only('format'),
            ['format' => ['required', 'in:pdf,xlsx']],
            ['format.required' => 'Select a file format.', 'format.in' => 'Unsupported format selected.']
        );

        if ($formatCheck->fails()) {
            return back()->withErrors($formatCheck)->withInput();
        }

        $format = $request->input('format');
        $filters = $request->filters();

        try {
            $report = $summary->build($summary->filteredBookings($filters), $filters);
            $content = $format === 'pdf' ? $pdf->render($report) : $xlsx->render($report);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The report file could not be generated. Please try again.');
        }

        SystemLog::record($user->role_slug, (int) $user->getKey(), 'download_report', "Downloaded report as {$format}");

        return response($content, 200, [
            'Content-Type' => self::FORMATS[$format],
            'Content-Disposition' => 'attachment; filename="sbms-report-'.now()->format('Ymd-His').'.'.$format.'"',
            'Content-Length' => (string) strlen($content),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
