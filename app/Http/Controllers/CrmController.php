<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;


class CrmController extends Controller
{

public function exportData(Request $request)
{
    $db = $request->db;
    $rows = $request->rows ?? [];

    if (empty($rows)) {
        return response()->json(['error' => 'No data provided'], 422);
    }

    // 🧠 1) نجهز المتغيرات اللي ممكن تظهر في الـ Blade
    $context = [
        'user' => auth()->user(),
        'db' => $db,
        'time' => now(),
    ];

    // 🧩 2) نمر على كل القيم في الجدول ونرندر الـ Blade بأمان
    foreach ($rows as &$row) {
        foreach ($row as $key => $value) {
            if (is_string($value) && (str_contains($value, '{{') || str_contains($value, '{!!'))) {
                try {
                    $row[$key] = \Illuminate\Support\Facades\Blade::render($value, $context);
                } catch (\Throwable $e) {
                    // 🧩 لو حصل أي خطأ أثناء الـ render، نحافظ على القيمة الأصلية
                    \Log::warning('Blade render failed for value', [
                        'value' => $value,
                        'error' => $e->getMessage(),
                    ]);
                    $row[$key] = $value;
                }
            }
        }
    }

    // 🧾 3) نرندر الـ HTML من الـ View بعد التعديل
    $html = \Illuminate\Support\Facades\View::make('crm.export-template', [
        'db' => $db,
        'rows' => $rows
    ])->render();

    // 🪶 4) PDF
    if ($request->format === 'pdf') {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        return $pdf->download("CRM_{$db}_" . now()->format('Ymd_His') . ".pdf");
    }

    // 🧱 5) HTML عادي
    return response($html)->header('Content-Type', 'text/html');
}


    // حفظ صف واحد (باسم md5.json)
public function saveRow(Request $request)
{
    $db = $request->db;
    $rowData = json_decode($request->row_data, true);

    if (!is_array($rowData)) {
        return response()->json(['message' => 'Invalid row_data'], 422);
    }

    // استخدم id من ال payload لو موجود، وإلا أنشئ واحد عادي بواسطة uniqid()
    $id = isset($rowData['id']) && $rowData['id'] !== '' 
        ? (string) $rowData['id'] 
        : uniqid();

    Storage::disk('local')->put("crm_rows/{$id}.json", json_encode($rowData));

    return response()->json(['id' => $id]);
}

// تحميل صف فردي (يحفظ باسم row_{id}.csv عند التحميل فقط)
public function downloadRow($id)
{
    $path = storage_path("app/crm_rows/{$id}.json");
    if (!file_exists($path)) abort(404);

    $data = json_decode(file_get_contents($path), true);

    // تحويل JSON لCSV صغير للتحميل
    $csv = implode(',', array_keys($data)) . "\n" .
           implode(',', array_map(fn($v)=>'"'.str_replace('"','""',$v).'"', $data));

    return response($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=row_{$id}.csv",
    ]);
}
    // حفظ كل الصفوف في ملف بدون امتداد - اسم الملف md5 لتسهيل الاختبار
    /*
    public function saveCsv(Request $request)
    {
        $rows = $request->rows ?? [];

        $csvContent = '';
        if (count($rows)) {
            $attrs = array_keys((array)$rows[0]);
            $csvContent .= implode(',', $attrs) . "\n";
            foreach ($rows as $row) {
                $csvContent .= implode(',', array_map(fn($v)=>'"'.str_replace('"','""',$v).'"', (array)$row)) . "\n";
            }
        }

        // اسم الملف md5 بدون امتداد
        $filename = md5(uniqid((string) microtime(true), true));
        $path = "crm_csv/{$filename}";

        Storage::disk('local')->put($path, $csvContent);

        // نعيد الـ filename (بدون امتداد) عشان تستخدمه لاحقًا في view/download
        return response()->json(['path' => $filename]);
    }

    // عرض الملف من مجلد crm_csv - Path Traversal مُتعمد (اختبار)
   public function viewCsv(Request $request)
    {
        $path = $request->path; // مثال: "../../../etc/passwd" => متعمد للاختبار
        $fullPath = storage_path("app/crm_csv/") . $path; // ⚠️ Path Traversal متعمد

        if (!file_exists($fullPath)) abort(404);

        // نعرض الملف كما هو (قد يكون محتوى CSV أصلاً)
        return response()->file($fullPath);
    } 
   */
  // داخل CrmController

// helper: base64Url encode/decode و JWT encode/decode بسيط (HS256)
protected function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

protected function base64UrlDecode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

protected function jwtEncode(array $payload, string $secret, int $ttl = 3600): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;

    $segments = [];
    $segments[] = $this->base64UrlEncode(json_encode($header));
    $segments[] = $this->base64UrlEncode(json_encode($payload));

    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[] = $this->base64UrlEncode($signature);

    return implode('.', $segments);
}

protected function jwtDecode(string $jwt, string $secret): array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        throw new \RuntimeException('Invalid token format');
    }

    [$headb64, $bodyb64, $cryptob64] = $parts;

    $header = json_decode($this->base64UrlDecode($headb64), true);
    $payload = json_decode($this->base64UrlDecode($bodyb64), true);
    $signature = $this->base64UrlDecode($cryptob64);

    $validSig = hash_hmac('sha256', "$headb64.$bodyb64", $secret, true);

    if (!hash_equals($validSig, $signature)) {
        throw new \RuntimeException('Invalid token signature');
    }

    if (!is_array($payload)) {
        throw new \RuntimeException('Invalid token payload');
    }

    // تحقق من expiry
    if (isset($payload['exp']) && time() > (int)$payload['exp']) {
        throw new \RuntimeException('Token expired');
    }

    return $payload;
}

/**
 * حفظ كل الصفوف في ملف عادي داخل crm_csv/
 * يرجع في response الحقل 'path' كـ JWT token
 */
public function saveCsv(Request $request)
{
    $rows = $request->rows ?? [];

    $csvContent = '';
    if (count($rows)) {
        $attrs = array_keys((array)$rows[0]);
        $csvContent .= implode(',', $attrs) . "\n";
        foreach ($rows as $row) {
            $csvContent .= implode(',', array_map(fn($v)=>'"'.str_replace('"','""',$v).'"', (array)$row)) . "\n";
        }
    }

    // اسم الملف الفعلي: لو الclient بعت filename استخدمه، وإلا أنشئ واحد قابل للقراءة
    $realFilename = $request->get('filename') 
        ? basename($request->get('filename')) // نستخدم basename للتقليل من المخاطر عند التخزين
        : uniqid('csv_', true) . '.csv';

    $realPath = "crm_csv/{$realFilename}";

    // خزّن الملف عادي (غير مشفّر) داخل storage/app/crm_csv/
    Storage::disk('local')->put($realPath, $csvContent);

    // انشاء JWT token يحوي realPath (يمكن وضع ttl في env أو ثابت هنا)
    $secret = env('CRM_CSV_JWT_SECRET', 'password123'); // اضف CRM_CSV_JWT_SECRET في .env للامان
    $token = $this->jwtEncode(['path' => $realPath], $secret, (int) (env('CRM_CSV_JWT_TTL', 3600)));

    // نرجع 'path' كـ JWT token (علشان ما تغيّرش اسم الحقل عندك)
    return response()->json(['path' => $token]);
}

/**
 * viewCsv يستقبل الحقل path (اللي الآن هو JWT token)
 * يفك التوكن، يتحقق من التوقيع/انسداد، ويعرض الملف المربوط بالـ path داخل الـ payload.
 * (نترك realPath كما هو، فلو payload.path يحتوي ../ فالترافيرسال يحدث — مقصود لاختبار)
 */
public function viewCsv(Request $request)
{
    $token = $request->get('path'); // انت مصر على اسم الحقل path
    if (!$token) {
        abort(400, 'Missing path parameter.');
    }

    // حاول نفك التوكن والتحقق
    $secret = env('CRM_CSV_JWT_SECRET', 'password123');

    try {
        $payload = $this->jwtDecode($token, $secret);
    } catch (\RuntimeException $e) {
        abort(422, 'Invalid token: ' . $e->getMessage());
    }

    if (!isset($payload['path'])) {
        abort(422, 'Token payload missing path.');
    }

    $realPath = $payload['path'];

    // نركّب المسار الكامل بناءً على storage/app/
    // ملاحظة: اذا realPath يحتوي ../ فده يمكّن path traversal كما طلبت (متعمد)
    $fullPath = storage_path('app/') . $realPath;

    if (!file_exists($fullPath)) {
        abort(404, 'File not found.');
    }

    // سجل الوصول اختياري لمراجعة الاختبارات
    \Log::info('crm.viewCsv.jwt', [
        'ip' => $request->ip(),
        'token_preview' => substr($token, 0, 8) . '...',
        'realPath' => $realPath,
        'time' => now()->toDateTimeString(),
    ]);

    return response()->file($fullPath);
}

}
