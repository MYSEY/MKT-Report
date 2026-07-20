<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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

        // ✅ Group by branch then currency
        foreach ($this->results as $row) {
            $branch   = $row['Branch'] ?? 'Unknown';
            $currency = $row['DrCurrency'] ?? '';
            $grouped[$branch][$currency][] = $row;
        }

        // ✅ Separate #N/A from normal branches
        $naItems      = $grouped['#N/A'] ?? [];
        $normalGroups = array_filter($grouped, fn($key) => $key !== '#N/A', ARRAY_FILTER_USE_KEY);

        // ✅ Helper to render item row
        $renderItem = fn($item) => [
            $item['Branch']      ?? '',
            $item['DrAccount']   ?? '',
            $item['DrCategory']  ?? '',
            $item['DrCurrency']  ?? '',
            $item['CrAccount']   ?? '',
            $item['CrCategory']  ?? '',
            $item['CrCurrency']  ?? '',
            $item['Amount']      ?? 0,
            $item['LDNumber']    ?? '',
            $item['CCY']         ?? '',
            $item['KHName']      ?? '',
            $item['Outstanding'] ?? 0,
            $item['TotaArr']     ?? 0,
            $item['PricipalArr'] ?? 0,
            $item['InteresArr']  ?? 0,
            $item['PenaltyArr']  ?? 0,
            $item['ChargeArr']   ?? 0,
            $item['DateCol']     ?? '',
            $item['TotalCol']    ?? 0,
            $item['Principal']   ?? 0,
            $item['Interest']    ?? 0,
            $item['Charge']      ?? 0,
            $item['LoanProduct'] ?? '',
            $item['Note']        ?? '',
            $item['Agent']       ?? '',
            $item['Officer']     ?? '',
            $item['ClientTel']   ?? '',
        ];

        // ✅ Render KHR branches first
        foreach ($normalGroups as $branch => $currencies) {
            if (!isset($currencies['KHR'])) continue;
            $items = $currencies['KHR'];
            foreach ($items as $item) {
                $rows[] = $renderItem($item);
            }
            $rows[] = [
                $branch . ' Total', '', '', '', '', '', '',
                array_sum(array_column($items, 'Amount')),
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                '', '', '', '', '',
            ];
        }

        // ✅ Render USD branches after
        foreach ($normalGroups as $branch => $currencies) {
            if (!isset($currencies['USD'])) continue;
            $items = $currencies['USD'];
            foreach ($items as $item) {
                $rows[] = $renderItem($item);
            }
            $rows[] = [
                $branch . ' Total', '', '', '', '', '', '',
                array_sum(array_column($items, 'Amount')),
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                '', '', '', '', '',
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

        // ✅ Grand Total — sum ALL currencies in one row
        $rows[] = [
            'Grand Total', '', '', '', '', '', '',
            array_sum(array_column($allNumeric, 'Amount')),
            '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '',
        ];

        // ✅ Empty row
        $rows[] = array_fill(0, 27, '');

        // ✅ #N/A rows after Grand Total and before Agent
        if (!empty($naItems)) {
            foreach ($naItems as $currency => $items) {
                foreach ($items as $item) {
                    $rows[] = $renderItem($item);
                }
            }
            $rows[] = [
                '#N/A Total',
                '', '', '', '', '', '',
                0,
                '', '', '', '', '', '', '', '', '', '', '', '', '', '',
                '', '', '', '', '',
            ];
        }

        // ✅ Empty row
        $rows[] = array_fill(0, 27, '');

        // ✅ Agent summary header
        $rows[] = array_merge(['Agent', 'KHR', 'USD'], array_fill(0, 24, ''));

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
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow    = $sheet->getHighestRow();
        $lastColumn = 'AA';

        // ✅ Set Arial Narrow size 9 for entire sheet
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'size' => 9,
            ],
        ]);

        // ✅ Header style
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'name' => 'Arial Narrow',
                'bold' => true,
                'size' => 9,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ✅ Data rows styling
        for ($i = 2; $i <= $lastRow; $i++) {
            $cellValue = (string) $sheet->getCell('A' . $i)->getValue();

            if (str_starts_with($cellValue, 'Grand Total')) {
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 9,
                    ],
                ]);
            } elseif ($cellValue === '#N/A Total') {
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 9,
                    ],
                ]);
            } elseif ($cellValue === 'Agent') {
                $sheet->getStyle('A' . $i . ':C' . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 9,
                    ],
                ]);
            } elseif (isset($this->agentGrouped[$cellValue])) {
                $sheet->getStyle('A' . $i . ':C' . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 9,
                    ],
                ]);
            } elseif (str_ends_with($cellValue, ' Total')) {
                $sheet->getStyle('A' . $i . ':' . $lastColumn . $i)->applyFromArray([
                    'font' => [
                        'name' => 'Arial Narrow',
                        'bold' => true,
                        'size' => 9,
                    ],
                ]);
            } else {
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