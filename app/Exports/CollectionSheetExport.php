<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CollectionSheetExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithColumnFormatting
{
    public function __construct(private $query) {}

    public function query()
    {
        return $this->query->reorder()->orderBy('RS.ID');
    }

    public function headings(): array
    {
        return [
            'Loan ID',
            'Repayment Date',
            'Principal',
            'Interest',
            'Charge',
            'Branch',
            'Currency'
        ];
    }

    public function map($row): array
    {
        return [
            $row->LoanID,
            $row->CollectionDate,
            (float) $row->Principal,
            (float) $row->Interest,
            (float) $row->Charge,
            $row->Branch,
            $row->Currency,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // #,##0.00
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}