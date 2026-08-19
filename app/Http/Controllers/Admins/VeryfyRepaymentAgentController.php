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
            $today = Carbon::now()->format('Y-m-d');
            $query = DB::table('verify_repayment_agents')
            ->select('verify_repayment_agents.*')
            ->where('date', 'like', $today . '%');

            // ✅ Search filter
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('branch', 'like', '%' . $search . '%')
                    ->orWhere('date', 'like', '%' . $search . '%');
                });
            }

            $recordsTotal = $query->count();
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

    public function verifyRepaymentAgentMonthly(Request $request)
    {
        if (request()->ajax()) {
            $query = DB::table('verify_repayment_agents')
            ->select('verify_repayment_agents.*');

            // ✅ Search filter
            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('branch', 'like', '%' . $search . '%')
                    ->orWhere('date', 'like', '%' . $search . '%');
                });
            }
            $recordsTotal = $query->count();
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
        return view('verify_repayment_agent.monthly');
    }

    public function verifyRepaymentDetail(Request $request, $id)
    {
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        if (request()->ajax()) {
            $query = VerifyRepaymentAgentDetail::where('verify_repayment_agent_id', $id);
            if ($request->filled('branch_id')) {
                $query->where('Branch', $request->branch_id);
            }
            
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
            $accessBranch = trim(preg_replace('/\s+ALL$/i', '', Auth::user()->AccessBranch));
            if(Auth::user()->Role == 'L06' || Auth::user()->Role == 'L07' || Auth::user()->Role == 'L01') {
                $query->where('Branch', $accessBranch);
            }

            $recordsTotal = $query->count();
            $recordsFiltered = $query->count();
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 10));

            $data = (clone $query)->offset($start)->limit($limit)->get()
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
        // ✅ Roll delete old data before insert
        $this->rollDeleteOldData();
        
        $results       = [];
        $branchResults = [];

        $verifyrepayment = VerifyRepaymentAgent::create([
            'name'       => Auth::user()->DisplayName,
            'date'       => Carbon::now()->format('Y-m-d H:i:s'),
            'branch'     => Auth::user()->Branch,
            'memo'       => $this->generateMemo(),
            'created_by' => Auth::user()->DisplayName,
        ]);

        $results       = [];
        $branchResults = [];

        // ✅ Get max Reference for today
        $today = Carbon::now()->format('Y-m-d');

        $maxReference = DB::table('verify_repayment_agent_details')
        ->join('verify_repayment_agents', 'verify_repayment_agent_details.verify_repayment_agent_id', '=', 'verify_repayment_agents.id')
        ->where('verify_repayment_agents.date', 'like', $today . '%')
        ->max('verify_repayment_agent_details.Reference');

        $referenceCounter = (int) ($maxReference ?? 0);

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
                $referenceCounter++;
                $Reference = $referenceCounter;

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
                    'created_at'                => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at'                => Carbon::now()->format('Y-m-d H:i:s'),
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
        $accessBranch = trim(preg_replace('/\s+ALL$/i', '', Auth::user()->AccessBranch));
        $query = VerifyRepaymentAgentDetail::where('verify_repayment_agent_id', $id);
        if ($accessBranch != 'HQ') {
            $query->where('Branch', $accessBranch);
        }

        $results = $query
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
        $query = VerifyRepaymentAgentBranchDetail::where('verify_repayment_agent_id', $id);
        $accessBranch = trim(preg_replace('/\s+ALL$/i', '', Auth::user()->AccessBranch));
        if ($accessBranch != 'HQ') {
            $query->where('Branch', $accessBranch);
        }
        $results = $query->get()
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
    private function generateMemo(): string
    {
        $today      = Carbon::now()->format('Y-m-d');
        $todayCount = VerifyRepaymentAgent::where('date', 'like', $today . '%')->count();
        $nextNumber = $todayCount + 1;
        return $today . ' (' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT) . ')';
        // Result: 2026-08-19 (01)
    }
    private function rollDeleteOldData(): void
    {
        $today    = Carbon::now()->format('Y-m-d');
        $oneMonth = Carbon::now()->subMonth()->format('Y-m-d');
        $latestDate = VerifyRepaymentAgent::orderBy('date', 'desc')->value('date');
        if (!$latestDate) return;
        $latestDay = Carbon::parse($latestDate)->format('Y-m-d');
        if ($latestDay === $today) return;
        $oldestRecord = VerifyRepaymentAgent::orderBy('date', 'asc')->first();
        if (!$oldestRecord) return;
        $oldestDay = Carbon::parse($oldestRecord->date)->format('Y-m-d');
        if ($oldestDay >= $oneMonth) return;
        $oldIds = VerifyRepaymentAgent::where('date', 'like', $oldestDay . '%')->pluck('id');
        if ($oldIds->isEmpty()) return;
        VerifyRepaymentAgentDetail::whereIn('verify_repayment_agent_id', $oldIds)->delete();
        VerifyRepaymentAgentBranchDetail::whereIn('verify_repayment_agent_id', $oldIds)->delete();
        VerifyRepaymentAgent::whereIn('id', $oldIds)->delete();
        \Log::info('Rolling delete oldest day: ' . $oldestDay . ' total records: ' . $oldIds->count());
    }
    public function checkDelete()
    {
        $today    = Carbon::now()->format('Y-m-d');
        $oneMonth = Carbon::now()->subMonth()->format('Y-m-d');
        $latestDate = VerifyRepaymentAgent::orderBy('date', 'desc')->value('date');

        if (!$latestDate) {
            return response()->json(['willDelete' => false]);
        }
        $latestDay = Carbon::parse($latestDate)->format('Y-m-d');
        if ($latestDay === $today) {
            return response()->json(['willDelete' => false]);
        }
        $oldestRecord = VerifyRepaymentAgent::orderBy('date', 'asc')->first();
        if (!$oldestRecord) {
            return response()->json(['willDelete' => false]);
        }
        $oldestDay = Carbon::parse($oldestRecord->date)->format('Y-m-d');
        if ($oldestDay >= $oneMonth) {
            return response()->json(['willDelete' => false]);
        }
        $oldIds       = VerifyRepaymentAgent::where('date', 'like', $oldestDay . '%')->pluck('id');
        $totalRecords = VerifyRepaymentAgentDetail::whereIn('verify_repayment_agent_id', $oldIds)->count();

        return response()->json([
            'willDelete'   => true,
            'oldestDay'    => $oldestDay,
            'totalRecords' => $totalRecords,
            'totalUploads' => $oldIds->count(),
        ]);
    }
    public function destroy($id)
    {
        $record = VerifyRepaymentAgent::findOrFail($id);
        VerifyRepaymentAgentDetail::where('verify_repayment_agent_id', $id)->delete();
        VerifyRepaymentAgentBranchDetail::where('verify_repayment_agent_id', $id)->delete();
        $record->delete();
        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully',
        ]);
    }
}