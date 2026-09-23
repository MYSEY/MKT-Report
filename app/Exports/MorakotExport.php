<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MorakotExport implements FromArray, WithHeadings, WithCustomCsvSettings
{
    protected array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    private function clean($value): string
    {
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    private function padDecimal($value, int $decimals = 16): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '';
        }

        $value = (string) $value;

        // Handle negative sign
        $negative = false;
        if (str_starts_with($value, '-')) {
            $negative = true;
            $value = substr($value, 1);
        }

        // Split into integer and decimal parts
        if (str_contains($value, '.')) {
            [$intPart, $decPart] = explode('.', $value, 2);
        } else {
            $intPart = $value;
            $decPart = '';
        }

        // Pad or truncate decimal part to exact length
        $decPart = str_pad(substr($decPart, 0, $decimals), $decimals, '0');

        $result = $intPart . '.' . $decPart;

        return $negative ? '-' . $result : $result;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            $tranDate = '';
            if (!empty($row['TranDate'])) {
                try {
                    $tranDate = Carbon::parse($row['TranDate'])->format('Y-m-d');
                } catch (\Throwable $e) {
                    $tranDate = $this->clean($row['TranDate']);
                }
            }

            return [
                $this->clean($row['Branch'] ?? ''),
                $this->clean($row['DrAccount'] ?? ''),
                $this->clean($row['DrCategory'] ?? ''),
                $this->clean($row['DrCurrency'] ?? ''),
                $this->clean($row['CrAccount'] ?? ''),
                $this->clean($row['CrCategory'] ?? ''),
                $this->clean($row['CrCurrency'] ?? ''),
                $this->clean($row['Amount'] ?? ''),
                $this->clean($row['LCYAmount']    ?? '',),
                $this->clean($row['ExchangeRate'] ?? '', ),
                $this->clean($row['Transaction'] ?? ''),
                $tranDate,
                $this->clean($row['Reference'] ?? ''),
                $this->clean($row['Note'] ?? ''),
                $this->clean($row['DrGLKey'] ?? ''),
                $this->clean($row['CrGLKey'] ?? ''),
                $this->clean($row['Module'] ?? ''),
                $this->clean($row['Officer'] ?? ''),
                $this->clean($row['DisbursementList'] ?? ''),
                $this->clean($row['TargetBranch'] ?? ''),
                $this->clean($row['TargetBranchDrCr'] ?? ''),
            ];
        }, $this->results);
    }

    public function headings(): array
    {
        return [
            'Branch', 'DrAccount', 'DrCategory', 'DrCurrency',
            'CrAccount', 'CrCategory', 'CrCurrency', 'Amount',
            'LCYAmount', 'ExchangeRate', 'Transaction', 'TranDate',
            'Reference', 'Note', 'DrGLKey', 'CrGLKey', 'Module',
            'Officer', 'DisbursementList', 'TargetBranch', 'TargetBranchDrCr',
        ];
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => "\r\n",
            'use_bom'                => false, // Change this from true to false
            'include_separator_line' => false,
        ];
    }
}