<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ExportCOPerformance implements FromCollection, WithColumnWidths, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $export_datas;

    public function __construct($request)
    {
        // -------------------------
        // 1) SUBQUERY FIXED
        // -------------------------
        $subQueryPD = DB::connection('pgsql')
            ->table('MKT_PD_DATE')
            ->select(
                'ID',
                DB::raw('SUM("OutPriAmountAS") AS "PDPrincipal"'),
                DB::raw('SUM("OutIntAmountAS") AS "PDInterest"'),
                DB::raw('SUM("OutPenAmountAS") AS "PDPenalty"'),
                DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
                DB::raw('MAX("DueDate") AS "DueDate"')
            )
        ->groupBy('ID');
        
        $query = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT as LC')
        ->select([
            'LC.ContractOfficerID',
            'LC.Currency',
            DB::raw('MAX("OFFICER"."FirstName") AS "FirstName"'),
            DB::raw('MAX("OFFICER"."LastName") AS "LastName"'),

            DB::raw('SUM("LC"."Disbursed") AS totaldisbursed'),
            DB::raw('SUM("LC"."OutstandingAmountAS") AS OutstandingAmt'),
            DB::raw('SUM("LC"."LoanBalanceAS") AS totalloanbalanceas'),

            DB::raw('COUNT(DISTINCT "LC"."LoanApplicationID") AS "TotalLoans"'),
            DB::raw('COUNT(DISTINCT "LC"."ContractCustomerID") AS "borrowers"'),

            // ===============================
            // ✅ TOTAL PRINCIPAL DUE
            // ===============================
            DB::raw('SUM("PD"."PDPrincipal") AS "TotalPDPrincipal"'),
            DB::raw('SUM("PD"."PDInterest") AS "TotalPDInterest"'),
            // DB::raw('SUM("PD"."PDPenalty") AS "TotalPDPenalty"'),
            DB::raw('
                SUM(
                    CASE
                        WHEN NULLIF("LC"."AssetClass", \'\')::INTEGER > 0
                        THEN "PD"."PDPenalty"
                        ELSE 0
                    END
                ) AS "TotalPDPenalty"
            '),
           
            DB::raw('
                COUNT(
                    DISTINCT CASE
                        WHEN "PD"."DueDay" >= 1
                        AND (
                            "PD"."PDPrincipal" > 0
                            OR "PD"."PDInterest" > 0
                        )
                        THEN "LC"."ID"
                    END
                ) AS "Pars"
            '),
            DB::raw('
                SUM(
                    CASE 
                        WHEN "PD"."DueDay" >= 1 
                        AND (
                            "PD"."PDInterest" > 0
                            OR "PD"."PDPrincipal" > 0
                        )
                        THEN "LC"."OutstandingAmountAS" 
                        ELSE 0 
                    END
                ) AS "ParAmount"
            '),
            // ✅ CONDITION-BASED LOANS
            DB::raw('
                COUNT(
                    DISTINCT CASE
                        WHEN "LC"."LoanProduct" = \'101\'
                        AND "CUST"."Gender" = \'Female\'
                        AND (
                                CASE
                                    WHEN "LC"."Currency" = \'KHR\'
                                    THEN "LC"."Disbursed" / 4000
                                    ELSE "LC"."Disbursed"
                                END
                            ) <= 2000
                            AND "LCOLL"."Collateral" IS NULL
                        THEN "LC"."LoanApplicationID"
                    END
                ) AS "Loans"
            '),
            // ✅ PAR Outstanding subtotal
            DB::raw('
                SUM(
                    CASE
                        WHEN
                            "LC"."LoanProduct" = \'101\'
                            AND "CUST"."Gender" = \'Female\'
                            AND (
                                CASE
                                    WHEN "LC"."Currency" = \'KHR\'
                                    THEN "LC"."Disbursed" / 4000
                                    ELSE "LC"."Disbursed"
                                END
                            ) <= 2000
                            AND "LCOLL"."Collateral" IS NULL
                        THEN
                            CASE
                                WHEN "LC"."Currency" = \'KHR\'
                                THEN "LC"."Disbursed" / 4000
                                ELSE "LC"."Disbursed"
                            END
                        ELSE 0
                    END
                ) AS "OutstandingAmt"
            '),
            DB::raw('
                COUNT(
                    DISTINCT CASE
                        WHEN "PD"."DueDay" >= 1
                        AND "LC"."LoanProduct" = \'101\'
                        AND "CUST"."Gender" = \'Female\'
                        AND (
                            CASE
                                WHEN "LC"."Currency" = \'KHR\'
                                THEN "LC"."Disbursed" / 4000
                                ELSE "LC"."Disbursed"
                            END
                        ) <= 2000
                        AND "LCOLL"."Collateral" IS NULL
                        THEN "LC"."LoanApplicationID"
                    END
                ) AS "OutPARs"
            '),
            DB::raw('
                SUM(
                    CASE
                        WHEN
                            "PD"."DueDay" >= 1
                            AND "LC"."LoanProduct" = \'101\'
                            AND "CUST"."Gender" = \'Female\'
                            AND (
                                CASE
                                    WHEN "LC"."Currency" = \'KHR\'
                                    THEN "LC"."Disbursed" / 4000
                                    ELSE "LC"."Disbursed"
                                END
                            ) <= 2000
                            AND "LCOLL"."Collateral" IS NULL
                        THEN
                            CASE
                                WHEN "LC"."Currency" = \'KHR\'
                                THEN "LC"."Disbursed" / 4000
                                ELSE "LC"."Disbursed"
                            END
                        ELSE 0
                    END
                ) AS "ParAmtAS"
            ')
        ])
        ->leftJoin('MKT_OFFICER as OFFICER', 'LC.ContractOfficerID', '=', 'OFFICER.ID')
        ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
        ->leftJoin('MKT_LOAN_COLLATERAL as LCOLL', function ($join) {
            $join->whereRaw('"LCOLL"."ID" = \'LC\' || "LC"."ID"')
                ->where('LCOLL.ID', 'like', 'LC%');
        })
        ->leftJoinSub($subQueryPD, 'PD', function ($join) {
            $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
        })
        ->where('LC.OutstandingAmountAS', '>', 0)
        ->groupBy(
            'LC.ContractOfficerID',
            'LC.Currency'
        );

        $query->when(request('branch_id'), function ($q, $branch_id) {
            return $q->where('LC.Branch', $branch_id);
        });

        // GET DATA
        $data = $query->get();

        // -------------------------
        // EXPORT FORMAT
        // -------------------------
        $currency = DB::connection('pgsql')->table('MKT_CURRENCY')->where('ID', 'KHR')->select('ReportingRate')->first();

        $exchangeRate = (float) $currency->ReportingRate;
        $dataExcel = [];
        $currentCO = null;

        // subtotal holders
        $sub = [
            'total_borrowers' => 0,
            'total_loans' => 0,
            'disbursed' => 0,
            'outstanding' => 0,
            'loan_balance' => 0,
            'pars' => 0,
            'par_amt' => 0,
            'par_rate' => 0,
            'pd_principal' => 0,
            'pd_interest' => 0,
            'pd_penalty' => 0,
            'ArrearRate' => 0,
            'Loans' => 0,
            'OutstandingAmt' => 0,
            'OutPARs' => 0,
            'ParAmtAS' => 0,
            'OutPARRate' => 0,
        ];
        // GRAND TOTAL
        $grand = array_fill_keys(array_keys($sub), 0);
        foreach ($data as $row) {
            // -----------------------------
            // BASE VALUES
            // -----------------------------
            $disbursed   = (float) $row->totaldisbursed;
            $outstanding = (float) $row->outstandingamt;
            $loanBalance = (float) $row->totalloanbalanceas;
            $parAmount   = (float) $row->ParAmount;

            $pdPrincipal = (float) $row->TotalPDPrincipal;
            $pdInterest  = (float) $row->TotalPDInterest;
            $pdPenalty   = (float) $row->TotalPDPenalty;
            $OutstandingAmt   =  $row->OutstandingAmt;
            $ParAmtAS   =  $row->ParAmtAS;

            // -----------------------------
            // ✅ CONVERT KHR → USD
            // -----------------------------
            if ($row->Currency === 'KHR') {
                $disbursed      *= $exchangeRate;
                $outstanding    *= $exchangeRate;
                $loanBalance    *= $exchangeRate;
                $parAmount      *= $exchangeRate;
                $pdPrincipal    *= $exchangeRate;
                $pdInterest     *= $exchangeRate;
                $pdPenalty      *= $exchangeRate;
                $OutstandingAmt *= $exchangeRate;
                $ParAmtAS       *= $exchangeRate;
            }

            // -----------------------------
            // 🔹 CO CHANGE → PUSH SUBTOTAL
            // -----------------------------
            if ($currentCO !== null && $currentCO !== $row->ContractOfficerID) {

                $ParRate = $sub['outstanding'] > 0 ? round(($sub['par_amt'] / $sub['outstanding']) * 100, 2) : 0;
                $ArrearRate = round(($sub['pd_principal'] / $sub['outstanding']) * 100, 2);
                $OutPARRate = $sub['OutstandingAmt'] > 0 ? round(($sub['ParAmtAS'] / $sub['OutstandingAmt']) * 100, 2): 0;
                
                // 👉 print SubTotal row
                $dataExcel[] = [
                    '',
                    'SubTotal',
                    'USD',
                    $sub['total_borrowers'],
                    $sub['total_loans'],
                    number_format($sub['disbursed'], 2),
                    number_format($sub['outstanding'], 2),
                    number_format($sub['loan_balance'], 2),
                    $sub['pars'],
                    number_format($sub['par_amt'], 2),
                    $ParRate . '%',
                    number_format($sub['pd_principal'], 2),
                    number_format($sub['pd_interest'], 2),
                    number_format($sub['pd_penalty'], 2),
                    $ArrearRate . '%',
                    $sub['Loans'],
                    number_format($sub['OutstandingAmt'], 2),
                    $sub['OutPARs'],
                    number_format($sub['ParAmtAS'], 2),
                    $OutPARRate . '%',
                ];

                // 👉 add ONLY subtotal to grand total
                foreach ($sub as $k => $v) {
                    $grand[$k] += $v;
                }

                // reset subtotal
                $sub = array_fill_keys(array_keys($sub), 0);
            }


            $currentCO = $row->ContractOfficerID;

            // -----------------------------
            // 🔹 DETAIL ROW (RAW VALUES)
            // -----------------------------
            $dataExcel[] = [
                $row->ContractOfficerID,
                trim(($row->LastName ?? '') . ' ' . ($row->FirstName ?? '')),
                $row->Currency,
                $row->borrowers,
                $row->TotalLoans,
                number_format($row->totaldisbursed,2),
                number_format($row->outstandingamt,2),
                number_format($row->totalloanbalanceas,2),
                number_format($row->Pars,2),
                number_format($row->ParAmount,2),
                round(($row->outstandingamt > 0 ? $row->ParAmount / $row->outstandingamt : 0) * 100, 2) . '%',
                number_format($row->TotalPDPrincipal,2),
                number_format($row->TotalPDInterest,2),
                number_format($row->TotalPDPenalty,2),
                round(($row->outstandingamt > 0 ? $row->TotalPDPrincipal / $row->outstandingamt : 0) * 100, 2) . '%',
                number_format($row->Loans,2),
                number_format($row->OutstandingAmt,2),
                number_format($row->OutPARs,2),
                number_format($row->ParAmtAS,2),
                round(($row->OutstandingAmt > 0 ? $row->ParAmtAS / $row->OutstandingAmt : 0) * 100, 2) . '%',
            ];

            // -----------------------------
            // ✅ ACCUMULATE SUBTOTAL (USD!)
            // -----------------------------

            $sub['total_borrowers']    += $row->borrowers;
            $sub['total_loans']  += $row->TotalLoans;
            $sub['disbursed']    += $disbursed;
            $sub['outstanding']  += $outstanding;
            $sub['loan_balance'] += $loanBalance;
            $sub['pars']         += $row->Pars;
            $sub['par_amt']      += $parAmount;
            $sub['pd_principal'] += $pdPrincipal;
            $sub['pd_interest']  += $pdInterest;
            $sub['pd_penalty']   += $pdPenalty;
            $sub['Loans']        += $row->Loans;
            $sub['OutstandingAmt'] += $OutstandingAmt;
            $sub['OutPARs']      += $row->OutPARs;
            $sub['ParAmtAS']     += $ParAmtAS;
        }
        
        // ==========================
        // GRAND TOTAL ROW
        // ==========================
        if ($currentCO !== null) {

            $subParRate = $sub['outstanding'] > 0 ? round(($sub['par_amt'] / $sub['outstanding']) * 100, 2): 0;
            $subArrearRate = round(($sub['pd_principal'] / $sub['outstanding']) * 100, 2);
            $subOutPARRate = $sub['OutstandingAmt'] > 0 ? round(($sub['ParAmtAS'] / $sub['OutstandingAmt']) * 100, 2) : 0;

            $dataExcel[] = [
                '',
                'SubTotal',
                'USD',
                $sub['total_borrowers'],
                $sub['total_loans'],
                number_format($sub['disbursed'], 2),
                number_format($sub['outstanding'], 2),
                number_format($sub['loan_balance'], 2),
                $sub['pars'],
                number_format($sub['par_amt'], 2),
                $subParRate . '%',
                number_format($sub['pd_principal'], 2),
                number_format($sub['pd_interest'], 2),
                number_format($sub['pd_penalty'], 2),
                $subArrearRate . '%',
                number_format($sub['Loans'],2),
                number_format($sub['OutstandingAmt'], 2),
                number_format($sub['OutPARs'],2),
                number_format($sub['ParAmtAS'], 2),
                $subOutPARRate . '%',
            ];

            foreach ($sub as $k => $v) {
                $grand[$k] += $v;
            }
        }

        $grandParRate = $grand['outstanding'] > 0 ? round(($grand['par_amt'] / $grand['outstanding']) * 100, 2) : 0;
        $grandArrearRate = round(($grand['pd_principal'] / $grand['outstanding']) * 100, 2);
        $grandOutPARRate = $grand['OutstandingAmt'] > 0 ? round(($grand['ParAmtAS'] / $grand['OutstandingAmt']) * 100, 2): 0;
        // $grandBorrowers = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->distinct()->count('ContractCustomerID');

        $grandBorrowers = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT')
        ->when(request('branch_id'), function ($q, $branch_id) {
            $q->where('Branch', $branch_id);
        })
        ->where('OutstandingAmountAS', '>', 0)
        ->distinct()
        ->count('ContractCustomerID');
        
        $dataExcel[] = [
            '',
            'GrandTotal',
            'USD',
            $grandBorrowers,
            $grand['total_loans'],
            number_format($grand['disbursed'], 2),
            number_format($grand['outstanding'], 2),
            number_format($grand['loan_balance'], 2),
            number_format($grand['pars'],2),
            number_format($grand['par_amt'], 2),
            $grandParRate . '%',
            number_format($grand['pd_principal'], 2),
            number_format($grand['pd_interest'], 2),
            number_format($grand['pd_penalty'], 2),
            $grandArrearRate . '%',
            number_format($grand['Loans'],2),
            number_format($grand['OutstandingAmt'], 2),
            number_format($grand['OutPARs'],2),
            number_format($grand['ParAmtAS'], 2),
            $grandOutPARRate . '%',
        ];
        $this->export_datas = $dataExcel;
    }

    public function collection()
    {
        return new Collection($this->export_datas);
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function headings(): array
    {
        return [
            "CO ID",
            "CO Name",
            "Currency",
            "#Borrowers",
            "#Total Loans",
            "Disbursed Amt.",
            "Oustanding Amt.",
            "Loan Balance",
            "#PARs",
            "PAR Amt.",
            "PAR Rate",
            "PD Principal",
            "PD Interest",
            "PD Penalty",
            "Arrear Rate",
            "#Loans",
            "Oustanding Amt.",
            "#PARs",
            "PAR Amt.",
            "PAR Rate",
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
            // AfterSheet::class => function(AfterSheet $event) {

            //     $sheet = $event->sheet->getDelegate();

            //     // Get the last row automatically
            //     $lastRow = $sheet->getHighestRow();

            //     // Apply border
            //     $sheet->getStyle("A1:O{$lastRow}")
            //         ->applyFromArray([
            //             'borders' => [
            //                 'allBorders' => [
            //                     'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            //                 ],
            //             ],
            //         ]);
            // },
        ];
    }
}