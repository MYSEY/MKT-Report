<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class MorakotExport implements
    FromArray,
    WithHeadings,
    WithCustomCsvSettings
{
    protected array $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * Format Amount
     *
     * Example:
     * 399400 -> 399400.00
     */
    private function formatAmount($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format(
            (float) $value,
            2,
            '.',
            ''
        );
    }

    /**
     * Format LCYAmount
     *
     * Keep 18 decimal places.
     */
    private function formatDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format(
            (float) $value,
            18,
            '.',
            ''
        );
    }

    /**
     * Format ExchangeRate
     *
     * Keep 18 decimal places.
     */
    private function formatExchangeRate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format(
            (float) $value,
            18,
            '.',
            ''
        );
    }

    /**
     * Format date
     *
     * Example:
     * 2026-08-21 -> 26-08-21
     */
    private function formatDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * CSV Data
     */
    public function array(): array
    {
        return array_map(function ($row) {

            return [

                // 1
                $row['Branch'] ?? '',

                // 2
                $row['DrAccount'] ?? '',

                // 3
                $row['DrCategory'] ?? '',

                // 4
                $row['DrCurrency'] ?? '',

                // 5
                $row['CrAccount'] ?? '',

                // 6
                $row['CrCategory'] ?? '',

                // 7
                $row['CrCurrency'] ?? '',

                // 8 - Amount
                "\t" . $this->formatAmount(
                    $row['Amount'] ?? ''
                ),

                // 9 - LCYAmount
                "\t" . $this->formatDecimal(
                    $row['LCYAmount'] ?? ''
                ),

                // 10 - ExchangeRate
                "\t" . $this->formatExchangeRate(
                    $row['ExchangeRate'] ?? ''
                ),

                // 11
                $row['Transaction'] ?? '',

                // 12
                $this->formatDate(
                    $row['TranDate'] ?? ''
                ),

                // 13
                $row['Reference'] ?? '',

                // 14
                $row['Note'] ?? '',

                // 15
                $row['DrGLKey'] ?? '',

                // 16
                $row['CrGLKey'] ?? '',

                // 17
                $row['Module'] ?? '',

                // 18
                $row['Officer'] ?? '',

                // 19
                $row['DisbursementList'] ?? '',

                // 20
                $row['TargetBranch'] ?? '',

                // 21
                $row['TargetBranchDrCr'] ?? '',
            ];

        }, $this->results);
    }

    /**
     * CSV Headers
     */
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

    /**
     * Morakot CSV Settings
     */
    public function getCsvSettings(): array
    {
        return [
            // Morakot CSV format
            'delimiter' => ',',

            // CSV enclosure
            'enclosure' => '"',

            // Windows line ending
            'line_ending' => "\r\n",

            // IMPORTANT:
            // DO NOT add UTF-8 BOM
            'use_bom' => false,

            // Do not add separator line
            'include_separator_line' => false,

            // Do not use Excel compatibility mode
            'excel_compatibility' => false,
        ];
    }
}