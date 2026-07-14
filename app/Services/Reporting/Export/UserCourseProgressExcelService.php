<?php

namespace App\Services\Reporting\Export;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Styled 2-sheet Excel export for the User Course Progress report.
 * Sheet 1: Completed Courses (KPI)  — includes Completion Date column
 * Sheet 2: Non-Completed Courses (KPI) — no Completion Date column
 *
 * Faithful reproduction of the old nvt-courses ExcelExportService (same headers,
 * colours, widths and layout) so the client's file is unchanged.
 */
class UserCourseProgressExcelService
{
    /**
     * @param Collection<int, array<string, mixed>> $rows Enriched rows from
     *        UserCourseProgressReportService::buildRows()
     */
    public function export(Collection $rows): BinaryFileResponse
    {
        $completed    = $rows->filter(fn ($r) => ! empty($r['is_completed']))->values();
        $nonCompleted = $rows->filter(fn ($r) => empty($r['is_completed']))->values();

        $spreadsheet = new Spreadsheet();

        $completedSheet = $spreadsheet->getActiveSheet();
        $completedSheet->setTitle('Completed Courses (KPI)');
        $this->formatWorksheet($completedSheet, $completed, 'Completed Courses (KPI)');

        $nonCompletedSheet = $spreadsheet->createSheet();
        $nonCompletedSheet->setTitle('Non-Completed Courses (KPI)');
        $this->formatWorksheet($nonCompletedSheet, $nonCompleted, 'Non-Completed Courses (KPI)');

        $filename = 'user_course_progress_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function formatWorksheet(Worksheet $sheet, Collection $data, string $sheetName): void
    {
        $isCompletedSheet = $sheetName === 'Completed Courses (KPI)';

        if ($isCompletedSheet) {
            $headers = [
                'A' => 'Employee Name',
                'B' => 'Department',
                'C' => 'Course type',
                'D' => 'Course Name',
                'E' => 'Course Beginning Date',
                'F' => 'Completion Status',
                'G' => 'DaysOverdue',
                'H' => 'progress%',
                'I' => 'Start Course',
                'J' => 'Completion Date',
                'K' => 'Overall Learning Score (0-100)',
                'L' => 'Score Band (Excellent / Good / Needs Attention)',
                'M' => 'Compliance Status (Compliant/At Risk/Non-Compliant)',
            ];
        } else {
            $headers = [
                'A' => 'Employee Name',
                'B' => 'Department',
                'C' => 'Course type',
                'D' => 'Course Name',
                'E' => 'Course Beginning Date',
                'F' => 'Completion Status',
                'G' => 'DaysOverdue',
                'H' => 'progress%',
                'I' => 'Start Course',
                'J' => 'Overall Learning Score (0-100)',
                'K' => 'Score Band (Excellent / Good / Needs Attention)',
                'L' => 'Compliance Status (Compliant/At Risk/Non-Compliant)',
            ];
        }

        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }

        $headerStyle = [
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ];

        $lastCol      = $isCompletedSheet ? 'M' : 'L';
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);

        $row = 2;
        foreach ($data as $assignment) {
            $sheet->setCellValue('A' . $row, $assignment['user_name'] ?? '');
            $sheet->setCellValue('B' . $row, $assignment['department'] ?? '');
            $sheet->setCellValue('C' . $row, ucfirst($assignment['course_type'] ?? ''));
            $sheet->setCellValue('D' . $row, $assignment['course_name'] ?? '');
            $sheet->setCellValue('E' . $row, $assignment['course_beginning_date_formatted'] ?? '');
            $sheet->setCellValue('F' . $row, $assignment['completion_status'] ?? '');

            $daysOverdue = $assignment['days_overdue'] ?? null;
            $sheet->setCellValue('G' . $row, ($daysOverdue !== null && $daysOverdue > 0) ? $daysOverdue : '');

            $sheet->setCellValue('H' . $row, ($assignment['progress_percentage'] ?? 0) . '%');
            $sheet->setCellValue('I' . $row, $assignment['started_date'] ?? '');

            if ($isCompletedSheet) {
                $sheet->setCellValue('J' . $row, $assignment['completion_date'] ?? '');
                $sheet->setCellValue('K' . $row, $assignment['learning_score'] ?? 0);
                $sheet->setCellValue('L' . $row, $assignment['score_band'] ?? '');
                $sheet->setCellValue('M' . $row, $assignment['compliance_status'] ?? '');

                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B8CCE4']],
                    ]);
                }
            } else {
                $sheet->setCellValue('J' . $row, $assignment['learning_score'] ?? 0);
                $sheet->setCellValue('K' . $row, $assignment['score_band'] ?? '');
                $sheet->setCellValue('L' . $row, $assignment['compliance_status'] ?? '');

                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B8CCE4']],
                    ]);
                }
            }

            $row++;
        }

        if ($row > 2) {
            $sheet->getStyle('A1:' . $lastCol . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(15);

        if ($isCompletedSheet) {
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(18);
            $sheet->getColumnDimension('L')->setWidth(20);
            $sheet->getColumnDimension('M')->setWidth(20);
        } else {
            $sheet->getColumnDimension('J')->setWidth(18);
            $sheet->getColumnDimension('K')->setWidth(20);
            $sheet->getColumnDimension('L')->setWidth(20);
        }

        if ($row > 2) {
            foreach (['C', 'E', 'F', 'G', 'H', 'I'] as $col) {
                $sheet->getStyle($col . '2:' . $col . ($row - 1))
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $extraCols = $isCompletedSheet ? ['J', 'K', 'L', 'M'] : ['J', 'K', 'L'];
            foreach ($extraCols as $col) {
                $sheet->getStyle($col . '2:' . $col . ($row - 1))
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        $sheet->setAutoFilter('A1:' . $lastCol . '1');

        if ($data->isEmpty()) {
            $sheet->setCellValue('A2', 'No data available');
            $sheet->mergeCells('A2:' . $lastCol . '2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}
