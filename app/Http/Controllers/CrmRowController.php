<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrmMain;

class CrmRowController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function saveRow(Request $request)
    {
        $request->validate([
            'db' => 'required|string',
            'row_data' => 'required|string'
        ]);

        $db = $request->db;
        $rowData = json_decode($request->row_data, true);

        if (!$rowData) {
            return response()->json(['message' => 'Invalid data'], 422);
        }

        // تحقق إذا موجود في crm_main
        $existing = CrmMain::where('source_db', $db)
            ->whereJsonContains('data', $rowData)
            ->first();

        if ($existing) {
            $crmId = $existing->id;
        } else {
            $crm = CrmMain::create([
                'source_db' => $db,
                'source_row_id' => $rowData['id'] ?? null,
                'data' => $rowData
            ]);
            $crmId = $crm->id;
        }

        return response()->json([
            'message' => 'Row loaded successfully',
            'id' => $crmId
        ]);
    }

    public function viewCrm($id)
    {
        $crm = CrmMain::findOrFail($id);
        return response()->json($crm);
    }

    public function downloadRow($id)
{
    $crm = CrmMain::findOrFail($id); // يجيب البيانات من الـ DB

    // تحويل الـ data لملف JSON
    $filename = "crm_row_{$id}.json";
    $content = json_encode($crm->data, JSON_PRETTY_PRINT);

    return response($content)
        ->header('Content-Type', 'application/json')
        ->header('Content-Disposition', "attachment; filename={$filename}");
}

}
