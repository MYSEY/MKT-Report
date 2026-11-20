<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PDDateExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->reorder()->orderBy('PD.ID');
    }

    public function headings(): array
    {
        return [
            'Branch',
            'Status',
            'Curr',
            'Inputter',
            'Createdon',
            'Authorizer',
            'Authorizeon',
            'ID',
            'PDID',
            'DueDate',
            'NumDayDue',
            'ODStatus',
            'TotODAmount',
            'OutAmount',
            'PrincipalDue',
            'OutPriAmount',
            'InterestDue',
            'OutIntAmount',
            'PenaltyDue',
            'OutPenAmount',
            'ChargeDue',
            'OutChgAmount',
            'NextRunDate',
            'OutAmountAS',
            'PrincipalDueAS',
            'OutPriAmountAS',
            'IntDueAmountAS',
            'OutIntAmountAS',
            'ChargeDueAS',
            'OutChgAmountAS',
            'PenaltyDueAS',
            'OutPenAmountAS',
            'VATDue',
            'OutVATAmount',
            'VATDueAS',
            'OutVATAmountAS',
            'WFStatus',
        ];
    }

    public function map($row): array
    {
        return [
            $row->Branch,
            $row->Status,
            $row->Curr,
            $row->Inputter,
            $row->Createdon,
            $row->Authorizer,
            $row->Authorizeon,
            $row->ID,
            $row->PDID,
            $row->DueDate,
            $row->NumDayDue,
            $row->ODStatus,
            (float) $row->TotODAmount,
            (float) $row->OutAmount,
            (float) $row->PrincipalDue,
            (float) $row->OutPriAmount,
            (float) $row->InterestDue,
            (float) $row->OutIntAmount,
            (float) $row->PenaltyDue,
            (float) $row->OutPenAmount,
            (float) $row->ChargeDue,
            (float) $row->OutChgAmount,
            $row->NextRunDate,
            (float) $row->OutAmountAS,
            (float) $row->PrincipalDueAS,
            (float) $row->OutPriAmountAS,
            (float) $row->IntDueAmountAS,
            (float) $row->OutIntAmountAS,
            (float) $row->ChargeDueAS,
            (float) $row->OutChgAmountAS,
            (float) $row->PenaltyDueAS,
            (float) $row->OutPenAmountAS,
            (float) $row->VATDue,
            (float) $row->OutVATAmount,
            (float) $row->VATDueAS,
            (float) $row->OutVATAmountAS,
            $row->WFStatus,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'U' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'V' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'X' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'Y' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'Z' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AA' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AB' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AC' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AD' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AE' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AF' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AG' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AH' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'AI' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
