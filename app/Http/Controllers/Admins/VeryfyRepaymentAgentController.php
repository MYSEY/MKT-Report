<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BranchCode;
use App\Exports\MorakotExport;
use App\Exports\BranchExport;
use App\Traits\HasRolePermission;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class VeryfyRepaymentAgentController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('Veryfy Repayment Agent Report');
    }
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

        // ✅ Query 1: Loan + Customer + PastDue
        $loans = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
            ->leftJoin('MKT_LOAN_PRODUCT as LP', 'LC.LoanProduct', '=', 'LP.ID')
            ->leftJoin('MKT_PAST_DUE as PD', 'LC.ID', '=', 'PD.LoanID')
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
                'LP.Description as LoanProduct',
            ])
        ->get();

        $startDate = date('Y-m-01', strtotime($request->date)); // first day of month
        $endDate   = date('Y-m-t', strtotime($request->date));  // last day of month

        $repSchedules = DB::connection('pgsql')
        ->table('MKT_REP_SCHEDULE')
        ->select(['LoanID', 'CollectionDate', 'Principal', 'Interest', 'Charge', 'Balance', 'Curr', 'RepStatus'])
        ->whereBetween('CollectionDate', [$startDate, $endDate])->get()
        ->keyBy('LoanID');

        // ✅ Build loanLookup (same as Excel K column formula)
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
            $finalAccount   = $suffix . ($branchCodes[$loan->Branch] ?? '');
            $loanLookup[$finalAccount] = $loan;
        }

        $configeAgent = [
            'WING' => '2789101',
            'TUME' => '2789103',
            'AMKR' => '2789104',
            'ACLB' => '2789106',
        ];

        $results       = [];
        $branchResults = [];

        foreach ($rows as $key => $row) {
            if ($key > 0 && !empty($row[0])) {
                $uploadedAccount  = trim($row[0]);
                $uploadedName     = trim($row[1] ?? '');
                $uploadedPhone    = trim($row[2] ?? '');
                $uploadedCurrency = trim($row[3] ?? '');
                $uploadedAmount   = (float) trim($row[4] ?? 0);
                $uploadedAgent    = trim($row[6] ?? '');
                $DrCategory = $configeAgent[$uploadedAgent] ?? null;
                // ✅ Fix CrAccount
                $CrAccount = 'DD' . mb_substr($uploadedAccount, 0, -2);

                if ($uploadedCurrency === 'USD') {
                    $ExchangeRate      = '1.0000000000000000';
                    $uploadedLCYAmount = bcmul((string) $uploadedAmount, $ExchangeRate, 16);
                } else {
                    $ExchangeRate      = number_format((float) $request->exchange_rate, 16, '.', '');
                    $uploadedLCYAmount = bcmul((string) $uploadedAmount, $ExchangeRate, 16);
                }

                $loan         = $loanLookup[$uploadedAccount] ?? null;
                $loanFullName = $loan ? trim(($loan->LastNameEn ?? '') . ' ' . ($loan->FirstNameEn ?? '')) : '';
                $fullName     = $loanFullName !== '' ? $loanFullName : '(Error: )';
                // ✅ Fix duplicate count
                $dupCount     = collect($rows)->filter(fn($r) => isset($r[0]) && trim($r[0]) === $uploadedAccount)->count();
                $duplicateMsg = $dupCount > 1 ? ' (' . $dupCount . ' Times)' : '';

                $rep    = $loan ? ($repSchedules[$loan->LDNumber] ?? null) : null;
                $collectionDate = $rep->CollectionDate ?? null;
                $principal      = $rep->Principal ?? 0;
                $interest       = $rep->Interest ?? 0;
                $charge         = $rep->Charge ?? 0;
                $totalCol       = $principal + $interest + $charge;
                $CrAccount  = $loan->Account ?? '#N/A';
                if (!$loan) {
                    $results[] = [
                        'Branch'           => '#VALUE!',
                        'DrAccount'        => '',
                        'DrCategory'       => '#VALUE!',
                        'DrCurrency'       => '0',
                        'CrAccount'        => $CrAccount,
                        'CrCategory'       => '3852204',
                        'CrCurrency'       => '0',
                        'Amount'           => $uploadedAmount,
                        'LCYAmount'        => '#N/A',
                        'ExchangeRate'     => '#N/A',
                        'Transaction'      => 40,
                        'TranDate'         => $request->date,
                        'Reference'        => $loanFullName,
                        'Note'             => $uploadedAgent . ' ' . $fullName . $duplicateMsg,
                        'DrGLKey'          => '',
                        'CrGLKey'          => '',
                        'Module'           => 'FT',
                        'Officer'          => '',
                        'DisbursementList' => '',
                        'TargetBranch'     => 'HQ',
                        'TargetBranchDrCr' => 'Dr',
                    ];

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
                    continue;
                }

                $PricipalArr     = $loan->PricipalArr ?? 0;
                $InteresArr = $loan->InteresArr ?? 0;
                $chargeArr   = $loan->ChargeArr ?? 0;

                if (($PricipalArr + $InteresArr + $chargeArr) > 0) {
                    $note = 'Arrears';
                } elseif ($collectionDate == $request->date) {
                    $note = 'Due Date';
                } else {
                    $note = 'Prepaid';
                }

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
                    'Note'             => $uploadedAgent . ' ' . $fullName . $duplicateMsg,
                    'DrGLKey'          => '',
                    'CrGLKey'          => '',
                    'Module'           => 'FT',
                    'Officer'          => '',
                    'DisbursementList' => '',
                    'TargetBranch'     => 'HQ',
                    'TargetBranchDrCr' => 'Dr',
                ];

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
                    'Outstanding' => $loan->OutstandingAmountAS,
                    'TotaArr'     => $loan->TotaArr,
                    'PricipalArr' => $loan->PricipalArr,
                    'InteresArr'  => $loan->InteresArr,
                    'PenaltyArr'  => $loan->PenaltyArr,
                    'ChargeArr'   => $loan->ChargeArr,
                    'DateCol'     => $collectionDate,
                    'TotalCol'    => $totalCol,
                    'Principal'   => $principal,
                    'Interest'    => $interest,
                    'Charge'      => $charge,
                    'LoanProduct' => $loan->LoanProduct,
                    'Note'        => $note,
                    'Agent'       => $uploadedAgent,
                    'Officer'     => '',
                    'ClientTel'   => trim(($loan->Mobile1 ?? '') . ' ' . ($loan->Mobile2 ?? '')),
                ];
            }
        }
        session(['veryfy_results_branch' => $branchResults]);
        return $results;
    }

    public function downloadToMorakot(Request $request)
    {
        $results = session('veryfy_results', []);
        if (empty($results)) {
            return back()->with('error', 'No data to download. Please import a file first.');
        }
        $fileName = 'uploadToMorakot_' . date('Ymd_His') . '.csv';
        return Excel::download(new MorakotExport($results), $fileName);
    }
    public function downloadToBranch(Request $request)
    {
        $results = session('veryfy_results_branch', []);
        if (empty($results)) {
            return back()->with('error', 'No data to download. Please import a file first.');
        }
        $fileName = 'tmp_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new BranchExport($results), $fileName);
    }
}