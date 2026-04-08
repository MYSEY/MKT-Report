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

    public function __construct($request)
    {
        $subQueryPD = DB::connection('pgsql')
            ->table(DB::raw('(
                SELECT DISTINCT ON ("ID")
                    "ID",
                    CAST("NumDayDue" AS INTEGER) AS "DueDay",
                    "DueDate"
                FROM "MKT_PD_DATE"
                WHERE "OutIntAmountAS" > 0 OR "OutPriAmountAS" > 0
                ORDER BY "ID", CAST("NumDayDue" AS INTEGER) DESC
            ) as PD'));
            
            $subQueryACCENTR = DB::connection('pgsql')
            ->table('MKT_ACC_ENTRY')
            ->select(
                'Account',
                // 'Reference',
                DB::raw('MAX("Reference") AS "Reference"'),
                DB::raw('MAX("TransactionDate") AS "LastPaymentDate"'),
            )
            ->where('Amount', '>', 0)
            ->groupBy('Account');
            
            $query = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->select([
                'LC.ID',
                'LC.ContractCustomerID',
                'LC.Branch',
                'LC.Account',
                'LC.Currency',
                'LC.Disbursed',
                'LC.LoanBalanceAS',
                'LC.OutstandingAmountAS',
                'LC.InterestRate',
                'LC.AIRAS',
                'LC.AIRCurrentAS',
                'LC.AccrIntPerDay',
                'LC.TotalInterest',
                'LC.ValueDate',
                'LC.MaturityDate',
                'LC.Term',
                'LC.DisbursedStat',
                'LC.AssetClass',
                'LC.MoreThanOneYear',
                'LC.CBCSubSection',
                'LC.LoanPurpose',
                'LC.ContractOfficerID',
                'LC.LoanType',
                'LC.RestructuredCycle',
                'LCol.Collateral as CollateralID',
                'LC.Amount',
                'LC.OutstandingAmount',
                'LC.EIRRate',
                'LC.AccrInterest',
                'LC.IntIncEarned',
                'LC.Sector as MACode',
                'LC.LoanProduct',
                'LC.Cycle',
                'LC.SubAmount',
                'LC.SubLoanPurpose',
                'LC.PartneredWith',
                'LC.RestructureType',
                'CUST.LastNameEn',
                'CUST.FirstNameEn',
                'CUST.Gender',
                'CUST.IDType',
                'CUST.IDNumber',
                'CUST.Mobile1',
                'CUST.Mobile2',
                'CUST.HouseNo',
                'CUST.CBCISSubSection as CBCISSubSectionCuSt',
                'CUST.Village as AddressCode',
                'CUST.Street',
                'VL.LocalDescription as Village',
                'CM.LocalDescription as Commune',
                'DS.LocalDescription as District',
                'PR.LocalDescription as Province',
                'Sct.Description as MADes',
                'LPr.Description as LoanProductDes',
                'PD.DueDay',
                'PD.DueDate as OverdueDate',
                'LCh1.Charge AS LoanCharge101',
                'LCh1.Charge AS LoanCharge',
                'LCh1.ChargeEarned',
                'LCh1.ChargeUnearned',
                'LCh2.Charge AS LoanCharge102',
                'LCh2.Charge as RegularCharge',
                'POS.Description as CustomerOccupation',
                'SD.RepMode as ScheduleType',

                // ✅ FINAL FIX (correlated subquery)
                // DB::raw('(
                //     SELECT MAX(AE."TransactionDate")
                //     FROM "MKT_ACC_ENTRY" AE
                //     WHERE AE."Account" = "LC"."Account"
                //     AND AE."Amount" > 0
                //     AND (
                //         AE."Reference" = "LC"."ID"
                //         OR AE."Reference" = \'PD\' || "LC"."ID"
                //     )
                // ) as "LastPaymentDate"')
                DB::raw('(
                    SELECT MAX(AE."TransactionDate")
                    FROM "MKT_ACC_ENTRY" AE
                    WHERE AE."Account" = "LC"."Account"
                    AND AE."Amount" > 0
                    AND AE."Transaction" IN (\'6\',\'7\',\'10\',\'11\',\'13\',\'25\',\'27\',\'40\',\'52\',\'53\',\'54\')
                    AND (
                        AE."Reference" = "LC"."ID"
                        OR AE."Reference" = \'PD\' || "LC"."ID"
                    )
                ) as "LastPaymentDate"')
            ])

            // ✅ PD Subquery Join
            ->leftJoinSub($subQueryPD, 'PD', function ($join) {
                $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
            })

            // Other joins
            ->leftJoin('MKT_LOAN_CHARGE as LCh1', function($q){
                $q->on('LC.ID', '=', 'LCh1.ID')
                ->where('LCh1.ChargeKey', '=', 101);
            })
            ->leftJoin('MKT_LOAN_CHARGE as LCh2', function($q){
                $q->on('LC.ID', '=', 'LCh2.ID')
                ->where('LCh2.ChargeKey', '=', 102);
            })
            ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
            ->leftJoin('MKT_SCHED_DEFINE as SD', 'LC.ID', '=', 'SD.ID')
            ->leftJoin('MKT_POSITION as POS', 'POS.ID', '=', 'CUST.Position')
            ->leftJoin('MKT_VILLAGE as VL', 'CUST.Village', '=', 'VL.ID')
            ->leftJoin('MKT_COMMUNE as CM', 'CUST.Commune', '=', 'CM.ID')
            ->leftJoin('MKT_DISTRICT as DS', 'CUST.District', '=', 'DS.ID')
            ->leftJoin('MKT_PROVINCE as PR', 'CUST.Province', '=', 'PR.ID')
            ->leftJoin('MKT_SECTOR as Sct', 'LC.Sector', '=', 'Sct.ID')
            ->leftJoin('MKT_LOAN_COLLATERAL as LCol', 'LC.ID', '=', 'LCol.ID')
            ->leftJoin('MKT_LOAN_PRODUCT as LPr', 'LC.LoanProduct', '=', 'LPr.ID');

        // -------------------------
        // FILTERS (if used)
        // -------------------------
        $query->when($request->branch_id, fn($q,$branch_id) =>
            $q->where('LC.Branch', $branch_id)
        );
        $query->when($request->LCID, fn($q,$LCID) =>
            $q->where('LC.ID', 'ilike', "%{$LCID}%")
        );

        $searchValue = request()->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('LC.Account', 'like', "%{$searchValue}%")
                ->orWhere('LC.ID', 'like', "%{$searchValue}%")
                ->orWhere('CUST.FirstNameEn', 'like', "%{$searchValue}%")
                ->orWhere('CUST.LastNameEn', 'like', "%{$searchValue}%")
                ->orWhere('LC.ContractCustomerID', 'like', "%{$searchValue}%")
                ->orWhere('LC.ContractOfficerID', 'like', "%{$searchValue}%")
                ->orWhere('PR.LocalDescription', 'like', "%{$searchValue}%")
                ->orWhere('DS.LocalDescription', 'like', "%{$searchValue}%")
                ->orWhere('CM.LocalDescription', 'like', "%{$searchValue}%")
                ->orWhere('VL.LocalDescription', 'like', "%{$searchValue}%")
                ->orWhere('Sct.Description', 'like', "%{$searchValue}%")
                ->orWhere('LPr.Description', 'like', "%{$searchValue}%");
            });
        }

        // GET DATA
        $data = $query->get();
        $this->totalRecord = count($data);
        // -------------------------
        // EXPORT FORMAT
        // -------------------------
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
    public function khmerToEnglishNumber($value)
    {
        $khmerNumbers = ['០','១','២','៣','៤','៥','៦','៧','៨','៩'];
        $englishNumbers = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($khmerNumbers, $englishNumbers, $value);
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
                // Column T = date column
                $sheet->getStyle("T2:T{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("U2:U{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("AI2:AI{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
                $sheet->getStyle("AK2:AK{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);

                // Adjust this range if your numeric columns move
                $event->sheet->getStyle('M:S')->getNumberFormat()->setFormatCode('#,##0.00');

                $event->sheet->getStyle('AN:AO')->getNumberFormat()->setFormatCode('#,##0.00');
                $event->sheet->getStyle('AW:BB')->getNumberFormat()->setFormatCode('#,##0.00');
            },
        ];
    }
}
