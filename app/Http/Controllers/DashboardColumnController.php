<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardColumnController extends Controller
{
    public function loadColumn(Request $request)
    {
        $data = $request->validate([
            'column' => 'required|string',
            'values' => 'required|array',
        ]);

        $column = $data['column'];
        $values = $data['values'];

        foreach($values as $val) {
            DB::table('crm_main')->insert([
                'column_name' => $column,
                'value' => $val,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Column data loaded successfully']);
    }
}
