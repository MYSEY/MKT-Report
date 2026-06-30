<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRolePermission;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TMGExport;
use App\Models\Position;

class TMGController extends Controller
{
     use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('TMG Report');
    }
    public static function condition() {
        $positionArray = Position::where("type", "2")->pluck('position_id')->toArray();
        return function ($query) use ($positionArray) {
            $positions = !empty($positionArray) ? $positionArray : [0];

            // ៣. កំណត់តំណែងដែលត្រូវបង្ហាញ (ប្រើ $positions ដែលជា Array ធម្មតា)
            $query->whereIn("users.position_id", $positions);

            // ២. កំណត់ Status បុគ្គលិក
            $query->where(function($q) {
                $q->whereIn("users.emp_status", ['1', '2', '10', 'Probation'])
                ->orWhereNull('users.id'); 
            });
        };
    }

    public static function getDatas($request)
    {
        $query = DB::connection('mysqlhrconnection')->table('users')
            ->leftJoin('branchs', 'users.branch_id', '=', 'branchs.id')
            ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
            ->select([
                'users.*',
                'positions.name_khmer as position_name_kh',
                'positions.name_english as position_name_en',
                'branchs.branch_name_kh',
                'branchs.branch_name_en',
            ]);
        // ... ផ្នែក Search និង Pagination ទុកដដែល ...
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('users.employee_name_kh', 'like', "%{$search}%")
                ->orWhere('users.employee_name_en', 'like', "%{$search}%")
                ->orWhere('positions.name_khmer', 'like', "%{$search}%")
                ->orWhere('positions.name_english', 'like', "%{$search}%")
                ->orWhere('branchs.branch_name_kh', 'like', "%{$search}%")
                ->orWhere('branchs.branch_name_en', 'like', "%{$search}%");
            });
        }
       $query->where(self::condition());
        // $query->where("users.is_loan", 1);
        
        return $query;
    }
    public function index(Request $request)
    {
        if (!$this->denyPermission('TMG Report View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $query = self::getDatas($request);
            $data = $query->get(); // ទាញយកទិន្នន័យ
            $recordsFiltered = $query->count();
            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 20));
            $data = $query->orderBy('positions.id', 'ASC')->offset($start)->limit($limit)->get();
            
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => $query->count(),
                'recordsFiltered' => $recordsFiltered, 
                'data' => $data,
            ]);
        }
        return view('hr-reports.TMG_report');
    }
    public function exportExcel(Request $request) {
        // ទាញយកទិន្នន័យតាមរយៈ Query ដែលយើងបានធ្វើកាលពីមុន
        $query = self::getDatas($request);
        $data = $query->orderBy('positions.id', 'ASC')->get();

        return Excel::download(new TMGExport($data), 'TMG_Report.xlsx');
    }
}
