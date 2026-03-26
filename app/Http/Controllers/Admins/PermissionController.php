<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Models\Category;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
