<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Traits\HasRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Province;
use App\Exports\NetworkEmployeeExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class NetworkEmployeeController extends Controller
{
    use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('Network Employee');
    }

    public static function condition($request) {
        return function ($query) use ($request) {
            // បំបែកខែ និង ឆ្នាំ
            // $month = date('m', strtotime('01/' . $dateInput));
            // $year = date('Y', strtotime('01/' . $dateInput));
            // if ($request->has('network_date') && !empty($request->network_date)) {
            //     $targetDate = $request->network_date;
            // } else {
            //     $targetDate = date('Y-m-d'); // ថ្ងៃនេះ
            // }

            // // បង្កើតជាថ្ងៃចុងក្រោយនៃខែនោះ (ឧទាហរណ៍៖ 2026-04-30)
            // $lastDayOfMonth = date('Y-m-t', strtotime($targetDate));


            $query->where(function($q) use ($request) {
                $dateInput = $request->get('network_date') ?? date('Y-m-d');
                $time = strtotime($dateInput);
                $month = date('m', $time);
                $year  = date('Y', $time);
                $lastDayOfMonth = date('Y-m-t', $time);

                // ប្រើ Sub-Query ដើម្បីខ្ចប់លក្ខខណ្ឌបុគ្គលិកទាំងអស់ ការពារកុំឱ្យជះឥទ្ធិពលដល់លក្ខខណ្ឌផ្សេង
                $q->where(function($mainGroup) use ($month, $year, $lastDayOfMonth) {
                    
                    // --- លក្ខខណ្ឌទី ១: បុគ្គលិកកំពុងធ្វើការ (Active) ---
                    // និយមន័យ៖ ចូលធ្វើការមុនដាច់ខែនេះ ហើយ (មិនទាន់ឈប់ ឬ ឈប់ក្រោយដាច់ខែនេះ)
                    $mainGroup->where(function($active) use ($lastDayOfMonth) {
                        $active->whereIn("users.emp_status", ['1', '2', '10', 'Probation'])
                            ->whereDate('users.date_of_commencement', '<=', $lastDayOfMonth);
                    });

                    // --- លក្ខខណ្ឌទី ២: បុគ្គលិកឈប់ចន្លោះថ្ងៃ ០៦ ដល់ ០៥ នៃខែបន្ទាប់ ---
                    $mainGroup->orWhere(function($resignCycle) use ($month, $year) {
                        $from_date = date('Y-m-d', strtotime($year . '-' . $month . '-06'));
                        $to_date = date('Y-m-d', strtotime($from_date . ' +1 month -1 day')); // ថ្ងៃទី ០៥ ខែបន្ទាប់
                        
                        $resignCycle->whereIn("users.emp_status", ['3', '4', '5', '6', '7', '8', '9'])
                                    ->where('users.date_of_commencement', '<=', $to_date)
                                    ->whereBetween('users.resign_date', [$from_date, $to_date]);
                    });

                    // --- លក្ខខណ្ឌទី ៣: បុគ្គលិកទើបចូល ហើយឈប់វិញភ្លាមៗក្នុងខែតែមួយ (ថ្ងៃ ០១ ដល់ ០៥) ---
                    $mainGroup->orWhere(function($shortTerm) use ($month, $year) {
                        $from_date = date('Y-m-d', strtotime($year . '-' . $month . '-01'));
                        $to_date = date('Y-m-d', strtotime($year . '-' . $month . '-05'));
                        
                        $shortTerm->whereIn("users.emp_status", ['3', '4', '5', '6', '7', '8', '9'])
                                ->whereBetween('users.date_of_commencement', [$from_date, $to_date])
                                ->whereBetween('users.resign_date', [$from_date, $to_date]);
                    });

                    // --- លក្ខខណ្ឌទី ៤: បុគ្គលិកដែលឈប់មុនថ្ងៃទី ០១ (ករណីពិសេសប្រសិនបើអ្នកចង់បង្ហាញ) ---
                    // ចំណាំ៖ លក្ខខណ្ឌនេះគួរតែកំណត់រយៈពេលឱ្យច្បាស់ បើមិនដូច្នោះទេវានឹងចេញទិន្នន័យចាស់ៗខ្លាំងពេក
                    $mainGroup->orWhere(function($oldResign) use ($month, $year) {
                        $firstDayOfMonth = date('Y-m-d', strtotime($year . '-' . $month . '-01'));
                        $oldResign->whereIn("users.emp_status", ['3', '4', '5', '6', '7', '8', '9'])
                                ->whereDate('users.resign_date', '>=', $firstDayOfMonth) // យ៉ាងហោចណាស់ឈប់ក្នុងខែហ្នឹង
                                ->whereDate('users.date_of_commencement', '<', $firstDayOfMonth);
                    });

                })
                // បង្ហាញជួរទទេសម្រាប់ខេត្តដែលគ្មានបុគ្គលិក
                ->orWhereNull('users.id');
            });
        };
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
        $query->where(self::condition($request));

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
