<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Turns report figures into an Excel (.xlsx) file's bytes. */
final class ReportSpreadsheetExporter
{
    public function render(array $report): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Summary');

        $summary = [
            ['Janet\'s Angel Beauty Salon - Operational Report'],
            ['Generated', $report['generatedAt']],
            ['Filters', $report['filterSummary']],
            [],
            ['Total bookings', $report['totalBookings']],
            ['Pending bookings', $report['pendingBookings']],
            ['Completed bookings', $report['completedBookings']],
            ['Completed revenue (PHP)', $report['completedRevenue']],
            ['Feedback received', $report['feedbackCount']],
        ];
        $this->fill($sheet, $summary);

        $services = $book->createSheet()->setTitle('Revenue by Service');
        $rows = [['Service', 'Category', 'Bookings', 'Revenue (PHP)']];
        foreach ($report['byService'] as $row) {
            $rows[] = [$row->serviceName, $row->category ?? 'Uncategorized', $row->bookings_count, (float) $row->total_revenue];
        }
        $this->fill($services, $rows);

        $team = $book->createSheet()->setTitle('Staff Performance');
        $rows = [['Staff member', 'Completed bookings', 'Feedback', 'Average rating']];
        foreach ($report['staffPerformance'] as $row) {
            $rows[] = [$row->staff_name, $row->completed, $row->feedback_count, $row->avg_rating ? round((float) $row->avg_rating, 1) : null];
        }
        $this->fill($team, $rows);

        $path = tempnam(sys_get_temp_dir(), 'sbms');
        try {
            (new Xlsx($book))->save($path);

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
            $book->disconnectWorksheets();
        }
    }

    private function fill($sheet, array $rows): void
    {
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $cell = $sheet->getCell([$c + 1, $r + 1]);
                // Text goes in as plain text, never as a formula, so a name like "=cmd()" cannot run in Excel.
                is_string($value) ? $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) : $cell->setValue($value);
            }
        }
    }
}
