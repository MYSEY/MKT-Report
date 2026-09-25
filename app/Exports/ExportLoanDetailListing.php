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
                $row->ReportDate,                                                      // A
                $row->ID,                                                              // B
                $row->ContractCustomerID,                                              // C
                preg_replace('/\s+/', ' ', trim(($row->LastNameEn ?? '') . ' ' . ($row->FirstNameEn ?? ''))), // D
                $row->Branch,                                                          // E
                $row->Gender,                                                          // F
                $row->HouseNo.' '.$row->Street,                                        // G
                $row->Village,                                                         // H
                $row->Commune=='' ? 'None' : $row->Commune,                            // I
                $row->District,                                                        // J
                $row->Province,                                                        // K
                $row->Account,                                                         // L
                $row->Currency,                                                        // M
                bcdiv($row->Disbursed,1,2),                                            // N
                bcdiv($row->LoanBalanceAS,1,2),                                        // O
                bcdiv($row->OutstandingAmountAS,1,2),                                  // P
                bcdiv($row->InterestRate,1,2),                                         // Q
                round($row->AIRAS,2),                                                  // R
                round($row->IntIncEarned,2),                                           // S
                round($row->TotalInterest,2),                                          // T
                $this->formatDate($row->ValueDate),                                    // U
                $this->formatDate($row->MaturityDate),                                 // V
                $row->LoanProduct . ' ' .$row->LoanProductDes,                         // W
                $row->Term,                                                            // X
                $row->DisbursedStat,                                                   // Y
                $row->AssetClass,                                                      // Z
                $row->MoreThanOneYear,                                                 // AA
                (int)$row->CBCSubSection,                                              // AB
                (int)$row->CBCISSubSectionCuSt,                                        // AC
                $row->MACode,                                                          // AD
                $row->MADes,                                                           // AE
                $row->LoanPurpose,                                                     // AF
                $row->ContractOfficerID,                                               // AG
                $row->IDType,                                                          // AH
                $row->IDNumber,                                                        // AI
                $this->formatDate($row->LastPaymentDate),                              // AJ
                $row->DueDay == null ? '0' : $row->DueDay,                             // AK
                $this->formatDate($row->OverdueDate),                                  // AL
                $row->LoanType,                                                        // AM
                round($row->LoanCharge,2),                                             // AN
                round($row->ChargeEarned,2),                                           // AO
                round($row->ChargeUnearned,2),                                         // AP
                $row->ScheduleType == null || $row->ScheduleType == '0' ? 'None' : $row->ScheduleType, // AQ
                preg_replace('/\s+/', ' ', trim(($row->CustomerOccupation ?? ''))),    // AR
                $row->RestructuredCycle,                                               // AS
                preg_replace('/\s+/', ' ', trim(($row->AddressCode ?? ''))),           // AT
                $row->CollateralID == null ? 'None' : $row->CollateralID,              // AU
                $row->Mobile1. ' '. $row->Mobile2,                                     // AV
                $row->Cycle === null ? '03' : ltrim($row->Cycle, '0'),                 // AW
                round($row->Amount ,2),                                                // AX
                round($row->OutstandingAmount,2),                                      // AY
                $row->EIRRate,                                                         // AZ
                round($row->AccrIntPerDay,2),                                          // BA
                round($row->AccrInterest,2),                                           // BB
                round($row->RegularCharge,2),                                          // BC
                round($row->SubAmount,2),                                              // BD
                $row->SubLoanPurpose,                                                  // BE
                $row->PartneredWith,                                                   // BF
                $row->RestructureType,                                                 // BG
            ];
        }

        $this->export_datas = $dataExcel;
    }

    public function columnFormats(): array
    {
        return [
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'P' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'Q' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'R' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'T' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
            'Report Date',
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

                $sheet->getStyle("U2:U{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);  // ValueDate
                $sheet->getStyle("V2:V{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);  // MaturityDate
                $sheet->getStyle("AJ2:AJ{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14); // LastPaymentDate
                $sheet->getStyle("AL2:AL{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14); // OverdueDate

                $event->sheet->getStyle('N:T')->getNumberFormat()->setFormatCode('#,##0.00');   // Disbursed...Total Interest
                $event->sheet->getStyle('AO:AP')->getNumberFormat()->setFormatCode('#,##0.00'); // ChargeEarned/Unearned
                $event->sheet->getStyle('AX:BC')->getNumberFormat()->setFormatCode('#,##0.00'); // Amount...AccrInterest FIRS

                $event->sheet->getDelegate()->setTitle('Loanlisting');
            },
        ];
    }
}