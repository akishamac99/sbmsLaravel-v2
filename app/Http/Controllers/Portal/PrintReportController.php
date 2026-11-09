<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReportFilterRequest;
use App\Models\SystemLog;
use App\Services\Reports\ReportSummary;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC43 Print Report. Shows a print-ready page; the browser's print dialog does the printing. */
class PrintReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, ReportSummary $summary): View
    {
        $user = Auth::guard('staff')->user();
        SystemLog::record($user->role_slug, (int) $user->getKey(), 'print_report', 'Opened the print view of the report');

        return view('portal.report-print', $summary->build($summary->filteredBookings($request->filters()), $request->filters()) + ['role' => $user->role_slug, 'filters' => $request->filters()]);
    }
}
