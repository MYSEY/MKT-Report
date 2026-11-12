<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\MktTable;
use App\Traits\HasRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MKTTableSetupController extends Controller
{
    use HasRolePermission;

    public function index(Request $request)
    {
        if (!$this->denyPermission('MKT Table Setup View')) {
            return view('page.access_page');
        }

        return view('configurations.mkt-table-setup.index');
    }

    public function data()
    {

        return response()->json([
            'data' => MktTable::orderBy('sort_order')->orderBy('display_name')->get(),
        ]);
    }

    public function store(Request $request)
    {

        $row = MktTable::create($this->validated($request));

        return response()->json(['status' => 'ok', 'message' => 'Created successfully', 'data' => $row]);
    }

    public function update(Request $request, $id)
    {

        $row = MktTable::findOrFail($id);
        $row->update($this->validated($request, $row->id));

        return response()->json(['status' => 'ok', 'message' => 'Updated successfully', 'data' => $row]);
    }

    public function destroy($id)
    {

        MktTable::findOrFail($id)->delete();

        return response()->json(['status' => 'ok', 'message' => 'Deleted successfully']);
    }

    public function toggle($id)
    {

        $row = MktTable::findOrFail($id);
        $row->update(['is_active' => !$row->is_active]);

        return response()->json(['status' => 'ok', 'is_active' => $row->is_active]);
    }

    protected function validated(Request $request, $ignoreId = null): array
    {
        // បម្លែងជាអក្សរធំ និងកាត់ដកឃ្លា មុនពិនិត្យ unique
        $request->merge([
            'table_name' => strtoupper(trim((string) $request->input('table_name'))),
        ]);

        $data = $request->validate([
            'table_name'   => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('mkt-table', 'table_name')->ignore($ignoreId),
            ],
            'display_name' => ['required', 'string', 'max:150'],
            'date_column'  => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active');

        return $data;
    }
}
