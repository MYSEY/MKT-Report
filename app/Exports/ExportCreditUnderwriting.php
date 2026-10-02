<?php

namespace App\Exports;

use App\Http\Controllers\Admins\CreditUnderwritingController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ExportCreditUnderwriting implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    public function __construct(private $branchId = null)
    {
    }

    public function query()
    {
        $request = new Request(['branch_id' => $this->branchId]);
        return CreditUnderwritingController::getDatas($request, false);
    }

    public function headings(): array
    {
        return [
            'Branch',
            'LoanID',
            'Customer Name',
            'Currency',
            'Disbursed',
            'Value Date',
            'Outstanding Amount',
            'Income',
            'DSCR',
            'Credit Bureau Checks',
            'Number of Other Lenders',
        ];
    }

    public function map($row): array
    {
        return [
            $row->branch,
            $row->loan_id,
            $row->customer_name,
            $row->income_currency,
            $row->disbursed !== null ? (float) $row->disbursed : null,
            $row->value_date,
            $row->outstanding_amount !== null ? (float) $row->outstanding_amount : null,
            $row->monthly_income !== null ? (float) $row->monthly_income : null,
            $row->dsc_ratio !== '' ? (float) $row->dsc_ratio : null,
            $row->number_of_applicants !== '' ? (int) $row->number_of_applicants : null,
            $row->number_of_other_lenders !== '' ? (int) $row->number_of_other_lenders : null,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = $sheet->getHighestRow();
                for ($r = 2; $r <= $last; $r++) {
                    $currency = $sheet->getCell("D{$r}")->getValue();

                    // KHR: 1,200,000 | USD and others: 250.00
                    $format = $currency === 'KHR' ? '#,##0' : '#,##0.00';

                    // E = Disbursed, G = Outstanding, H = Income
                    foreach (['E', 'G', 'H'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($format);
                    }
                }
                // I = DSCR: add % sign
                $sheet->getStyle("I2:I{$last}")->getNumberFormat()->setFormatCode('0.00"%"');
            },
        ];
    }
}