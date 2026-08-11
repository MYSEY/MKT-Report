<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BranchCode;
use App\Models\VerifyRepaymentAgent;
use App\Models\VerifyRepaymentAgentDetail;
use App\Models\VerifyRepaymentAgentBranchDetail;
use Illuminate\Support\Facades\Auth;
use App\Exports\MorakotExport;
use App\Exports\BranchExport;
use App\Traits\HasRolePermission;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
            $query = DB::table('verify_repayment_agents')->select('verify_repayment_agents.*');
            $recordsTotal = $query->count();

            // ✅ Search filter
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('branch', 'like', '%' . $search . '%')
                    ->orWhere('date', 'like', '%' . $search . '%');
                });
            }

            $recordsFiltered = $query->count();
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 10));
            $data = (clone $query)->offset($start)->limit($limit)->orderBy('id', 'desc')->get();
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        return view('verify_repayment_agent.index');
    }
    public function verifyRepaymentDetail(Request $request, $id)
    {
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        if (request()->ajax()) {
            $query = VerifyRepaymentAgentDetail::where('verify_repayment_agent_id', $id);
            if ($request->filled('branch_id')) {
                $query->where('Branch', $request->branch_id);
            }
            $recordsTotal = $query->count();
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('Branch', 'like', '%' . $search . '%')
                    ->orWhere('CrAccount', 'like', '%' . $search . '%')
                    ->orWhere('CrCurrency', 'like', '%' . $search . '%')
                    ->orWhere('Note', 'like', '%' . $search . '%')
                    ->orWhere('TranDate', 'like', '%' . $search . '%');
                });
            }

            $recordsFiltered = $query->count();
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 10));

            $data = (clone $query)->orderBy('Reference', 'asc')->offset($start)->limit($limit)->get()
            ->map(fn($item) => [
                'Branch'           => $item->Branch,
                'DrAccount'        => $item->DrAccount,
                'DrCategory'       => $item->DrCategory,
                'DrCurrency'       => $item->DrCurrency,
                'CrAccount'        => $item->CrAccount,
                'CrCategory'       => $item->CrCategory,
                'CrCurrency'       => $item->CrCurrency,
                'Amount'           => $item->Amount,
                'LCYAmount'        => $item->LCYAmount,
                'ExchangeRate'     => $item->ExchangeRate,
                'Transaction'      => $item->Transaction,
                'TranDate'         => $item->TranDate,
                'Reference'        => $item->Reference,
                'Note'             => $item->Note,
                'DrGLKey'          => $item->DrGLKey,
                'CrGLKey'          => $item->CrGLKey,
                'Module'           => $item->Module,
                'Officer'          => $item->Officer,
                'DisbursementList' => $item->DisbursementList,
                'TargetBranch'     => $item->TargetBranch,
                'TargetBranchDrCr' => $item->TargetBranchDrCr,
            ]);

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        return view('verify_repayment_agent.detail', compact('branch', 'id'));
    }
    
    public function importVeryfyRepaymentAgent(Request $request)
    {
        $data        = Excel::toArray([], $request->file('file'));
        $rows        = $data[0];
        $branchCodes = BranchCode::pluck('code', 'abbreviations');
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

        $startDate = date('Y-m-01', strtotime($request->date));
        $endDate   = date('Y-m-t', strtotime($request->date));

        $repSchedules = DB::connection('pgsql')
        ->table('MKT_REP_SCHEDULE')
        ->select(['LoanID', 'CollectionDate', 'Principal', 'Interest', 'Charge', 'Balance', 'Curr', 'RepStatus'])
        ->whereBetween('CollectionDate', [$startDate, $endDate])->get()
        ->keyBy('LoanID');

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

        $verifyrepayment = VerifyRepaymentAgent::create([
            'name'       => Auth::user()->DisplayName,
            'date'       => Carbon::now()->format('Y-m-d H:i:s'),
            'branch'     => Auth::user()->Branch,
            'created_by' => Auth::user()->DisplayName,
        ]);

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

                // ✅ Bug 2 fixed - set CrAccount once only
                $CrAccount = 'DD' . mb_substr($uploadedAccount, 0, -2);

                if ($uploadedCurrency === 'USD') {
                    $ExchangeRate      = '1.000000000000000000';
                    $uploadedLCYAmount = bcmul((string) $uploadedAmount, $ExchangeRate, 18);
                } else {
                    $ExchangeRate      = number_format((float) $request->exchange_rate, 18, '.', '');
                    $uploadedLCYAmount = bcmul((string) $uploadedAmount, $ExchangeRate, 18);
                }

                $loan         = $loanLookup[$uploadedAccount] ?? null;
                $loanFullName = $loan ? trim(($loan->LastNameEn ?? '') . ' ' . ($loan->FirstNameEn ?? '')) : '';
                $fullName     = $loanFullName !== '' ? $loanFullName : '(Error: )';
                $Reference    = $key;

                $dupCount     = collect($rows)->filter(fn($r) => isset($r[0]) && trim($r[0]) === $uploadedAccount)->count();
                $duplicateMsg = $dupCount > 1 ? ' (' . $dupCount . ' Times)' : '';

                $rep            = $loan ? ($repSchedules[$loan->LDNumber] ?? null) : null;
                $collectionDate = $rep->CollectionDate ?? null;
                $principal      = $rep->Principal ?? 0;
                $interest       = $rep->Interest ?? 0;
                $charge         = $rep->Charge ?? 0;
                $totalCol       = $principal + $interest + $charge;

                // ✅ Common fields for both matched and unmatched
                $commonFields = [
                    'verify_repayment_agent_id' => $verifyrepayment->id, // ✅ Bug 1 fixed
                    'created_by'                => Auth::user()->DisplayName,
                    'updated_by'                => null,
                ];

                if (!$loan) {
                    $results[] = array_merge([
                        'Branch'           => '#VALUE!',
                        'DrAccount'        => '',
                        'DrCategory'       => '#VALUE!',
                        'DrCurrency'       => '0',
                        'CrAccount'        => $CrAccount,
                        'CrCategory'       => '3852204',
                        'CrCurrency'       => '0',
                        'Amount'           => $uploadedAmount,
                        'LCYAmount'        => null,
                        'ExchangeRate'     => null,
                        'Transaction'      => 40,
                        'TranDate'         => $request->date,
                        'Reference'        => $Reference,
                        'Note'             => $uploadedAgent . ' ' . $fullName . $duplicateMsg,
                        'DrGLKey'          => '',
                        'CrGLKey'          => '',
                        'Module'           => 'FT',
                        'Officer'          => '',
                        'DisbursementList' => '',
                        'TargetBranch'     => 'HQ',
                        'TargetBranchDrCr' => 'Dr',
                    ], $commonFields);

                    $branchResults[] = array_merge([
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
                    ],$commonFields);
                    continue;
                }

                $pricipalArr = $loan->PricipalArr ?? 0;
                $interesArr  = $loan->InteresArr ?? 0;
                $chargeArr   = $loan->ChargeArr ?? 0;

                if (($pricipalArr + $interesArr + $chargeArr) > 0) {
                    $note = 'Arrears';
                } elseif ($collectionDate == $request->date) {
                    $note = 'Due Date';
                } else {
                    $note = 'Prepaid';
                }

                $results[] = array_merge([
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
                    'Reference'        => $Reference,
                    'Note'             => $uploadedAgent . ' ' . $fullName . $duplicateMsg,
                    'DrGLKey'          => '',
                    'CrGLKey'          => '',
                    'Module'           => 'FT',
                    'Officer'          => '',
                    'DisbursementList' => '',
                    'TargetBranch'     => 'HQ',
                    'TargetBranchDrCr' => 'Dr',
                ], $commonFields);

                $branchResults[] = array_merge([
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
                ],$commonFields);
            }
        }

        if (!empty($results)) {
            foreach (array_chunk($results, 100) as $chunk) {
                VerifyRepaymentAgentDetail::insert($chunk);
            }
        }
        if (!empty($branchResults)) {
            foreach (array_chunk($branchResults, 100) as $chunk) {
                VerifyRepaymentAgentBranchDetail::insert($chunk);
            }
        }
        return response()->json([
            'success'   => true,
            'message'   => 'accounts loaded',
        ]);
    }

    public function downloadToMorakot(Request $request,$id)
    {
        $results = VerifyRepaymentAgentDetail::where('verify_repayment_agent_id', $id)
            ->orderBy('Reference', 'asc')
            ->get()
            ->map(fn($item) => [
                'Branch'           => $item->Branch,
                'DrAccount'        => $item->DrAccount,
                'DrCategory'       => $item->DrCategory,
                'DrCurrency'       => $item->DrCurrency,
                'CrAccount'        => $item->CrAccount,
                'CrCategory'       => $item->CrCategory,
                'CrCurrency'       => $item->CrCurrency,
                'Amount'           => $item->Amount,
                'LCYAmount'        => $item->LCYAmount,
                'ExchangeRate'     => $item->ExchangeRate,
                'Transaction'      => $item->Transaction,
                'TranDate'         => $item->TranDate,
                'Reference'        => $item->Reference,
                'Note'             => $item->Note,
                'DrGLKey'          => $item->DrGLKey,
                'CrGLKey'          => $item->CrGLKey,
                'Module'           => $item->Module,
                'Officer'          => $item->Officer,
                'DisbursementList' => $item->DisbursementList,
                'TargetBranch'     => $item->TargetBranch,
                'TargetBranchDrCr' => $item->TargetBranchDrCr,
            ])
        ->toArray();

        if (empty($results)) {
            return back()->with('error', 'No data to download.');
        }
        $fileName = 'uploadToMorakot_' . date('Ymd_His') . '.csv';
        return Excel::download(new MorakotExport($results), $fileName);
    }
    public function downloadToBranch(Request $request,$id)
    {
        $results = VerifyRepaymentAgentBranchDetail::where('verify_repayment_agent_id', $id)
            ->get()
            ->map(fn($item) => [
                'Branch'        => $item->Branch,
                'DrAccount'     => $item->DrAccount,
                'DrCategory'    => $item->DrCategory,
                'DrCurrency'    => $item->DrCurrency,
                'CrAccount'     => $item->CrAccount,
                'CrCategory'    => $item->CrCategory,
                'CrCurrency'    => $item->CrCurrency,
                'Amount'        => $item->Amount,
                'LDNumber'      => $item->LDNumber,
                'CCY'           => $item->CCY,
                'KHName'        => $item->KHName,
                'Outstanding'   => $item->Outstanding,
                'TotaArr'       => $item->TotaArr,
                'PricipalArr'   => $item->PricipalArr,
                'InteresArr'    => $item->InteresArr,
                'PenaltyArr'    => $item->PenaltyArr,
                'ChargeArr'     => $item->ChargeArr,
                'DateCol'       => $item->DateCol,
                'TotalCol'      => $item->TotalCol,
                'Principal'     => $item->Principal,
                'Interest'      => $item->Interest,
                'Charge'        => $item->Charge,
                'LoanProduct'   => $item->LoanProduct,
                'Note'          => $item->Note,
                'Agent'         => $item->Agent,
                'Officer'       => $item->Officer,
                'ClientTel'     => $item->ClientTel,
            ])
        ->toArray();
        if (empty($results)) {
            return back()->with('error', 'No data to download. Please import a file first.');
        }
        $fileName = 'tmp_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new BranchExport($results), $fileName);
    }
}