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

        $persons = $db->table('MKT_LOAN_CONTRACT as L')
            ->selectRaw('"L"."ID" as "LCID", \'Borrower\' as "Role", "L"."ContractCustomerID" as "PersonID"')
            ->unionAll(
                $db->table('MKT_LOAN_CO_BORROWER as CB')
                ->selectRaw('"CB"."ID" as "LCID", \'Co-Borrower\' as "Role", "CB"."CoBorrowerID" as "PersonID"')
            )
            ->unionAll(
                $db->table('MKT_GUARANTOR as G')
                ->selectRaw('"G"."ID" as "LCID", \'Guarantor\' as "Role", "G"."Guarantor" as "PersonID"')
            );

        $base = $db->table('MKT_LOAN_CONTRACT as LC')
            ->joinSub($persons, 'P', 'P.LCID', '=', 'LC.ID')
            ->join('MKT_CBC_DE as CBCD', function ($join) {
                $join->on('CBCD.Customer', '=', 'P.PersonID')
                    ->where('CBCD.MemberReference', '=', '');
            })
            ->leftJoin('MKT_CUSTOMER as CU', 'CU.ID', '=', 'P.PersonID')
            ->where('LC.ValueDate', '>=', '2024-02-01')
        ->when($request->filled('branch_id'), fn ($q) => $q->where('LC.Branch', $request->branch_id));

        $search = trim((string) $request->input('search.value', ''));
        $filtered = (clone $base)->when($search !== '', function ($q) use ($search) {
            $like = '%' . $search . '%';
            $q->where(function ($w) use ($like) {
                $w->whereRaw('CAST("LC"."ID" AS TEXT) ILIKE ?', [$like])
                ->orWhereRaw('CAST("LC"."LoanApplicationID" AS TEXT) ILIKE ?', [$like])
                ->orWhereRaw('CAST("P"."PersonID" AS TEXT) ILIKE ?', [$like])
                ->orWhereRaw('CONCAT("CU"."FirstNameEn", \' \', "CU"."LastNameEn") ILIKE ?', [$like]);
            });
        });

        return [$base, $filtered];
    }

    public static function getDatasCBCNoneEquriry(Request $request, $filtered, bool $paginate = true)
    {
        $columns = [
            0 => 'LC.Branch',
            1 => 'LC.ID',
            2 => 'CU.FirstNameEn',
            3 => 'CBCD.ApplicationType',
            4 => 'CU.Currency',
            5 => 'LC.ValueDate',
            6 => 'LC.Disbursed',
            7 => 'LC.OutstandingAmountAS',
            8 => 'CBCD.MemberReference',
        ];
        $orderCol = $columns[(int) $request->input('order.0.column', 1)] ?? 'LC.ID';
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';

        $query = $filtered
            ->selectRaw('
                "CU"."Branch" as branch,
                "LC"."ID" as loan_id,
                "LC"."LoanApplicationID" as loan_application_id,
                "P"."Role" as role,
                "P"."PersonID" as customer_id,
                CONCAT("CU"."FirstNameEn", \' \', "CU"."LastNameEn") as customer_name,
                "CBCD"."ApplicationType" as applicant_type,
                COALESCE("CU"."Currency", \'\') as currency,
                "LC"."ValueDate" as value_date,
                "LC"."Disbursed" as disbursed,
                "LC"."OutstandingAmountAS" as outstanding_amount,
                "CBCD"."MemberReference" as member_reference,
                "CBCD"."ID" as cbc_id,
                "CBCD"."DetailID" as detail_id
            ')
        ->orderBy($orderCol, $orderDir)
        ->orderBy('LC.ID')
        ->orderBy('P.Role')
        ->orderBy('CBCD.ID');

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
        return view('credit_underwriting.cbc_none_equriry');
    }

    public function CBCNoneEquriryDownload(Request $request)
    {
        return Excel::download(
            new ExportCBCNoneEquriry($request->input('branch_id')),
            'cbc_none_equriry_report.xlsx'
        );
    }
}