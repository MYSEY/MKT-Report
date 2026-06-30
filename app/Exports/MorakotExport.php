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
                $row['LCYAmount']        ?? '',
                $row['ExchangeRate']     ?? '',
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
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Amount
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // LCYAmount
            // 'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // ExchangeRate
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->results) + 1;
        // Header style
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Data rows style
        $sheet->getStyle('A2:U' . $lastRow)->applyFromArray([
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

        // Alternating row colors
        for ($i = 2; $i <= $lastRow; $i++) {
            if ($i % 2 === 0) {
                $sheet->getStyle('A' . $i . ':U' . $i)->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EBF3FB'],
                    ],
                ]);
            }
        }
        // Freeze header row
        $sheet->freezePane('A2');
        // Row height for header
        $sheet->getRowDimension(1)->setRowHeight(20);
        return [];
    }
}