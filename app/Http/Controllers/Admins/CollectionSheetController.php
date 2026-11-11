<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionSheetController extends Controller
{
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $query = self::getDatas($request);
            $recordsTotal = DB::connection('pgsql')->table('MKT_REP_SCHEDULE')->count();
            $recordsFiltered = $query->count();
            // Pagination
            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 10));
            $data = $query->offset($start)->limit($limit)->get();
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        return view('collection-sheet.index');
    }

    public static function getDatas($request)
    {

        $query = DB::connection('pgsql')
        ->table('MKT_REP_SCHEDULE as RS')
        ->select([
            'RS.ID',
            'RS.Branch',
            'RS.LoanID',
            'RS.CollectionDate',
            'RS.Principal',
            'RS.Interest',
            'RS.Charge',
            'RS.Balance',
        ]);

        if ($searchValue = request()->input('search.value')) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('RS.ID', 'ilike', "%{$searchValue}%")
                ->orWhere('RS.Branch', 'ilike', "%{$searchValue}%")
                ->orWhere('RS.LoanID', 'ilike', "%{$searchValue}%");
            });
        }
        return $query->orderBy('RS.ID');
    }
}
