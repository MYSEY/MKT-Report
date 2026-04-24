<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use App\Models\InterestIncome;
use Illuminate\Support\Facades\DB;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InterestIncomeController extends Controller
{
    use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('Interest Income');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->denyPermission('Interest Income View')) {
            return view('page.access_page');
        }

        if ($request->ajax()) {
            $query = InterestIncome::query();
            $recordsTotal = InterestIncome::count();
            $search = $request->input('search.value');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // ១. ស្វែងរកតាមអក្សរធម្មតាលើ account_number និង account_name
                    $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%");

                    // ២. ឆែកមើលថាតើអ្នកប្រើប្រាស់វាយពាក្យដែលត្រូវនឹង "type" ដែរឬទេ
                    if (stripos("Loan Product", $search) !== false) {
                        $q->orWhere('type', '1');
                    } 
                    
                    if (stripos("Other Bank", $search) !== false) {
                        $q->orWhere('type', '2');
                    }

                    // ៣. បន្ថែមការស្វែងរកលេខ 1 ឬ 2 ផ្ទាល់លើ column type (ករណី user វាយលេខ)
                    $q->orWhere('type', 'like', "%{$search}%");
                });
            }

            $recordsFiltered = $query->count();

            $start = $request->integer('start', 0);
            $limit = $request->integer('length', 10);
            
            $data = $query->offset($start)
                        ->limit($limit)
                        ->get();

            return response()->json([
                'draw'            => $request->integer('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data
            ]);
        }
        return view('configurations.interest-income.index');
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
        try {
            $data = $request->all();
            $data['created_by'] = session('MKT_USER.displayName');
            InterestIncome::create($data);
            DB::commit();
            Toastr::success('Created successfully.','Success');
            return redirect()->back();
        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Created fail','Error');
            return redirect()->back();
        }
    }

    public function import(Request $request){
        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            if (!in_array($extension, ["xlsx", "xls", "csv"])) {
                return back()->withErrors(["file" => "Invalid file format"]);
            }

            $spreadsheet = IOFactory::load($file);
            $sheetNames = $spreadsheet->getSheetNames();

            foreach ($sheetNames as $id) {
                $sheet = $spreadsheet->getSheetByName($id);
                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $accountNumberValue  = trim($sheet->getCell('B' . $row)->getValue());
                    $accountNameValue    = trim($sheet->getCell('C' . $row)->getValue());
                    $typeValue           = trim($sheet->getCell('D' . $row)->getValue());
                    $type = "";
                    if ($typeValue == "Loan product") {
                        $type ="1";
                    }
                    if ($typeValue == "Other bank") {
                        $type ="2";
                    }
                    $interestIncome = InterestIncome::create([
                        'account_number'    => $accountNumberValue,
                        'account_name'      => $accountNameValue,
                        'type'              => $type,
                        'created_by'        => Auth::id(),
                    ]);
                }
            }

            DB::commit();
            return 1;
        } catch (\Exception $e) {
            DB::rollback();
            Toastr::error("Import failed: " . $e->getMessage(), "Error");
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
        $data = InterestIncome::where('id',$id)->first();
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

            InterestIncome::findOrFail($request->id)->update([
                'type'       => $request->type,
                'account_number'       => $request->account_number,
                'account_name'       => $request->account_name,
                'updated_by' => session('MKT_USER.displayName'),
            ]);

            DB::commit();
            Toastr::success('Updated successfully.', 'Success');
            return redirect()->back();

        } catch (\Throwable $exp) {
            DB::rollback();
            Toastr::error('Updated fail', 'Error');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try{
            InterestIncome::destroy($request->id);
            Toastr::success('Deleted successfully.','Success');
            return redirect()->back();
        }catch(\Exception $e){
            DB::rollback();
            Toastr::error('Delete fail.','Error');
            return redirect()->back();
        }
    }
}
