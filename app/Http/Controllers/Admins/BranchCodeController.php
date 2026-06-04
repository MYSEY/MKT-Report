<?php

namespace App\Http\Controllers\Admins;
use App\Models\BranchCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreBranchCodeRequest;

class BranchCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if (!$this->denyPermission('Category View')) {
        //     return view('page.access_page');
        // }
        if ($request->ajax()) {
            // Define the base query
            $query = BranchCode::query();
            
            // Fetch paginated data
            $recordsTotal = BranchCode::count();
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
       return view('branch_code.index');
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
    public function store(StoreBranchCodeRequest $request)
    {
        try {
            $data = $request->all();
            $data['created_by'] = session('MKT_USER.displayName');
            BranchCode::create($data);
            DB::commit();
            Toastr::success('Created Branch code successfully.','Success');
            return redirect()->back();
        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Created Branch code fail','Error');
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
        $data = BranchCode::where('id',$id)->first();
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
            BranchCode::findOrFail($request->id)->update([
                'code'       => $request->code,
                'abbreviations'       => $request->abbreviations,
                'name'       => $request->name,
                'updated_by' => session('MKT_USER.displayName'),
                // 'updated_by' => Auth::id(),
            ]);

            DB::commit();
            Toastr::success('Updated Branch Code successfully.', 'Success');
            return redirect()->back();
        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Updated Branch Code fail', 'Error');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try{
            BranchCode::destroy($request->id);
            Toastr::success('Branch Code deleted successfully.','Success');
            return redirect()->back();
        }catch(\Exception $e){
            DB::rollback();
            Toastr::error('Branch Code delete fail.','Error');
            return redirect()->back();
        }
    }
}
