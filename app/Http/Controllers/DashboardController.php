<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attribute;
use App\Services\ElasticService;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }

    public function index(Request $req) {
        $user = $req->user();
        $db = $req->query('db', $user->role);
        if ($db !== $user->role) abort(403);

        $attrs = Attribute::all()->pluck('name')->toArray();
        return view('dashboard.index', compact('db','attrs'));
    }

public function data(Request $req, \App\Services\ElasticService $es)
{
    $user = $req->user();
    $role = $user->role;
    $db = $req->query('db', $role);

    // 🔒 حماية: لازم الـ db = role
    if ($db !== $role) {
        abort(403, 'Unauthorized access');
    }

    // 📦 حدد الـ index حسب نوع الـ db
    $index = $db === 'hr' ? 'hr_data' : 'support_data';

    // 📄 استعلام بسيط من Elasticsearch
    $body = [
        "query" => [
            "match_all" => (object)[]
        ],
        "size" => 100
    ];

    // ⛓️ ابحث
    $res = $es->search($index, $body);
    $hits = data_get($res, 'hits.hits', []);
    $rows = [];

    foreach ($hits as $hit) {
        $src = $hit['_source'] ?? [];
        $src['id'] = $hit['_id'] ?? null;
        $rows[] = $src;
    }

    return response()->json($rows);
}
    public function search(Request $req, ElasticService $es) {
        $user = $req->user();
        $role = $user->role;
        $q = $req->query('q','');
        if (!$q) return response()->json([]);

        $index = $role === 'hr' ? 'hr_data' : 'support_data';
        $body = ['query'=>['multi_match'=>['query'=>$q,'fields'=>['name','email','*']]]];
        $res = $es->search($index, $body);
        $hits = data_get($res,'hits.hits',[]);
        $results = [];
        foreach($hits as $h) $results[] = $h['_source'] ?? $h;
        return response()->json($results);
    }

        public function dashboard(Request $request)
    {
        $db = $request->query('db', 'hr');
        $user = Auth::user();

        if ($db !== $user->role) {
            abort(403, 'Forbidden');
        }

        $apiUrl = url("/api/dashboard/data?db={$db}");
        $res = file_get_contents($apiUrl);
        $rows = json_decode($res, true) ?? [];

        return view('dashboard.index', [
            'db' => $db,
            'rows' => $rows
        ]);
    }

   public function loadRow(Request $request)
{
    $request->validate([
        'row_id' => 'required',
        'column' => 'required|string',
        'value' => 'required',
    ]);

    $rowId = $request->row_id;
    $column = $request->column;
    $value = $request->value;

    $db = 'hr'; // أو خذها من session أو request
    $rowData = [$column => $value, 'id' => $rowId];

    $existing = CrmMain::where('source_db', $db)
        ->whereJsonContains('data', $rowData)
        ->first();

    if ($existing) {
        $crmId = $existing->id;
    } else {
        $crm = CrmMain::create([
            'source_db' => $db,
            'source_row_id' => $rowId,
            'data' => $rowData
        ]);
        $crmId = $crm->id;
    }

    return response()->json([
        'id' => $crmId,
        'message' => 'Value loaded successfully'
    ]);
}

    public function viewCrm($id)
    {
        $crm = CrmMain::findOrFail($id);
        return view('dashboard.view-crm', compact('crm'));
    }

}
