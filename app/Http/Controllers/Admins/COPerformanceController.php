<?php

namespace App\Http\Controllers\Admins;

use App\Exports\ExportCOPerformance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class COPerformanceController extends Controller
{
    public function coPerformance(Request $request){
        if (request()->ajax()) {
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

                DB::raw('SUM("LC"."Disbursed") AS TotalDisbursed'),
                DB::raw('SUM("LC"."OutstandingAmountAS") AS TotalOutstanding'),
                DB::raw('SUM("LC"."LoanBalanceAS") AS TotalLoanBalanceAs'),

                DB::raw('COUNT(DISTINCT "LC"."LoanApplicationID") AS "TotalLoans"'),
                DB::raw('COUNT(DISTINCT "LC"."ContractCustomerID") AS "TotalBorrowers"'),

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
            
            $search = request()->input('search.value');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('LC.ContractOfficerID', 'like', "%{$search}%")
                    ->orWhere('OFFICER.FirstName', 'like', "%{$search}%")
                    ->orWhere('OFFICER.LastName', 'like', "%{$search}%")
                    ->orWhere('LC.Currency', 'like', "%{$search}%")
                    ->orWhere('CUST.Gender', $search)
                    ->orWhere('LC.Branch', 'like', "%{$search}%");
                });
            }
            $data = $query->get();

            // ---- PROCESS GROUP + SUBTOTALS ----
            $currency = DB::connection('pgsql')->table('MKT_CURRENCY')->where('ID','KHR')->select('ID','ReportingRate')->first();
            $exchangeRate = (float)$currency->ReportingRate;

            $emptyTotals = [
                'TotalBorrowers' => 0,
                'TotalLoans' => 0,
                'TotalDisbursed' => 0,
                'TotalOutstanding' => 0,
                'TotalLoanBalanceAs' => 0,
                'Pars' => 0,
                'ParAmount' => 0,
                'TotalPDPrincipal' => 0,
                'TotalPDInterest' => 0,
                'TotalPDPenalty' => 0,
                'ArrearRate' => 0,
                'Loans' => 0,
                'OutstandingAmt' => 0,
                'OutPARs' => 0,
                'ParAmtAS' => 0,
            ];

            $groupTotals = $emptyTotals;
            $grandTotals = $emptyTotals;
            $currentGroup = null;
            $finalData = [];
            $allRows = (clone $query)->get();
            foreach ($allRows as $row) {
                // -----------------------------
                // GROUP CHANGE → PUSH SUBTOTAL
                // -----------------------------
                if ($currentGroup !== $row->ContractOfficerID) {
                    if ($currentGroup !== null) {

                        $subParRate = 0;
                        if ($groupTotals['TotalOutstanding'] > 0) {
                            $subParRate = $groupTotals['ParAmount'] / $groupTotals['TotalOutstanding'];
                        }
                        $subArrearRate = 0;
                        $subArrearRate = $groupTotals['TotalPDPrincipal'] / $groupTotals['TotalOutstanding'];

                        $subOutPARRate = 0;
                        if ($groupTotals['ParAmtAS'] > 0) {
                            $subOutPARRate = $groupTotals['ParAmtAS'] / $groupTotals['OutstandingAmt'];
                        }

                        $finalData[] = [
                            'ContractOfficerID' => '',
                            'DisplayName' => '<b style="color:#1f1f1f;font-size:14px;">SubTotal</b>',
                            'Currency' => 'USD',
                            'TotalBorrowers' => $groupTotals['TotalBorrowers'],
                            'TotalLoans' => $groupTotals['TotalLoans'],
                            'TotalDisbursed' => $groupTotals['TotalDisbursed'],
                            'TotalOutstanding' => $groupTotals['TotalOutstanding'],
                            'TotalLoanBalanceAs' => $groupTotals['TotalLoanBalanceAs'],
                            'Pars' => $groupTotals['Pars'],
                            'ParAmount' => $groupTotals['ParAmount'],
                            'parRate' => round($subParRate * 100, 2),
                            'TotalPDPrincipal' => $groupTotals['TotalPDPrincipal'],
                            'TotalPDInterest' => $groupTotals['TotalPDInterest'],
                            'TotalPDPenalty' => $groupTotals['TotalPDPenalty'],
                            'ArrearRate' => round($subArrearRate * 100, 2),
                            'Loans' => $groupTotals['Loans'],
                            'OutstandingAmt' => $groupTotals['OutstandingAmt'],
                            'OutPARs' => $groupTotals['OutPARs'],
                            'ParAmtAS' => $groupTotals['ParAmtAS'],
                            'OutPARRate' => round($subOutPARRate * 100, 2),
                            'subtotal_row' => true
                        ];

                        // ✅ ADD SUBTOTAL → GRAND TOTAL
                        foreach ($groupTotals as $key => $value) {
                            $grandTotals[$key] += $value;
                        }

                        // ✅ RESET AFTER ADDING
                        $groupTotals = $emptyTotals;
                    }
                    // RESET GROUP
                    $currentGroup = $row->ContractOfficerID;
                }

                // -----------------------------
                // ROW PAR RATE (BY CURRENCY)
                // -----------------------------
                $rowParRate = 0;
                if ($row->totaloutstanding > 0) {
                    $rowParRate = $row->ParAmount / $row->totaloutstanding;
                }

                $rowArrearRate = 0;
                $rowArrearRate = $row->TotalPDPrincipal / $row->totaloutstanding;

                $rowOutPARRate = 0;
                if ($row->ParAmtAS > 0) {
                    $rowOutPARRate = $row->ParAmtAS / $row->OutstandingAmt;
                }

                // -----------------------------
                // PUSH NORMAL ROW
                // -----------------------------
                $COName = trim(($row->LastName ?? '') . ' ' . ($row->FirstName ?? ''));
                $finalData[] = [
                    'ContractOfficerID' => $row->ContractOfficerID,
                    'DisplayName' => $COName !== '' ? $COName : '-',
                    'Currency' => $row->Currency,
                    'TotalBorrowers' => $row->TotalBorrowers,
                    'TotalLoans' => $row->TotalLoans,
                    'TotalDisbursed' => $row->totaldisbursed,
                    'TotalOutstanding' => $row->totaloutstanding,
                    'TotalLoanBalanceAs' => $row->totalloanbalanceas,
                    'Pars' => $row->Pars,
                    'ParAmount' => $row->ParAmount,
                    'parRate' => round($rowParRate * 100, 2),
                    'TotalPDPrincipal' => $row->TotalPDPrincipal,
                    'TotalPDInterest' => $row->TotalPDInterest,
                    'TotalPDPenalty' => $row->TotalPDPenalty,
                    'ArrearRate' => round($rowArrearRate * 100, 2),
                    'Loans' => $row->Loans,
                    'OutstandingAmt' => $row->OutstandingAmt,
                    'OutPARs' => $row->OutPARs,
                    'ParAmtAS' => $row->ParAmtAS,
                    'OutPARRate' => round($rowOutPARRate * 100, 2),
                    'subtotal_row' => false
                ];

                // -----------------------------
                // CONVERT TO USD FOR SUBTOTAL
                // -----------------------------
                $usdDisbursed = ($row->Currency === 'KHR') ? $row->totaldisbursed * $exchangeRate : $row->totaldisbursed;
                $usdOutstanding = ($row->Currency === 'KHR') ? $row->totaloutstanding * $exchangeRate : $row->totaloutstanding;
                $usdLoanBalance = ($row->Currency === 'KHR') ? $row->totalloanbalanceas * $exchangeRate : $row->totalloanbalanceas;
                $ParAmount = ($row->Currency === 'KHR') ? $row->ParAmount * $exchangeRate : $row->ParAmount;
                $TotalPDPrincipal = ($row->Currency === 'KHR') ? $row->TotalPDPrincipal * $exchangeRate : $row->TotalPDPrincipal;
                $TotalPDInterest = ($row->Currency === 'KHR') ? $row->TotalPDInterest * $exchangeRate : $row->TotalPDInterest;
                $TotalPDPenalty = ($row->Currency === 'KHR') ? $row->TotalPDPenalty * $exchangeRate : $row->TotalPDPenalty;
                
                // -----------------------------
                // ACCUMULATE SUBTOTALS (USD)
                // -----------------------------
                $groupTotals['TotalBorrowers'] += $row->TotalBorrowers;
                $groupTotals['TotalLoans'] += $row->TotalLoans;
                $groupTotals['TotalDisbursed'] += $usdDisbursed;
                $groupTotals['TotalOutstanding'] += $usdOutstanding;
                $groupTotals['TotalLoanBalanceAs'] += $usdLoanBalance;
                $groupTotals['Pars'] += $row->Pars;
                $groupTotals['ParAmount'] += $ParAmount;
                $groupTotals['TotalPDPrincipal'] += $TotalPDPrincipal;
                $groupTotals['TotalPDInterest'] += $TotalPDInterest;
                $groupTotals['TotalPDPenalty'] += $TotalPDPenalty;
                $groupTotals['Loans'] += $row->Loans;
                $groupTotals['OutstandingAmt'] += $row->OutstandingAmt;
                $groupTotals['OutPARs'] += $row->OutPARs;
                $groupTotals['ParAmtAS'] += $row->ParAmtAS;
            }
            // -----------------------------
            // FINAL SUBTOTAL
            // -----------------------------
            if ($currentGroup !== null) {
                $finalParRate = 0;
                if ($groupTotals['TotalOutstanding'] > 0) {
                    $finalParRate = $groupTotals['ParAmount'] / $groupTotals['TotalOutstanding'];
                }
                $finalArrearRate = 0;
                $finalArrearRate = $groupTotals['TotalPDPrincipal'] / $groupTotals['TotalOutstanding'];
                $finalOutPARRate = 0;
                if ($groupTotals['ParAmtAS'] > 0) {
                    $finalOutPARRate = $groupTotals['ParAmtAS'] / $groupTotals['OutstandingAmt'];
                }
                $finalData[] = [
                    'ContractOfficerID' => '',
                    'DisplayName' => '<b style="color:#1f1f1f;font-size:14px;">SubTotal</b>',
                    'Currency' => 'USD',
                    'TotalBorrowers' => $groupTotals['TotalBorrowers'],
                    'TotalLoans' => $groupTotals['TotalLoans'],
                    'TotalDisbursed' => $groupTotals['TotalDisbursed'],
                    'TotalOutstanding' => $groupTotals['TotalOutstanding'],
                    'TotalLoanBalanceAs' => $groupTotals['TotalLoanBalanceAs'],
                    'Pars' => $groupTotals['Pars'],
                    'ParAmount' => $groupTotals['ParAmount'],
                    'parRate' => round($finalParRate * 100, 2),
                    'TotalPDPrincipal' => $groupTotals['TotalPDPrincipal'],
                    'TotalPDInterest' => $groupTotals['TotalPDInterest'],
                    'TotalPDPenalty' => $groupTotals['TotalPDPenalty'],
                    'ArrearRate' => round($finalArrearRate * 100, 2),
                    'Loans' => $groupTotals['Loans'],
                    'OutstandingAmt' => $groupTotals['OutstandingAmt'],
                    'OutPARs' => $groupTotals['OutPARs'],
                    'ParAmtAS' => $groupTotals['ParAmtAS'],
                    'OutPARRate' => round($finalOutPARRate * 100, 2),
                    'subtotal_row' => true
                ];
                // ✅ ADD LAST SUBTOTAL → GRAND TOTAL
                foreach ($groupTotals as $key => $value) {
                    $grandTotals[$key] += $value;
                }
            }

            //grand total par rate
            $countQuery = clone $query;
            $recordsTotal = DB::connection('pgsql')->table(DB::raw("({$countQuery->toSql()}) as sub"))->mergeBindings($countQuery)->count();
            $recordsFiltered = DB::connection('pgsql')->table(DB::raw("({$countQuery->toSql()}) as sub"))->mergeBindings($countQuery)->count();

            $start = intval(request('start', 0));
            $limit = intval(request('length', 10));
            $finalData = array_slice($finalData, $start, $limit);

            $totalPages = ceil($recordsFiltered / $limit);
            $currentPage = floor($start / $limit) + 1;
            $grandBorrowers = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT')
            ->when(request('branch_id'), function ($q, $branch_id) {
                $q->where('Branch', $branch_id);
            })
            ->where('OutstandingAmountAS', '>', 0)
            ->distinct()
            ->count('ContractCustomerID');
            
            $grandParRate = 0;
            if ($grandTotals['TotalOutstanding'] > 0) {
                $grandParRate = $grandTotals['ParAmount'] / $grandTotals['TotalOutstanding'];
            }
            $grandArrearRate = 0;
            if ($grandTotals['TotalOutstanding'] > 0) {
                $grandArrearRate = $grandTotals['TotalPDPrincipal'] / $grandTotals['TotalOutstanding'];
            }
            
            $grandOutPARRate = 0;
            if ($grandTotals['ParAmtAS'] > 0) {
                $grandOutPARRate = $grandTotals['ParAmtAS'] / $grandTotals['OutstandingAmt'];
            }
            if ($currentPage === $totalPages) {
                $finalData[] = [
                    'ContractOfficerID' => '',
                    'DisplayName' => '<b style="color:#1f1f1f;font-size:14px;">GrandTotal</b>',
                    'Currency' => 'USD',
                    'TotalBorrowers' => $grandBorrowers,
                    'TotalLoans' => $grandTotals['TotalLoans'],
                    'TotalDisbursed' => $grandTotals['TotalDisbursed'],
                    'TotalOutstanding' => $grandTotals['TotalOutstanding'],
                    'TotalLoanBalanceAs' => $grandTotals['TotalLoanBalanceAs'],
                    'Pars' => $grandTotals['Pars'],
                    'ParAmount' => $grandTotals['ParAmount'],
                    'parRate' => round($grandParRate * 100, 2),
                    'TotalPDPrincipal' => $grandTotals['TotalPDPrincipal'],
                    'TotalPDInterest' => $grandTotals['TotalPDInterest'],
                    'TotalPDPenalty' => $grandTotals['TotalPDPenalty'],
                    'ArrearRate' => round($grandArrearRate * 100, 2),
                    'Loans' => $grandTotals['Loans'],
                    'OutstandingAmt' => $grandTotals['OutstandingAmt'],
                    'OutPARs' => $grandTotals['OutPARs'],
                    'ParAmtAS' => $grandTotals['ParAmtAS'],
                    'OutPARRate' => round($grandOutPARRate * 100, 2),
                    'grandtotal_row' => true
                ];
            }
            
            return response()->json([
                'draw' => intval(request('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $finalData
            ]);
        }
        
        // $data = DB::connection('pgsql')
        //     ->table('MKT_LOAN_CONTRACT as LC')
        //     ->select([
        //         'LC.ContractOfficerID',
        //         'US.DisplayName',
        //         'LC.Currency',
        //         'LC.Branch',
        //         DB::raw('SUM("LC"."Disbursed") AS TotalDisbursed'),
        //         DB::raw('SUM("LC"."OutstandingAmount") AS TotalOutstanding'),
        //         DB::raw('SUM("LC"."Amount") AS TotalAmount'),
        //         DB::raw('COUNT(DISTINCT "LC"."LoanApplicationID") AS "TotalLoans"'),
        //         DB::raw('COUNT(DISTINCT "LC"."ID") AS "TotalBorrowers"'),
        //     ])
        //     ->leftJoin('MKT_USER as US', 'LC.ContractOfficerID', '=', 'US.Officer')
        //     ->whereIn('LC.Currency', ['KHR', 'USD'])
        //     ->where('LC.Branch', '<>', '""')
        //     ->groupBy(
        //         'LC.ContractOfficerID',
        //         'US.DisplayName',
        //         'LC.Currency',
        //         'LC.Branch'
        // )->get();
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        $AssetClass = DB::connection('pgsql')->table('MKT_ASSET_CLASS')->select('ID', 'Description')->get();
        $data = DB::connection('pgsql')->table('MKT_DATES')->select('ID', 'SystemDate')->first();
        $currency = DB::connection('pgsql')->table('MKT_CURRENCY')->select('ID')->where('ID', 'USD')->first();
        return view('loans.co_performance',compact('branch','AssetClass','data','currency'));
    }

    public function coPerformanceDownload(Request $request){
        try {
            $data = DB::connection('pgsql')->table('MKT_DATES')->select('ID', 'SystemDate')->first();
            // Convert to Carbon
            $date = Carbon::parse($data->SystemDate);
            // Add current time
            $currentTime = now()->setTimezone('Asia/Phnom_Penh');
            $dateTime = $date->format('Y-m-d') . '-' . $currentTime->format('H-i');
            // File name
            $fileName = "CO Performance {$dateTime}.xlsx";
            return Excel::download(
                new ExportCOPerformance($request),
                $fileName
            );
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
