<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;

class PdfToExcelExport implements FromArray, WithEvents
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Fixed column widths (NO ShouldAutoSize - conflicts with wrap text)
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(60);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(16);

                // Header row styling
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
                $sheet->getStyle('A1:E1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // Wrap text for Transaction Details column
                $sheet->getStyle("B2:B{$highestRow}")
                    ->getAlignment()->setWrapText(true);

                // Vertical align top for all data rows
                $sheet->getStyle("A2:E{$highestRow}")
                    ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Center Date column
                $sheet->getStyle("A2:A{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Right-align amount columns
                $sheet->getStyle("C2:E{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Auto row height based on wrapped text length in column B
                $approxCharsPerLine = 60;
                for ($row = 2; $row <= $highestRow; $row++) {
                    $text      = (string) $sheet->getCell("B{$row}")->getValue();
                    $lineCount = max(1, (int) ceil(mb_strlen($text) / $approxCharsPerLine));
                    $lineCount = max($lineCount, count(explode("\n", $text)));
                    $sheet->getRowDimension($row)->setRowHeight($lineCount * 15);
                }

                // Thin borders on whole table
                $sheet->getStyle("A1:E{$highestRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Freeze header row
                $sheet->freezePane('A2');
            }
        ];
    }
}