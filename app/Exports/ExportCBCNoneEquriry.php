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

class ExportCBCNoneEquriry implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    public function __construct(private $branchId = null)
    {
    }

    public function query()
    {
        $request = new Request(['branch_id' => $this->branchId]);

        $callFilteredQuery = \Closure::bind(
            static fn (Request $request) => CreditUnderwritingController::filteredQueryCBCNoneEquriry($request),
            null,
            CreditUnderwritingController::class,
        );

        [, $filtered] = $callFilteredQuery($request);

        return CreditUnderwritingController::getDatasCBCNoneEquriry($request, $filtered, false);
    }

    public function headings(): array
    {
        return [
            'Branch',
            'LoanID',
            'Customer Name',
            'Applicant Type',
            'Currency',
            'Disbursed Date',
            'Disburse Amount',
            'Outstanding',
            'Member Reference',
        ];
    }

    public function map($row): array
    {
        return [
            $row->branch,
            $row->loan_application_id,
            $row->customer_name,
            $row->customer_type,
            $row->currency,
            $row->app_date,
            $row->amount !== null ? (float) $row->amount : null,
            $row->amount !== null ? (float) $row->amount : null,
            // $row->outstanding_amount !== null ? (float) $row->outstanding_amount : null,
            $row->CBC_Ref,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = $sheet->getHighestRow();

                // G = Disburse Amount, H = Outstanding
                $sheet->getStyle("G2:G{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("H2:H{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
            },
        ];
    }
}