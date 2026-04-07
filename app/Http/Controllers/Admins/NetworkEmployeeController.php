<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Traits\HasRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Province;
use App\Exports\NetworkEmployeeExport;
use Maatwebsite\Excel\Facades\Excel;

class NetworkEmployeeController extends Controller
{
    use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('Network Employee');
    }

    public static function getDatas($request)
    {
        $query = DB::connection('mysqlhrconnection')->table('provinces')
            ->leftJoin('branchs', 'provinces.code', '=', 'branchs.current_province')
            ->leftJoin('users', 'branchs.id', '=', 'users.branch_id')
            ->leftJoin('options', 'users.gender', '=', 'options.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->select([
                DB::raw("DENSE_RANK() OVER(ORDER BY provinces.id ASC) as province_no"),
                'provinces.id as province_id', 
                'provinces.name_km as pro_name_km',
                'provinces.name_en as province_name',
                'branchs.branch_name_kh',
                'branchs.branch_name_en',
                // បង្កើត merge_group ដើម្បីប្រើក្នុង JavaScript សម្រាប់ Merge ក្រឡា
                DB::raw("CASE 
                    WHEN branchs.branch_name_en LIKE '%Head Quarter%' THEN 'special_group'
                    WHEN branchs.branch_name_en LIKE '%Digital_Bro%' THEN 'special_group'
                    ELSE CAST(branchs.id AS CHAR) 
                END as merge_group"),

                DB::raw("CASE WHEN branchs.branch_name_en LIKE '%Head Quarter%' THEN 1 ELSE '' END as is_hq"),
                // កំណត់ branch_count ជា 0 សម្រាប់ HQ និង Digital
                DB::raw("CASE 
                    WHEN branchs.branch_name_en LIKE '%Head Quarter%' THEN ''
                    WHEN branchs.branch_name_en LIKE '%Digital_Bro%' THEN ''
                    WHEN branchs.id IS NULL THEN 0
                    ELSE 1 
                END as branch_count"),
                DB::raw("IFNULL(SUM(CASE WHEN options.name_english = 'Male' THEN 1 ELSE 0 END), 0) as male"),
                DB::raw("IFNULL(SUM(CASE WHEN options.name_english = 'Female' THEN 1 ELSE 0 END), 0) as female"),
                DB::raw("COUNT(users.id) as total"),
                // --- ផ្នែកថ្មី៖ រាប់តែ CO Positions ---
                DB::raw("IFNULL(SUM(CASE WHEN positions.name_english IN (
                    'Junior Credit Officer',
                    'Credit Officer',
                    'Senior Credit Officer',
                    'Junior Relationship Officer, Digital Lending', 
                    'Relationship Officer, Digital Lending', 
                    'Relationship Officer, Digital Lending'
                ) THEN 1 ELSE 0 END), 0) as co_count")
            ])
            ->groupBy(
                'provinces.id', 
                'provinces.name_en', 
                'provinces.name_km', 
                'branchs.id', 
                'branchs.branch_name_kh', 
                'branchs.branch_name_en'
            )
            // ១. ដាក់ខេត្តដែលមានសាខានៅលើគេ ២. តម្រៀបតាម ID ខេត្ត
            ->orderByRaw('branchs.id IS NULL ASC')
            ->orderBy('provinces.id', 'ASC')
            // ២. បន្ទាប់មកតម្រៀបតាម ID ខេត្ត
            ->orderBy('provinces.id', 'asc');
        $query->when(request('branch_id'), function ($q, $branch_id) {
            return $q->where('branchs.id', $branch_id);
        });
        // ... ផ្នែក Search និង Pagination ទុកដដែល ...
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('provinces.name_km', 'like', "%{$search}%")
                ->orWhere('provinces.name_en', 'like', "%{$search}%")
                ->orWhere('branchs.branch_name_kh', 'like', "%{$search}%")
                ->orWhere('branchs.branch_name_en', 'like', "%{$search}%");
            });
        }
        
        $query->where(function($q) {
            $q->whereIn("users.emp_status", ['1', '2', '10', 'Probation'])
            ->orWhereNull('users.id'); // បន្ថែមជួរនេះ ដើម្បីឱ្យបង្ហាញខេត្តដែលគ្មានបុគ្គលិក
        });
        return $query;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$this->denyPermission('Network Employee View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $query = self::getDatas($request);
            $data = $query->get(); // ទាញយកទិន្នន័យ
            
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => DB::connection('mysqlhrconnection')->table('provinces')->count(),
                'recordsFiltered' => $query->get()->count(), 
                'data' => $data,
            ]);
        }
        $branch = DB::connection('mysqlhrconnection')->table('branchs')->get();
        $provinces = Province::with("branches")->get();

        return view('hr-reports.network_employee',compact('branch','provinces'));
    }
    public function exportExcel(Request $request) {
        // ទាញយកទិន្នន័យតាមរយៈ Query ដែលយើងបានធ្វើកាលពីមុន
        $query = self::getDatas($request);
        $data = $query->get();

        return Excel::download(new NetworkEmployeeExport($data), 'Network_Employee_Report.xlsx');
    }
}
