<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollectionSheetController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Implement your logic to fetch data for the collection sheet
            // For example, you can query the database and return the data in JSON format
            $data = []; // Replace this with actual data fetching logic

            return response()->json([
                'data' => $data,
            ]);
        }

        return view('collection-sheet.index');
    }
}
