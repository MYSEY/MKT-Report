<?php

namespace App\Http\Controllers\Admins;

use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('User');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->denyPermission('User View')) {
            return view('page.access_page');
        }
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
            ]);

            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('MKT_USER.ID', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.LogInName', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.DisplayName', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.Role', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.Branch', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.AccessBranch', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.RestrictBranch', 'ILIKE', "%{$searchValue}%")
                      ->orWhere('MKT_USER.Officer', 'ILIKE', "%{$searchValue}%");
                });
            }

            // Total rows (no search)
            $recordsTotal = DB::connection('pgsql')->table('MKT_USER')->count();

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
