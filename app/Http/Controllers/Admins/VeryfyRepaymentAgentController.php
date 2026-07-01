<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BranchCode;
use App\Exports\MorakotExport;
use App\Exports\BranchExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class VeryfyRepaymentAgentController extends Controller
{
    public function index(Request $request)
    {
        if (request()->ajax()) {
            if (!$request->filled('date')) {
                return response()->json([
                    'draw'            => intval($request->draw),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => []
                ]);
            }

            $results         = session('veryfy_results', []);
            $recordsFiltered = count($results);
            $start           = intval($request->start ?? 0);
            $length          = intval($request->length ?? 10);
            $data            = array_slice($results, $start, $length);

            return response()->json([
                'draw'            => intval($request->draw),
                'recordsTotal'    => $recordsFiltered,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        return view('verify_repayment_agent.index');
    }

    public function importVeryfyRepaymentAgent(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $results       = self::processing($request);
        // ✅ Read branchResults from session (set inside processing())
        $branchResults = session('veryfy_results_branch', []);

        $total     = count($results);
        // ✅ Count matched/unmatched from branchResults which has Reason field
        $matched   = count(array_filter($branchResults, fn($r) => empty($r['Reason'])));
        $unmatched = count(array_filter($branchResults, fn($r) => !empty($r['Reason'])));

        session([
            'veryfy_results'   => $results,
            'veryfy_total'     => $total,
            'veryfy_matched'   => $matched,
            'veryfy_unmatched' => $unmatched,
        ]);

        return response()->json([
            'success'   => true,
            'total'     => $total,
            'matched'   => $matched,
            'unmatched' => $unmatched,
            'message'   => $total . ' accounts loaded',
        ]);
    }

    public static function processing(Request $request)
    {
        $data        = Excel::toArray([], $request->file('file'));
        $rows        = $data[0];
        $branchCodes = BranchCode::pluck('code', 'abbreviations');

        $loans = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
            ->leftJoin('MKT_LOAN_PRODUCT as LP', 'LC.LoanProduct', '=', 'LP.ID')
            ->leftJoin('MKT_PAST_DUE as PD', 'LC.ID', '=', 'PD.LoanID')
            ->leftJoin('MKT_REP_SCHEDULE as REP', 'PD.LoanID', '=', 'REP.LoanID')
            ->select([
                'LC.ID as LDNumber',
                'LC.ContractCustomerID',
                'LC.Branch',
                'LC.Account',
                'LC.Currency',
                'LC.Amount',
                'LC.Disbursed',
                'LC.LoanBalanceAS',
                'LC.OutstandingAmountAS',
                'LC.InterestRate',
                'LC.AIRAS',
                'LC.AIRCurrentAS',
                'LC.AccrIntPerDay',
                'LC.TotalInterest',
                'LC.ValueDate as DisburseDate',
                'LC.MaturityDate',
                'CUST.Officer',
                'CUST.LastNameEn',
                'CUST.FirstNameEn',
                'CUST.Gender',
                'CUST.Mobile1',
                'CUST.Mobile2',
                'PD.TotODAmountAS as TotaArr',
                'PD.TotPrincipalDueAS as PricipalArr',
                'PD.TotInterestDueAS as InteresArr',
                'PD.TotPenaltyDueAS as PenaltyArr',
                'PD.TotChargeDueAS as ChargeArr',
                'PD.Category',
                'PD.Currency as ArrCurrency',
                'REP.LoanID',
                'REP.CollectionDate',
                'REP.Principal',
                'REP.Interest',
                'REP.Charge',
                'REP.Balance',
                'REP.Curr',
                'REP.RepStatus',
                'LP.Description as LoanProduct',
            ])
        ->get();

        // Build lookup: finalAccount => loan record
        $loanLookup = [];
        foreach ($loans as $loan) {
            $account = $loan->Account;
            if (substr($account, 3, 1) === 'H') {
                $suffix = substr($account, -4);
            } elseif (substr($account, 2, 1) === 'M') {
                $suffix = substr($account, -5);
            } else {
                $suffix = substr($account, -6);
            }
            $finalAccount              = $suffix . ($branchCodes[$loan->Branch] ?? '');
            $loanLookup[$finalAccount] = $loan;
        }

        $configeAgent = [
            'WING' => '2789101',
            'TUME' => '2789103',
            'AMKR' => '2789104',
            'ACLB' => '2789106',
        ];

        $repStatusMap = [
            0 => 'Arrears',
            1 => 'Due Date',
            2 => 'Prepaid',
        ];

        $results       = [];
        $branchResults = [];

        foreach ($rows as $key => $row) {
            if ($key > 0 && !empty($row[0])) {
                $uploadedAccount  = trim($row[0]);
                $uploadedName     = trim($row[1] ?? '');
                $uploadedPhone    = trim($row[2] ?? '');
                $uploadedCurrency = trim($row[3] ?? '');
                $uploadedAmount   = trim($row[4] ?? '');
                $uploadedAgent    = trim($row[6] ?? '');

                $DrCategory        = $configeAgent[$uploadedAgent] ?? null;
                $CrAccount         = 'DD' . mb_substr($uploadedAccount, 0, -2);
                $Currency          = DB::connection('pgsql')->table('MKT_CURRENCY')->where('ID', $uploadedCurrency)->first();
                if ($uploadedCurrency=='USD') {
                    $ExchangeRate = 1;
                }else {
                    $ExchangeRate = $request->exchange_rate;
                }
                // $ExchangeRate      = $Currency->MidRate ?? null;
                $uploadedLCYAmount = $uploadedAmount * $ExchangeRate;

                $loan         = $loanLookup[$uploadedAccount] ?? null;
                // ✅ System full name from DB
                $loanFullName = $loan ? trim(($loan->LastNameEn ?? '') . ' ' . ($loan->FirstNameEn ?? '')) : '';
               
                // ✅ Check all 4 conditions if account found
                // if ($loan) {
                //     $mobile1 = $loan->Mobile1 ?? '';
                //     $mobile2 = $loan->Mobile2 ?? '';
                //     $nameMatch     = $uploadedName !== '' && (stripos($loanFullName, $uploadedName) !== false || stripos($uploadedName, $loanFullName) !== false);
                //     $phoneMatch    = $uploadedPhone !== '' && (str_contains($mobile1, $uploadedPhone) || str_contains($mobile2, $uploadedPhone));
                //     $matchCurrency = $uploadedCurrency !== '' && str_contains($loan->Currency, $uploadedCurrency);

                //     // ✅ If any condition fails, treat as not found
                //     if (!$nameMatch || !$phoneMatch || !$matchCurrency) {
                //         $loan = null;
                //     }
                // }

                if ($loanFullName !== '') {
                    $fullName = $loanFullName;
                }else {
                    $fullName = '(Error: )';
                }
                if (!$loan) {
                    // ✅ Branch: show #N/A for missing fields
                    $branchResults[] = [
                        'Branch'      => '#N/A',
                        'DrAccount'   => '#N/A',
                        'DrCategory'  => '#N/A',
                        'DrCurrency'  => '#N/A',
                        'CrAccount'   => '#N/A',
                        'CrCategory'  => '#N/A',
                        'CrCurrency'  => '#N/A',
                        'Amount'      => '#N/A',
                        'LDNumber'    => '#N/A',
                        'CCY'         => '#N/A',
                        'KHName'      => '#N/A',
                        'Outstanding' => '#N/A',
                        'TotaArr'     => '#N/A',
                        'PricipalArr' => '#N/A',
                        'InteresArr'  => '#N/A',
                        'PenaltyArr'  => '#N/A',
                        'ChargeArr'   => '#N/A',
                        'DateCol'     => '#N/A',
                        'TotalCol'    => '0',
                        'Principal'   => '0',
                        'Interest'    => '0',
                        'Charge'      => '0',
                        'LoanProduct' => '#N/A',
                        'Note'        => '#N/A',
                        'Agent'       => '#N/A',
                        'Officer'     => '#N/A',
                        'ClientTel'   => '#N/A',
                    ];
                    
                    // ✅ Morakot: keep as normal
                    $results[] = [
                        'Branch'           => '#VALUE!',
                        'DrAccount'        => '',
                        'DrCategory'       => '#VALUE!',
                        'DrCurrency'       => '0',
                        'CrAccount'        => $CrAccount,
                        'CrCategory'       => '3852204',
                        'CrCurrency'       => '0',
                        'Amount'           => '0',
                        'LCYAmount'        => '#N/A',
                        'ExchangeRate'     => '#N/A',
                        'Transaction'      => 40,
                        'TranDate'         => $request->date,
                        'Reference'        => $loanFullName,
                        'Note'             => $uploadedAgent . ' ' . $fullName,
                        'DrGLKey'          => '',
                        'CrGLKey'          => '',
                        'Module'           => 'FT',
                        'Officer'          => '',
                        'DisbursementList' => '',
                        'TargetBranch'     => 'HQ',
                        'TargetBranchDrCr' => 'Dr',
                    ];
                    continue;
                }

                // ✅ Note condition based on amounts and collection date
                $totaArr        = $loan->TotaArr ?? 0;
                $pricipalArr    = $loan->PricipalArr ?? 0;
                $chargeArr      = $loan->ChargeArr ?? 0;
                $collectionDate = $loan->CollectionDate ?? null;

                if (($totaArr + $pricipalArr + $chargeArr) > 0) {
                    $note = 'Arrears';
                } elseif ($collectionDate == $request->date) {
                    $note = 'Due Date';
                } else {
                    $note = 'Prepaid';
                }

                // ✅ Morakot result
                $results[] = [
                    'Branch'           => $loan->Branch,
                    'DrAccount'        => '',
                    'DrCategory'       => $DrCategory,
                    'DrCurrency'       => $uploadedCurrency,
                    'CrAccount'        => $CrAccount,
                    'CrCategory'       => '3852204',
                    'CrCurrency'       => $uploadedCurrency,
                    'Amount'           => $uploadedAmount,
                    'LCYAmount'        => $uploadedLCYAmount,
                    'ExchangeRate'     => $ExchangeRate,
                    'Transaction'      => 40,
                    'TranDate'         => $request->date,
                    'Reference'        => $loanFullName,
                    'Note'             => $uploadedAgent . ' ' . $fullName,
                    'DrGLKey'          => '',
                    'CrGLKey'          => '',
                    'Module'           => 'FT',
                    'Officer'          => $loan->Officer,
                    'DisbursementList' => '',
                    'TargetBranch'     => 'HQ',
                    'TargetBranchDrCr' => 'Dr',
                ];

                // ✅ Branch result — clean fields only (no Morakot fields)
                $branchResults[] = [
                    'Branch'      => $loan->Branch,
                    'DrAccount'   => '',
                    'DrCategory'  => $DrCategory,
                    'DrCurrency'  => $uploadedCurrency,
                    'CrAccount'   => $CrAccount,
                    'CrCategory'  => '3852204',
                    'CrCurrency'  => $uploadedCurrency,
                    'Amount'      => $uploadedAmount,
                    'LDNumber'    => $loan->LDNumber,
                    'CCY'         => $uploadedCurrency,
                    'KHName'      => $loanFullName,
                    'Outstanding' => $loan->Disbursed,
                    'TotaArr'     => $loan->TotaArr,
                    'PricipalArr' => $loan->PricipalArr,
                    'InteresArr'  => $loan->InteresArr,
                    'PenaltyArr'  => $loan->PenaltyArr,
                    'ChargeArr'   => $loan->ChargeArr,
                    'DateCol'     => $loan->CollectionDate,
                    'TotalCol'    => ($loan->Charge ?? 0) + ($loan->Principal ?? 0) + ($loan->Interest ?? 0),
                    'Principal'   => $loan->Principal,
                    'Interest'    => $loan->Interest,
                    'Charge'      => $loan->Charge,
                    'LoanProduct' => $loan->LoanProduct,
                    'Note'        => $note,
                    'Agent'       => $uploadedAgent,
                    'Officer'     => $loan->Officer,
                    'ClientTel'   => trim($loan->Mobile1 . ' ' . $loan->Mobile2),
                ];
            }
        }

        // ✅ Save branchResults to session
        session(['veryfy_results_branch' => $branchResults]);

        return $results;
    }

    public function downloadToMorakot(Request $request)
    {
        $results = session('veryfy_results', []);

        if (empty($results)) {
            return back()->with('error', 'No data to download. Please import a file first.');
        }

        $fileName = 'uploadToMorakot_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new MorakotExport($results), $fileName);
    }

    public function downloadToBranch(Request $request)
    {
        $results = session('veryfy_results_branch', []);

        if (empty($results)) {
            return back()->with('error', 'No data to download. Please import a file first.');
        }

        $fileName = 'Branch_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new BranchExport($results), $fileName);
    }
}