<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExportLoanDetailListing implements FromCollection, WithEvents, WithHeadings, WithColumnWidths, WithCustomStartCell
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $export_datas;
    protected $totalRecord;

    public function __construct($data)
    {
        $this->totalRecord = count($data);
        $dataExcel = [];
        foreach ($data as $row) {
            $dataExcel[] = [
                $row->ID,
                $row->ContractCustomerID,
                preg_replace('/\s+/', ' ', trim(($row->LastNameEn ?? '') . ' ' . ($row->FirstNameEn ?? ''))),
                $row->Branch,
                $row->Gender,
                $row->HouseNo.' '.$row->Street,
                $row->Village,
                $row->Commune=='' ? 'None' : $row->Commune,
                $row->District,
                $row->Province,
                $row->Account,
                $row->Currency,
                bcdiv($row->Disbursed,1,2),
                bcdiv($row->LoanBalanceAS,1,2),
                bcdiv($row->OutstandingAmountAS,1,2),
                bcdiv($row->InterestRate,1,2),
                round($row->AIRAS,2),
                round($row->IntIncEarned,2),
                round($row->TotalInterest,2),
                $this->formatDate($row->ValueDate),
                $this->formatDate($row->MaturityDate),
                $row->LoanProduct . ' ' .$row->LoanProductDes,
                $row->Term,
                $row->DisbursedStat,
                $row->AssetClass,
                $row->MoreThanOneYear,
                (int)$row->CBCSubSection,
                (int)$row->CBCISSubSectionCuSt,
                $row->MACode,
                $row->MADes,
                $row->LoanPurpose,
                $row->ContractOfficerID,
                $row->IDType,
                $row->IDNumber,
                $this->formatDate($row->LastPaymentDate),
                $row->DueDay == null ? '0' : $row->DueDay,
                $this->formatDate($row->OverdueDate),
                $row->LoanType,
                round($row->LoanCharge,2),
                round($row->ChargeEarned,2),
                round($row->ChargeUnearned,2),
                $row->ScheduleType == null || $row->ScheduleType == '0' ? 'None' : $row->ScheduleType,
                preg_replace('/\s+/', ' ', trim(($row->CustomerOccupation ?? ''))),
                $row->RestructuredCycle,
                preg_replace('/\s+/', ' ', trim(($row->AddressCode ?? ''))),
                $row->CollateralID == null ? 'None' : $row->CollateralID,
                $row->Mobile1. ' '. $row->Mobile2,
                $row->Cycle === null ? '03' : ltrim($row->Cycle, '0'),
                round($row->Amount ,2),
                round($row->OutstandingAmount,2),
                $row->EIRRate,
                round($row->AccrIntPerDay,2),
                round($row->AccrInterest,2),
                round($row->RegularCharge,2),
                round($row->SubAmount,2),
                $row->SubLoanPurpose,
                $row->PartneredWith,
                $row->RestructureType,
            ];
        }

        $this->export_datas = $dataExcel;
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
        ];
    }

    private function formatDate($date)
    {
        if (!$date) {
            return null;
        }

        return Date::dateTimeToExcel(
            Carbon::parse($date)
        );
    }
  
    public function collection()
    {
        return new Collection([
            $this->export_datas,
        ]);
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function headings(): array
    {
        return [
            "ID",
            "Customer ID",
            "Name",
            "Branch",
            "Gender",
            "Address",
            "Village",
            "Commune",
            "District",
            "Province",
            "Account #",
            "Currency",
            "Disburse",
            "Loan Amount AS",
            "Outstanding Amount AS",
            "Interest Rate AS",
            "Accrued Interest AS",
            "Interest Earned ($)",
            "Total Interest",
            "Disbursement Date",
            "Maturity Date",
            "Loan Product",
            "Term",
            "Status",
            "Asset Class",
            "More Than One Year",
            "CBCSubSection (Loan)",
            "CBCSubSection (Customer)",
            "MA Code",
            "MA Description",
            "Loan Purpose",
            "Officer",
            "ID Type",
            "ID Number",
            "Last Payment Date",
            "Overdue Days",
            "Overdue Date",
            "Loan Type",
            "Loan Charge(%)",
            "Charge Earned",
            "Charge Unearned",
            "Schedule Type (1=Dec, 2=Ann)",
            "Customer Occupation",
            "Restructured Cycle",
            "Address Code",
            "Collateral ID",
            "Customer Phone Number",
            "Loan Cycle",
            "Loan Amount FIRS",
            "Outstanding Amount FIRS",
            "Interest Rate FIRS",
            "Interest Per Day FIRS",
            "Accrued Interest FIRS",
            "Regular Charge(%)",
            "Sub Amount",
            "Sub Loan Purpose",
            "Partnered With",
            "Restructure Type",
        ];
    }

    public function columnWidths(): array
    {
        $columns = [];
        // A → Z
        foreach (range('A', 'Z') as $col) {
            $columns[$col] = 18; // default width
        }
        // AA → ZZ
        foreach (range('A', 'Z') as $first) {
            foreach (range('A', 'Z') as $second) {
                $columns[$first.$second] = 18;
            }
        }
        return $columns;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $lastRow = $this->totalRecord + 1;
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("T2:T{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("U2:U{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("AI2:AI{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("AK2:AK{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $event->sheet->getStyle('M:S')->getNumberFormat()->setFormatCode('#,##0.00');
                $event->sheet->getStyle('AN:AO')->getNumberFormat()->setFormatCode('#,##0.00');
                $event->sheet->getStyle('AW:BB')->getNumberFormat()->setFormatCode('#,##0.00');
            },
        ];
    }
}
