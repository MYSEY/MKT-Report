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

class BranchExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected array $results;
    protected array $agentGrouped = [];

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function array(): array
    {
        $rows    = [];
        $grouped = [];

        foreach ($this->results as $row) {
            $branch = $row['Branch'] ?? 'Unknown';
            $grouped[$branch][] = $row;
        }

        foreach ($grouped as $branch => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    $item['Branch']      ?? '',  // A
                    $item['DrAccount']   ?? '',  // B
                    $item['DrCategory']  ?? '',  // C
                    $item['DrCurrency']  ?? '',  // D
                    $item['CrAccount']   ?? '',  // E
                    $item['CrCategory']  ?? '',  // F
                    $item['CrCurrency']  ?? '',  // G
                    $item['Amount']      ?? 0,   // H
                    $item['LDNumber']    ?? '',  // I
                    $item['CCY']         ?? '',  // J
                    $item['KHName']      ?? '',  // K
                    $item['Outstanding'] ?? 0,   // L
                    $item['TotaArr']     ?? 0,   // M
                    $item['PricipalArr'] ?? 0,   // N
                    $item['InteresArr']  ?? 0,   // O
                    $item['PenaltyArr']  ?? 0,   // P
                    $item['ChargeArr']   ?? 0,   // Q
                    $item['DateCol']     ?? '',  // R
                    $item['TotalCol']    ?? 0,   // S
                    $item['Principal']   ?? 0,   // T
                    $item['Interest']    ?? 0,   // U
                    $item['Charge']      ?? 0,   // V
                    $item['LoanProduct'] ?? '',  // W
                    $item['Note']        ?? '',  // X
                    $item['Agent']       ?? '',  // Y
                    $item['Officer']     ?? '',  // Z
                    $item['ClientTel']   ?? '',  // AA
                ];
            }

            // ✅ Branch subtotal — sum Amount only
            $numericItems = array_filter($items, fn($i) => $i['Outstanding'] !== '#N/A');
            $rows[] = [
                $branch . ' Total', // A
                '', '', '', '', '', '',                                // B-G
                array_sum(array_column($numericItems, 'Amount')),      // H
                '', '', '', '', '', '', '', '', '', '', '', '', '', '', // I-V
                '', '', '', '', '',                                    // W-AA
            ];
        }

        // ✅ All numeric rows (exclude #N/A)
        $allNumeric = array_filter($this->results, fn($i) => $i['Outstanding'] !== '#N/A');

        // ✅ Build agent grouped by currency
        $this->agentGrouped = [];
        foreach ($allNumeric as $item) {
            $agent    = $item['Agent'] ?? '';
            $currency = $item['DrCurrency'] ?? '';
            if (!isset($this->agentGrouped[$agent])) {
                $this->agentGrouped[$agent] = ['KHR' => 0, 'USD' => 0];
            }
            if ($currency === 'KHR') {
                $this->agentGrouped[$agent]['KHR'] += $item['Amount'];
            } elseif ($currency === 'USD') {
                $this->agentGrouped[$agent]['USD'] += $item['Amount'];
            }
        }

        // ✅ Empty row
        $rows[] = array_fill(0, 27, '');

        // ✅ Grand Total row
        $rows[] = [
            'Grand Total', '', '', '', '', '', '',              // A-G
            array_sum(array_column($allNumeric, 'Amount')),     // H
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', // I-V
            '', '', '', '', '',                                 // W-AA
        ];

        // ✅ Empty row
        $rows[] = array_fill(0, 27, '');

        // ✅ Agent summary header
        $rows[] = array_merge(
            ['Agent', 'KHR', 'USD'],
            array_fill(0, 24, '')
        );

        // ✅ Agent summary rows
        foreach ($this->agentGrouped as $agent => $amounts) {
            $rows[] = array_merge(
                [$agent, $amounts['KHR'] ?: 0, $amounts['USD'] ?: 0],
                array_fill(0, 24, '')
            );
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Branch',       // A
            'DrAccount',    // B
            'DrCategory',   // C
            'DrCurrency',   // D
            'CrAccount',    // E
            'CrCategory',   // F
            'CrCurrency',   // G
            'Amount',       // H
            'LD Number',    // I
            'CCY',          // J
            'KH Name',      // K
            'Outstanding',  // L
            'Tota Arr',     // M
            'Pricipal Arr', // N
            'Interes Arr',  // O
            'Penalty Arr',  // P
            'Charge Arr',   // Q
            'Date Col',     // R
            'Total Col',    // S
            'Principal',    // T
            'Interest',     // U
            'Charge',       // V
            'Loan Product', // W
            'Note',         // X
            'Agent',        // Y
            'Officer',      // Z
            'Client Tel',   // AA
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Amount
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Outstanding
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // TotaArr
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // PricipalArr
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // InteresArr
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // PenaltyArr
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // ChargeArr
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // TotalCol
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Principal
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Interest
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2, // Charge
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = 'AA';

        // ✅ Set Arial Narrow size 8 for entire sheet
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'size' => 8,
            ],
        ]);

        // ✅ Header style
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'bold' => true,
                'size' => 8,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ✅ Data rows
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = (string) $sheet->getCell('A' . $i)->getValue();

            if ($cellValue === 'Grand Total') {
                // Grand Total row — bold only
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 8,
                    ],
                ]);
            } elseif ($cellValue === 'Agent') {
                // Agent summary header — bold only
                $sheet->getStyle('A' . $i . ':C' . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 8,
                    ],
                ]);
            } elseif (isset($this->agentGrouped[$cellValue])) {
                // Agent data rows — bold only
                $sheet->getStyle('A' . $i . ':C' . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 8,
                    ],
                ]);
            } elseif (str_ends_with($cellValue, ' Total')) {
                // Branch subtotal row — bold only
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 8,
                    ],
                ]);
            } else {
                // Border for data rows
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);
            }
        }

        $sheet->freezePane('A2');
        $sheet->getRowDimension(1)->setRowHeight(20);

        return [];
    }
}