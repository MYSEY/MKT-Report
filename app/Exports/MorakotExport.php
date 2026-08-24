<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MorakotExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }
    
    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['Branch']           ?? '',
                $row['DrAccount']        ?? '',
                $row['DrCategory']       ?? '',
                $row['DrCurrency']       ?? '',
                $row['CrAccount']        ?? '',
                $row['CrCategory']       ?? '',
                $row['CrCurrency']       ?? '',
                $row['Amount']           ?? '',
                "\t" . ($row['LCYAmount']    ?? ''),
                "\t" . ($row['ExchangeRate'] ?? ''),
                $row['Transaction']      ?? '',
                $row['TranDate']         ?? '',
                $row['Reference']        ?? '',
                $row['Note']             ?? '',
                $row['DrGLKey']          ?? '',
                $row['CrGLKey']          ?? '',
                $row['Module']           ?? '',
                $row['Officer']          ?? '',
                $row['DisbursementList'] ?? '',
                $row['TargetBranch']     ?? '',
                $row['TargetBranchDrCr'] ?? '',
            ];
        }, $this->results);
    }

    public function headings(): array
    {
        return [
            'Branch',
            'DrAccount',
            'DrCategory',
            'DrCurrency',
            'CrAccount',
            'CrCategory',
            'CrCurrency',
            'Amount',
            'LCYAmount',
            'ExchangeRate',
            'Transaction',
            'TranDate',
            'Reference',
            'Note',
            'DrGLKey',
            'CrGLKey',
            'Module',
            'Officer',
            'DisbursementList',
            'TargetBranch',
            'TargetBranchDrCr',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => '@',
            'J' => '@',
            'H' => '0.00',
        ];
    }
    public function styles(Worksheet $sheet): array
    {
        $lastRow    = count($this->results) + 1;
        $lastColumn = 'U';

        // ✅ Set Arial Narrow size 8 for entire sheet
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'size' => 10,
            ],
        ]);

        // ✅ Header style (bold, centered, no fill)
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ✅ Data rows — border only, no fill colors
        $sheet->getStyle('A2:' . $lastColumn . $lastRow)->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        // ✅ Freeze header row
        $sheet->freezePane('A2');
        // ✅ Row height for header
        $sheet->getRowDimension(1)->setRowHeight(20);
        return [];
    }
}