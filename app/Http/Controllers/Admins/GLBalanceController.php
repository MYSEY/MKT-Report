<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRolePermission;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class GLBalanceController extends Controller
{
    use HasRolePermission;
    public static function getBranchs(){
        $branchs = DB::connection('pgsql')->table('MKT_BRANCH')->get();
        return $branchs;
    }
    public static function getData($request) {
        $AccessBranch = Auth::user()->AccessBranch;
        $branches = preg_split('/\s+/', trim($AccessBranch));
        $dateInput = $request->get('date') ?? date('Y-m');
        
        $filterDate = Carbon::parse($dateInput)->subMonth()->format('Y-m');
        $currency_date = Carbon::parse()->subMonth()->format('Y-m');
        $previousYear = null;
        $previousMonth = null;
        if($filterDate == $currency_date){
            $name_table = "MKT_GL_BALANCE";
        }else{
            $name_table = "MKT_GL_BALANCE_BACKUP";
            $previousYear = Carbon::parse($dateInput)->format('Y');
            $previousMonth = Carbon::parse($dateInput)->format('m');
        }
        $query = DB::connection('pgsql')->table("MKT_GL_MAPPING as mp")
            ->leftJoin($name_table . ' as bl', 'mp.ID', '=', 'bl.ID')
            ->select(
                'mp.ID',
                'mp.Description',
                'mp.BalanceType',
                DB::raw('MAX(bl."Currency") as "Currency"'),
                DB::raw('SUM(bl."Balance") as "Balance"'),
                DB::raw('SUM(bl."LCYBalance") as "LCYBalance"'),
                DB::raw('SUM(bl."LCYPrevMonthBal") as "LCYPrevMonthBal"'),
                DB::raw('SUM(bl."CurrentMonthBal") as "CurrentMonthBal"'),
                DB::raw('SUM(bl."LCYCurrentMonthBal") as "LCYCurrentMonthBal"'),
                DB::raw('SUM(bl."PrevYearBal") as "PrevYearBal"'),
                DB::raw('SUM(bl."LCYPrevYearBal") as "LCYPrevYearBal"'),
                DB::raw('SUM(bl."YTDBal") as "YTDBal"'),
                DB::raw('SUM(bl."LCYYTDBal") as "LCYYTDBal"'),
                DB::raw('SUM(bl."LCYBalance") as "LCYBalance"')
            )
            ->orderByRaw("MAX(bl.\"Currency\") = 'KHR' DESC")
            ->orderBy('mp.ID', 'asc')
            ->where('bl.Branch', '<>', '');
        // --- Filters ---
        if($branches[0] == "HQ"){
            if ($request->branch_id && $request->branch_id != 'ALL') {
                $query->where('bl.Branch', $request->branch_id);
            }
        }else{
            $query->where('bl.Branch', $branches[0]);
        }
        if ($request->currency) {
          $query->where('bl.Currency', $request->currency);
        }
        if ($previousYear) {
            $query->where('bl.GLYear', $previousYear);
        }
        if ($previousMonth) {
            $query->where('bl.GLMonth', $previousMonth);
        }
        
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('bl."ID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('mp."ID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('mp."Description"::text'), 'like', "%{$search}%");
            });
        }
        $query->groupBy('mp.ID');
        return $query;
    }
    public static function getDataDetail($request) {
        $AccessBranch = Auth::user()->AccessBranch;
        $branches = preg_split('/\s+/', trim($AccessBranch));
        $dateInput = $request->get('date') ?? date('Y-m');
        $id = $request->id;
        
        $filterDate = Carbon::parse($dateInput)->format('Y-m');
        $query = DB::connection('pgsql')->table('MKT_JOURNAL as jn')
            ->leftJoin('MKT_GL_MAPPING_DE as mp', 'jn.GL_KEYS', '=', 'mp.ConsolKey')
            ->leftJoin('MKT_TRANSACTION as tst', 'jn.Transaction', '=', 'tst.ID')
            ->select(
                'mp.ID',
                'tst.Description as Tst_Description',
                DB::raw("CONCAT(jn.\"Transaction\", ' - ', tst.\"Description\") as \"Transaction\""),
                'jn.Branch', 
                'jn.Description', 
                DB::raw('SUBSTRING(jn."Description" FROM 1 FOR 4) as "SortCut"'),
                'jn.Currency',
                'jn.Reference',
                'jn.TransactionDate',
                'jn.GL_KEYS',
                'jn.Amount',
                'jn.PrevBalance',
                'jn.DebitCredit',
                DB::raw("CASE WHEN jn.\"DebitCredit\" = 'Dr' THEN jn.\"Amount\" ELSE 0 END as \"Debit\""),
                DB::raw("CASE WHEN jn.\"DebitCredit\" = 'Cr' THEN jn.\"Amount\" ELSE 0 END as \"Credit\""),
                DB::raw("CASE 
                    WHEN jn.\"DebitCredit\" = 'Dr' THEN (COALESCE(jn.\"Amount\", 0) + COALESCE(jn.\"PrevBalance\", 0))
                    WHEN jn.\"DebitCredit\" = 'Cr' THEN ((COALESCE(jn.\"Amount\", 0) * -1) + COALESCE(jn.\"PrevBalance\", 0))
                    ELSE COALESCE(jn.\"PrevBalance\", 0)
                END as \"balance\"")
            )
            ->where('jn.Branch', '<>', '')
            ->whereRaw('LENGTH(mp."ID"::text) = 8')
            ->when($filterDate, function ($q) use ($filterDate) {
                return $q->where('jn.TransactionDate', 'ILIKE', '%' . $filterDate . '%'); 
            })
            // Optional: Filter by mp.ID when loading detail view
            ->when(!empty($id), function ($q) use ($id) {
                return $q->where('mp.ID', $id);
            })
            ->orderBy('jn.Branch', 'asc')
            ->orderBy('jn.TransactionDate', 'asc');
        // --- Filters ---
        if($branches[0] == "HQ"){
            if ($request->branch_id && $request->branch_id != 'ALL') {
                $query->where('jn.Branch', $request->branch_id);
            }
        }else{
            $query->where('jn.Branch', $branches[0]);
        }
        if ($request->currency) {
          $query->where('jn.Currency', $request->currency);
        }
        
        $search = request()->input('search.value');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $term = "%{$search}%";

                $q->where(DB::raw('jn."Branch"::text'), 'ILIKE', $term)
                ->orWhere(DB::raw('jn."Reference"::text'), 'ILIKE', $term)
                ->orWhere(DB::raw('jn."Description"::text'), 'ILIKE', $term)
                // ->orWhere(DB::raw('mp."ID"::text'), 'ILIKE', $term)
                // ->orWhere(DB::raw('jn."GL_KEYS"::text'), 'ILIKE', $term)
                // Search inside the Joined Transaction ID & Description
                ->orWhere(DB::raw('jn."Transaction"::text'), 'ILIKE', $term)
                ->orWhere(DB::raw('tst."Description"::text'), 'ILIKE', $term);
            });
        }
        return $query;
    }
    public function index(Request $request) {
        if (!$this->denyPermission('GL Balance View')) {
            return view('page.access_page');
        }
        
        if (request()->ajax()) {
            // ១. ទាញ Query ពី getDataTB (ដែលមិនទាន់មាន GroupBy)
            $baseQuery = self::getData($request);
            
            $groupedQuery = clone $baseQuery;
            $groupedQuery->groupBy('mp.ID', 'mp.Description', 'mp.BalanceType');

            // ៣. រាប់ចំនួន RecordsFiltered (ប្រើ Sub-query)
            $recordsTotal = DB::connection('pgsql')
                ->table(DB::raw("({$groupedQuery->toSql()}) as sub"))
                ->mergeBindings($groupedQuery)
                ->count();

            // ៥. ទាញទិន្នន័យសម្រាប់បង្ហាញតាមទំព័រ
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));
            
            if ($limit == -1) {
                $data = $groupedQuery->get();
            } else {
                $data = $groupedQuery->offset($start)->limit($limit)->get();
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal, 
                'data' => $data,
            ]);
        }
        
        $branchs = self::getBranchs();
        return view('mkt-reports.gl-balances.index', compact('branchs'));
    }
    public function detail(Request $request){
        if (!$this->denyPermission('GL Balance View')) {
            return view('page.access_page');
        }
        
        if (request()->ajax()) {
            // ១. ទាញ Query ពី getDataTB (ដែលមិនទាន់មាន GroupBy)
            $baseQuery = self::getDataDetail($request);
            
            $groupedQuery = clone $baseQuery;
            // $groupedQuery->groupBy('mp.ID','jn.Description');

            // ៣. រាប់ចំនួន RecordsFiltered (ប្រើ Sub-query)
            $recordsTotal = DB::connection('pgsql')
                ->table(DB::raw("({$groupedQuery->toSql()}) as sub"))
                ->mergeBindings($groupedQuery)
                ->count();

            // ៥. ទាញទិន្នន័យសម្រាប់បង្ហាញតាមទំព័រ
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));
            
            if ($limit == -1) {
                $data = $groupedQuery->get();
            } else {
                $data = $groupedQuery->offset($start)->limit($limit)->get();
            }
            // dd($data);
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal, 
                'data' => $data,
            ]);
        }
        
        $branchs = self::getBranchs();
        return view('mkt-reports.gl-balances.detail', compact('branchs'));
    }
}
