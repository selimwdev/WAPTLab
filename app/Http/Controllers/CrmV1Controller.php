<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrmV1Controller extends Controller
{
    /**
     * 🧠 تحقق من صلاحية المستخدم عبر مقارنة القيم في قاعدة البيانات المقابلة
     */
    protected function checkCrossDbEntityMatch(string $filename, array $data = []): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Forbidden: unauthenticated.');
        }

        $role = strtolower($user->role ?? '');
        if (!in_array($role, ['hr', 'support'])) {
            return; // باقي الأدوار غير مقيدة
        }

        // 🧩 نحدد قاعدة البيانات المقابلة
        $oppositeConnection = $role === 'hr' ? 'mysql_support' : 'mysql_hr';

        try {
            // استخرج كل القيم النصية من الصف
            $values = collect($data)
                ->filter(fn($v) => is_scalar($v) && strlen(trim($v)) > 0)
                ->map(fn($v) => trim(strtolower($v)))
                ->unique()
                ->values()
                ->toArray();

            if (empty($values)) {
                return;
            }

            // هنسحب كل entity_id اللي فيها واحدة من القيم
            $matches = DB::connection($oppositeConnection)
                ->table('values')
                ->select('entity_id', 'value')
                ->whereIn(DB::raw('LOWER(value)'), $values)
                ->get();

            if ($matches->isEmpty()) {
                return;
            }

            // نعد عدد القيم المتطابقة في كل entity_id
            $entityCount = $matches->groupBy('entity_id')->map->count();

            // هل فيه entity_id فيها أكتر من قيمة (يعني علي الأقل 2 من نفس الصف)
            $entityWithMultipleMatches = $entityCount->filter(fn($count) => $count > 1);

            if ($entityWithMultipleMatches->isNotEmpty()) {
                abort(403, 'Forbidden: record overlaps with protected data in the opposite DB.');
            }

        } catch (\Throwable $e) {
            Log::error('Cross-DB EAV check failed', [
                'error' => $e->getMessage(),
                'user_role' => $user->role,
            ]);
            abort(500, 'Internal security check failed.');
        }
    }

    /**
     * 📥 حفظ صف واحد (ملف JSON)
     */
    public function saveRow(Request $request)
    {
        $db = $request->db;
        $rowData = json_decode($request->row_data, true);

        if (!is_array($rowData)) {
            return response()->json(['message' => 'Invalid row_data'], 422);
        }

        // 🛡️ تحقق من البيانات في القاعدة المقابلة
        $this->checkCrossDbEntityMatch($db ?? 'unknown', $rowData);

        $id = isset($rowData['id']) && $rowData['id'] !== ''
            ? (string) $rowData['id']
            : uniqid();

        Storage::disk('local')->put("crm_rows/{$id}.json", json_encode($rowData));

        return response()->json(['id' => $id]);
    }

    /**
     * 📤 تحميل صف فردي كـ CSV
     */
    public function downloadRow($id)
    {
        $path = storage_path("app/crm_rows/{$id}.json");
        if (!file_exists($path)) {
            abort(404, 'Row not found.');
        }

        $data = json_decode(file_get_contents($path), true);

        // 🛡️ تحقق من القيم في قاعدة البيانات المقابلة
        $this->checkCrossDbEntityMatch("row_{$id}.json", $data);

        $csv = implode(',', array_keys($data)) . "\n" .
               implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $data));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=row_{$id}.csv",
        ]);
    }
}
