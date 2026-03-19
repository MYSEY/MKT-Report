<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            $query = DB::connection('pgsql')
            ->table('MKT_USER')
            ->select([
                'MKT_USER.ID',
                'MKT_USER.LogInName',
                'MKT_USER.DisplayName',
                'MKT_USER.Role',
                'MKT_USER.Branch',
                'MKT_USER.AccessBranch',
                'MKT_USER.RestrictBranch',
                'MKT_USER.Officer',
                'MKT_USER.Active',
            ])->where('Active', 'Yes');

            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('MKT_USER.ID', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.LogInName', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.DisplayName', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.Role', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.Branch', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.AccessBranch', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.RestrictBranch', 'like', "%{$searchValue}%")
                    ->orWhere('MKT_USER.Officer', 'like', "%{$searchValue}%");
                });
            }

            // Total rows (no search)
            $recordsTotal = DB::connection('pgsql')
            ->table('MKT_USER')->where('Active', 'Yes')->count();

            // Total rows (with search)
            $recordsFiltered = $query->count();
            // Pagination
            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 10));
            $data = $query->orderBy('ID', 'desc')->offset($start)->limit($limit)->get();
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
        }
        return view('users.index');
    }

    public function menu(Request $request){
        return view('menu.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
