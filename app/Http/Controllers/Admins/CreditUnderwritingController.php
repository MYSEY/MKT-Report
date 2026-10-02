<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportCreditUnderwriting;

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
            3  => 'LAS.DSCRatio',
            4  => 'CU.FirstNameEn',
            5  => 'CU.Currency',
            6  => 'CU.MonthlyIncome',
            7  => 'LC.EnquiryMemberRef',
            8  => 'CBCSub.NumOfCBCReport',
            9  => 'CBCSub.NumOfApplicant',
            10 => 'LGSub.ID',
            11 => 'LGSub.NumOfOtherLender',
        ];
        $orderCol = $columns[(int) $request->input('order.0.column', 0)] ?? 'LC.ID';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = $filtered
            ->selectRaw('
                "LC"."ID" as loan_id,
                "LC"."Branch" as branch,
                "LC"."LoanApplicationID" as loan_application_id,
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
}