<?php

namespace App\Http\Controllers\Admins;

use App\Models\Category;
use App\Models\Permission;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Permission::all();
        return view('permission.index',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = Category::all();
        return view('permission.create',compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request)
    {
        try{
            $data_per = Category::find($request->category_id);
            $permissionName = $data_per->name;
            foreach ($request->permission as $value){
                $check_duplicate = Permission::where('name', $permissionName.' '.$value)->first();
                if(!empty($check_duplicate)){
                    DB::rollback();
                    toastr()->warning('Duplicate entry permission'.' '.$value,'Error');
                    return redirect()->back();
                }
            }
            foreach ($request->permission as $value){
                Permission::create([
                    'category_id'    => $request->category_id,
                    'name'    => $permissionName.' '.$value
                ]);
            }
            DB::commit();
            toastr()->success('Create Permission successfully.','success');
            return redirect()->route('permission.index');
        }catch(\Exception $e){
            DB::rollback();
            toastr()->error('Create Permission fail', $e->getMessage());
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
        $data = Permission::find($id);
        $category = Category::all();
        return view('permission.edit',compact('data','category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();
            $data_per = Category::findOrFail($request->category_id);
            $permissionName = $data_per->name;
            $permission = Permission::findOrFail($id);
            $permission->name = $permissionName . ' ' . $request->permission;
            $permission->category_id = $request->category_id;
            $permission->save();
            DB::commit();
            Toastr::success('Permission updated successfully.', 'Success');
            return redirect()->route('permission.index');
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollback();
            Toastr::error('Permission update failed.', 'Error');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            Permission::destroy($id);
            Toastr::success('Permission deleted successfully.', 'Success');
            return redirect()->back();
        } catch (\Exception $e) {
            Toastr::error('Permission delete fail.', 'Error');
            return redirect()->back();
        }
    }
}
