<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportCreditUnderwriting;
use App\Exports\ExportCBCNoneEquriry;

class CreditUnderwritingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            [$base, $filtered] = self::filteredQuery($request);

            $recordsTotal    = (clone $base)->count();
            $recordsFiltered = (clone $filtered)->count();
            $data            = self::getDatas($request)->get(); // paged

            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }

        return view('credit_underwriting.index');
    }

    public function download(Request $request)
    {
        return Excel::download(
            new ExportCreditUnderwriting($request->input('branch_id')),
            'credit_underwriting_report.xlsx'
        );
    }

    /**
     * Returns [$base, $filtered]: joins + branch filter, and the same with search applied.
     */
    private static function filteredQuery(Request $request): array
    {
        $db = DB::connection('pgsql');
        $lgSub = $db->table('MKT_LENDING_GUIDELINE as LG')
            ->join('MKT_LENDING_GUIDELINE_DE as LGD', 'LG.ID', '=', 'LGD.ID')
            ->where('LGD.Bank', '<>', '500')
            ->whereNotNull('LG.LoanAppID')
            ->where('LG.LoanAppID', '<>', '')
        ->groupBy('LG.LoanAppID')
        ->selectRaw('"LG"."LoanAppID", MAX("LG"."ID") as "ID", COUNT(DISTINCT "LGD"."Bank") as "NumOfOtherLender"');

        $cbcSub = $db->table('MKT_CBC as CBC')
            ->join('MKT_CBC_DE as CBCD', 'CBC.ID', '=', 'CBCD.ID')
            ->whereNotNull('CBCD.EnquiryID')
            ->where('CBCD.EnquiryID', '<>', '')
            ->where('CBCD.EnquiryID', '<>', 'FAIL')
            ->whereNotNull('CBC.LoanAppID')
        ->where('CBC.LoanAppID', '<>', '')
        ->groupBy('CBC.LoanAppID')
        ->selectRaw('"CBC"."LoanAppID", COUNT(DISTINCT "CBC"."ID") as "NumOfCBCReport", COUNT("CBCD"."Customer") as "NumOfApplicant"');

        // Latest assessment per loan application (prevents duplicate loan rows)
        $lasSub = $db->table('MKT_LOAN_ASSESSMENT')
            ->selectRaw('DISTINCT ON ("LoanApplicationID") "LoanApplicationID", "DSCRatio"')
            ->whereNotNull('LoanApplicationID')
        ->where('LoanApplicationID', '<>', '')
        ->orderBy('LoanApplicationID')
        ->orderByDesc('ID'); // use a date column if you have one

        $base = $db->table('MKT_LOAN_CONTRACT as LC')
            ->leftJoinSub($lasSub, 'LAS', 'LAS.LoanApplicationID', '=', 'LC.LoanApplicationID')
            ->leftJoin('MKT_CUSTOMER as CU', 'CU.ID', '=', 'LC.ContractCustomerID')
        ->leftJoinSub($lgSub, 'LGSub', 'LGSub.LoanAppID', '=', 'LC.LoanApplicationID')
        ->leftJoinSub($cbcSub, 'CBCSub', 'CBCSub.LoanAppID', '=', 'LC.LoanApplicationID')
        ->when($request->filled('branch_id'), fn ($q) => $q->where('LC.Branch', $request->branch_id));

        $search = trim((string) $request->input('search.value', ''));
        $filtered = (clone $base)->when($search !== '', function ($q) use ($search) {
            $like = '%' . $search . '%';
            $q->where(function ($w) use ($like) {
                $w->whereRaw('CAST("LC"."ID" AS TEXT) ILIKE ?', [$like])
                  ->orWhereRaw('CAST("LC"."LoanApplicationID" AS TEXT) ILIKE ?', [$like])
                  ->orWhereRaw('CAST("LC"."EnquiryMemberRef" AS TEXT) ILIKE ?', [$like])
                  ->orWhereRaw('CONCAT("CU"."FirstNameEn", \' \', "CU"."LastNameEn") ILIKE ?', [$like]);
            });
        });
        return [$base, $filtered];
    }

    /**
     * Data query. $paginate = false for the Excel export (all rows).
     */
    public static function getDatas(Request $request, bool $paginate = true)
    {
        [, $filtered] = self::filteredQuery($request);
        $columns = [
            0  => 'LC.ID',
            1  => 'LC.Branch',
            2  => 'LC.LoanApplicationID',
            3  => 'LC.Amount',
            4  => 'LC.Disbursed',
            5  => 'LC.ValueDate',
            6  => 'LC.OutstandingAmountAS',
            7  => 'LAS.DSCRatio',
            8  => 'CU.FirstNameEn',
            9  => 'CU.Currency',
            10 => 'CU.MonthlyIncome',
            11 => 'LC.EnquiryMemberRef',
            12 => 'CBCSub.NumOfCBCReport',
            13 => 'CBCSub.NumOfApplicant',
            14 => 'LGSub.ID',
            15 => 'LGSub.NumOfOtherLender',
        ];
        $orderCol = $columns[(int) $request->input('order.0.column', 0)] ?? 'LC.ID';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = $filtered
            ->selectRaw('
                "LC"."ID" as loan_id,
                "LC"."Branch" as branch,
                "LC"."LoanApplicationID" as loan_application_id,
                "LC"."Amount" as amount,
                "LC"."Disbursed" as disbursed,
                "LC"."ValueDate" as value_date,
                "LC"."OutstandingAmountAS" as outstanding_amount,
                COALESCE("LAS"."DSCRatio"::text, \'\') as dsc_ratio,
                CONCAT("CU"."FirstNameEn", \' \', "CU"."LastNameEn") as customer_name,
                "CU"."Currency" as income_currency,
                "CU"."MonthlyIncome" as monthly_income,
                COALESCE("LC"."EnquiryMemberRef"::text, \'\') as cbc_member_ref,
                COALESCE("CBCSub"."NumOfCBCReport"::text, \'\') as number_of_cbc_enquiries,
                COALESCE("CBCSub"."NumOfApplicant"::text, \'\') as number_of_applicants,
                COALESCE("LGSub"."ID"::text, \'\') as lgid,
                COALESCE("LGSub"."NumOfOtherLender"::text, \'\') as number_of_other_lenders
            ')
        ->orderBy($orderCol, $orderDir)->orderBy('LC.ID');

        if ($paginate) {
            $start  = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 10);
            if ($length > 0) {
                $query->offset($start)->limit($length);
            }
        }
        return $query;
    }



    public static function filteredQueryCBCNoneEquriry(Request $request): array
    {
        $db = DB::connection('pgsql');

        // Dates from the request (default: current month)
        $dateFrom = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->toDateString();
        $dateToExclusive = $request->filled('to_date') ? $request->to_date : now()->endOfMonth()->toDateString();

        // Filters shared by the three parts (no approval condition here)
        $common = function ($q) use ($dateFrom, $dateToExclusive) {
            $q->where('LA.Branch', '<>', '')
            ->where('LA.AppDate', '>=', $dateFrom)
            ->where('LA.AppDate', '<', $dateToExclusive)
            ->where('LA.Currency', '<>', '');
        };

        // Main borrower: WFStatus = 'APP'
        $borrower = $db->table('MKT_LOAN_APPLICATION as LA')
            ->selectRaw('
                "LA"."Branch", "LA"."Currency", "LA"."AppDate",
                "LA"."LNCustomerID" as "CustomerID",
                \'Borrower\' as "CustomerType",
                "LA"."ID", "LA"."Amount"
            ')
            ->tap($common)
            ->where('LA.WFStatus', '=', 'APP')
            ->whereNotNull('LA.LNCustomerID')
            ->where('LA.LNCustomerID', '<>', '');

        // Co-borrowers: ApprovedDate <> ''
        $coBorrower = $db->table('MKT_LOAN_APPLICATION as LA')
            ->join('MKT_LOAN_CO_BORROWER as CBR', 'CBR.ID', '=', 'LA.ID')
            ->selectRaw('
                "LA"."Branch", "LA"."Currency", "LA"."AppDate",
                "CBR"."CoBorrowerID" as "CustomerID",
                \'Co_Borrower\' as "CustomerType",
                "LA"."ID", "LA"."Amount"
            ')
            ->tap($common)
        ->where('LA.ApprovedDate', '<>', '')
        ->whereNotNull('CBR.CoBorrowerID')
        ->where('CBR.CoBorrowerID', '<>', '');

        // Guarantors: ApprovedDate <> ''
        $guarantor = $db->table('MKT_LOAN_APPLICATION as LA')
            ->join('MKT_GUARANTOR as GRT', 'GRT.ID', '=', 'LA.ID')
            ->selectRaw('
                "LA"."Branch", "LA"."Currency", "LA"."AppDate",
                "GRT"."Guarantor" as "CustomerID",
                \'Guarantor\' as "CustomerType",
                "LA"."ID", "LA"."Amount"
            ')
            ->tap($common)
            ->where('LA.ApprovedDate', '<>', '')
            ->whereNotNull('GRT.Guarantor')
            ->where('GRT.Guarantor', '<>', '');

        // CustomerRoles CTE
        $roles = $borrower->unionAll($coBorrower)->unionAll($guarantor);

        // Main query: people whose application has no CBC record
        $base = $db->query()
            ->fromSub($roles, 'CR')
            ->leftJoin('MKT_CUSTOMER as CUS', 'CUS.ID', '=', 'CR.CustomerID')
            ->leftJoin('MKT_CBC as CBC', 'CBC.LoanAppID', '=', 'CR.ID')
        ->whereNull('CBC.ID')
        ->when($request->filled('branch_id'), fn ($q) => $q->where('CR.Branch', $request->branch_id));

        $search = trim((string) $request->input('search.value', ''));
        $filtered = (clone $base)->when($search !== '', function ($q) use ($search) {
            $like = '%' . $search . '%';
            $q->where(function ($w) use ($like) {
                $w->whereRaw('CAST("CR"."ID" AS TEXT) ILIKE ?', [$like])
                ->orWhereRaw('CAST("CR"."CustomerID" AS TEXT) ILIKE ?', [$like])
                ->orWhereRaw('TRIM(COALESCE("CUS"."FirstNameEn", \'\') || \' \' || COALESCE("CUS"."LastNameEn", \'\')) ILIKE ?', [$like]);
            });
        });

        return [$base, $filtered];
    }

    public static function getDatasCBCNoneEquriry(Request $request, $filtered, bool $paginate = true)
    {
        $columns = [
            0 => 'CR.Branch',
            1 => 'CR.ID',
            2 => 'CR.CustomerID',
            3 => 'CR.CustomerType',
            4 => 'CR.Currency',
            5 => 'CR.AppDate',
            6 => 'CR.Amount',
        ];
        $orderCol = $columns[(int) $request->input('order.0.column', 5)] ?? 'CR.AppDate';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = $filtered
            ->selectRaw('
                "CR"."Branch"       as branch,
                "CR"."Currency"     as currency,
                "CR"."AppDate"      as app_date,
                "CR"."CustomerID"   as customer_id,
                TRIM(COALESCE("CUS"."FirstNameEn", \'\') || \' \' || COALESCE("CUS"."LastNameEn", \'\')) as customer_name,
                "CR"."CustomerType" as customer_type,
                "CR"."ID"           as loan_application_id,
                "CR"."Amount"       as amount,
                "CBC"."ID"          as "CBC_Ref"
            ')
        ->orderBy($orderCol, $orderDir)
        ->orderBy('CR.ID')
        ->orderByRaw('CASE "CR"."CustomerType" WHEN \'Borrower\' THEN 1 WHEN \'Co_Borrower\' THEN 2 ELSE 3 END')
        ->orderBy('CR.CustomerID');

        if ($paginate) {
            $start  = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 10);
            if ($length > 0) {
                $query->offset($start)->limit($length);
            }
        }

        return $query;
    }

    public function CBCNoneEquriry(Request $request)
    {
        if ($request->ajax()) {
            [$base, $filtered] = self::filteredQueryCBCNoneEquriry($request);

            $recordsTotal    = (clone $base)->count();
            $recordsFiltered = (clone $filtered)->count();
            $data            = self::getDatasCBCNoneEquriry($request, clone $filtered)->get();
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        return view('credit_underwriting.cbc_none_equriry', compact('branch'));
    }

    public function CBCNoneEquriryDownload(Request $request)
    {
        return Excel::download(
            new ExportCBCNoneEquriry($request->input('branch_id')),
            'cbc_none_equriry_report.xlsx'
        );
    }
}