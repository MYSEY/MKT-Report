<?php

namespace App\Http\Controllers\Admins;

use App\Models\Category;
use App\Models\Permission;
use Brian2694\Toastr\Toastr;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('Role');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->denyPermission('Role View')) {
            return view('page.access_page');
        }
        if ($request->ajax()) {
            $query = DB::connection('pgsql')
            ->table('MKT_ROLE as R')
            ->select([
                'R.ID',
                'R.Description'
            ]);

            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('R.ID', 'ILIKE', "%{$searchValue}%")
                    ->orWhere('R.Description', 'ILIKE', "%{$searchValue}%");
                });
            }
            
            $recordsTotal = DB::connection('pgsql')->table('MKT_ROLE as R')->count();
            $recordsFiltered = $query->count();
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 10));
            $data = $query->offset($start)->limit($limit)->get();
            
            // Return JSON response
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
        }
        return view('roles.index');
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
        $role = DB::connection('pgsql')
            ->table('MKT_ROLE as R')
            ->select([
                'R.ID',
                'R.Description',
                'U.DisplayName',
                'U.Role',
            ])
        ->leftJoin('MKT_USER as U', 'U.Role', '=', 'R.ID')
        ->where('R.ID',$id)->first();
        $rolePermission = Permission::leftJoin(
            "role_has_permissions",
            "role_has_permissions.permission_id",
            "=",
            "permissions.id"
        )
        ->where("role_has_permissions.role_id", $id)
        ->pluck('permissions.id')
        ->toArray();
        $category = Category::all();
        return view('roles.edit', compact('role','category','rolePermission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();
            DB::table('role_has_permissions')->where('role_id', $request->id)->delete();
            // Insert new permissions only if there are any
            if ($request->has('permission') && is_array($request->permission)) {
                foreach ($request->permission as $permId) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $request->id,
                        'permission_id' => $permId,
                    ]);
                }
            }
            DB::commit();
            toastr()->success('Role permissions updated successfully', 'Success');
            return redirect()->route('role.index');
        } catch (\Exception $e) {
            DB::rollBack();
            toastr()->error('Save failed', 'Error');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
