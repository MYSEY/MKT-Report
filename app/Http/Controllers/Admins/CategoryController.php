<?php

namespace App\Http\Controllers\Admins;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('Category');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->denyPermission('Category View')) {
            return view('page.access_page');
        }
        if ($request->ajax()) {
            // Define the base query
            $query = Category::query();
            
            // Fetch paginated data
            $recordsTotal = Category::count();
            $recordsFiltered = $query->count();
            // Apply pagination for the actual data retrieval
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 10));
            $data = $query->offset($start)->limit($limit)->get();
            
            // Return JSON response
            return response()->json([
                'draw' => intval($request->input('draw')),  // Optional: for client-side tracking
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
        }
        return view('categorys.index');
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
    public function store(StoreCategoryRequest $request)
    {
        try {
            $data = $request->all();
            // $data['created_by'] = Auth::user()->id;
            $data['created_by'] = session('MKT_USER.displayName');
            Category::create($data);
            DB::commit();
            Toastr::success('Created Category successfully.','Success');
            return redirect()->back();
        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Created Category fail','Error');
            return redirect()->back();
        }
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
        $data = Category::where('id',$id)->first();
        return response()->json([
            'success'=>$data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            Category::findOrFail($request->id)->update([
                'name'       => $request->name,
                'updated_by' => session('MKT_USER.displayName'),
                // 'updated_by' => Auth::id(),
            ]);

            DB::commit();
            Toastr::success('Updated Category successfully.', 'Success');
            return redirect()->back();

        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Updated Category fail', 'Error');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try{
            Category::destroy($request->id);
            Toastr::success('Category deleted successfully.','Success');
            return redirect()->back();
        }catch(\Exception $e){
            DB::rollback();
            Toastr::error('Category delete fail.','Error');
            return redirect()->back();
        }
    }
}
