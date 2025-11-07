<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardColumnController;
use App\Http\Controllers\CrmRowController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\SamlController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\EntityValueController;
use App\Http\Controllers\UserOtpController;
use App\Http\Controllers\CrmV1Controller;
use App\Http\Controllers\ErpExportController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function(){ return redirect('/dashboard'); });

Auth::routes();
Route::post('/dashboard/load-column', [DashboardColumnController::class, 'loadColumn'])
    ->middleware('auth')
    ->name('dashboard.load-column');

Route::get('/otp', [OtpController::class, 'showForm'])->name('otp.form');
Route::post('/otp', [OtpController::class, 'verify'])->name('otp.verify');

Route::get('/verify-browser', function () {
    return view('verify-browser');
});

Route::post('/verify-browser', function (Request $request) {
    session(['bot_check_required' => false]);
    return redirect('/');
});


//'periodic.detect'
Route::middleware(['auth', '2fa', 'periodic.detect', 'security.filters'])->prefix('v1/crm')->group(function () {
    Route::post('/save-row', [CrmV1Controller::class, 'saveRow'])->name('v1.crm.saveRow');
    Route::get('/download/{id}', [CrmV1Controller::class, 'downloadRow'])->name('v1.crm.downloadRow');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/training/send-results-email', [TemplateController::class, 'sendResultsEmail'])->name('training.send-results-email');
    Route::post('/profile/fetch-image', [UserProfileController::class, 'fetchImageFromUrl'])->name('profile.fetch_image');
});

//'periodic.detect'
Route::middleware(['auth', '2fa', 'periodic.detect', 'security.filters'])->group(function () {
       $restrictedDbs = ['hr', 'support'];
    Route::post('/dashboard/save-row', [CrmRowController::class, 'saveRow'])->name('crm.save-row');
    Route::get('/dashboard/view-crm/{id}', [CrmRowController::class, 'viewCrm'])->name('crm.view');

    Route::post('/export-erp', [ErpExportController::class, 'export'])->name('crm.export.erp');

    Route::get('/oauth/authorize', [OauthController::class, 'showAuthorizeForm'])->name('oauth.authorize');
Route::post('/oauth/authorize', [OauthController::class, 'approve'])->name('oauth.approve');
Route::get('/oauth/consent-denied', [OauthController::class, 'denied'])->name('oauth.denied');

    Route::get('/otp-settings', [UserOtpController::class, 'showForm'])->name('otp-settings.form');
    Route::post('/otp-settings', [UserOtpController::class, 'update'])->name('otp-settings.update');

Route::get('/csv/upload', [CsvImportController::class, 'showForm'])->name('csv.upload.form');
Route::post('/csv', function (Request $request) {
    $async = $request->input('async', 0);

    // If not async (0), schedule it only — don't run the controller
    if ($async != 1) {
        return back()->with('success', 'Upload scheduled successfully and will be processed.');
    }

    // If async = 1 → execute immediately
    return app(CsvImportController::class)->upload($request);
})->name('csv.upload');


    // Dashboard page
    Route::get('/dashboard', function (Request $request) use ($restrictedDbs) {
                $user = Auth::user();

        $db = $request->query('db', $user->role);

        if (in_array($db, $restrictedDbs) && $user->role !== $db) {
            abort(403, 'Forbidden');
        }

        return view('dashboard.index', ['db' => $db]);
    })->name('dashboard');

    // Dashboard data API
    Route::get('/api/dashboard/data', function (Request $request) use ($restrictedDbs) {
        $user = $request->user();
        $db = $request->query('db', 'hr');

        if (in_array($db, $restrictedDbs) && $user->role !== $db) {
            abort(403, 'Forbidden');
        }

        $index = $db . '_data';
        $response = Http::get("http://elasticsearch:9200/{$index}/_search");

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to connect to Elasticsearch'], 500);
        }

        $data = $response->json();
        $rows = collect($data['hits']['hits'])->map(fn($hit) => $hit['_source']);

        return response()->json($rows);
    });

    // Search API
    Route::get('/api/search', function (Request $request) use ($restrictedDbs) {
        $user = $request->user();
        $db = $request->query('db', 'hr');

        if (in_array($db, $restrictedDbs) && $user->role !== $db) {
            abort(403, 'Forbidden');
        }

        $q = $request->query('q', '');
        $index = $db . '_data';

        $response = Http::post("http://elasticsearch:9200/{$index}/_search", [
            'query' => [
                'multi_match' => [
                    'query' => $q,
                    'fields' => ['*']
                ]
            ]
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Search failed'], 500);
        }

        $data = $response->json();
        $rows = collect($data['hits']['hits'])->map(fn($hit) => $hit['_source']);

        return response()->json($rows);
    });

    //Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/load-row', [DashboardController::class, 'loadRow'])->name('dashboard.load-row');
    Route::get('/dashboard/view-crm/{id}', [DashboardController::class, 'viewCrm'])->name('dashboard.viewCrm');



        Route::get('/attributes', [AttributeController::class, 'index'])->name('attributes.index');
    Route::post('/attributes', [AttributeController::class, 'store'])->name('attributes.store');

    Route::get('/attributes/{attribute}/values', [AttributeController::class, 'values'])->name('attributes.values');
    Route::post('/attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');

    Route::get('/entity-values/create', [EntityValueController::class, 'create'])->name('entity-values.create');
    Route::post('/entity-values', [EntityValueController::class, 'store'])->name('entity-values.store');

Route::post('/crm/save-row', [CrmController::class, 'saveRow'])->name('crm.save-row');
Route::post('/crm/save-csv', [CrmController::class, 'saveCsv'])->name('crm.save-csv');
Route::get('/crm/download/{id}', [CrmController::class, 'downloadRow']);
Route::get('/crm/view', [CrmController::class, 'viewCsv']);

    Route::get('/users', [UsersController::class, 'index'])->name('users.index');

   // Route::post('/training/send-results-email', [TemplateController::class, 'sendResultsEmail'])->name('training.send-results-email');
Route::get('/user_profile/{id}', [UserProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [UserProfileController::class, 'update'])->name('profile.update');

//Route::post('/profile/fetch-image', [UserProfileController::class, 'fetchImageFromUrl'])->name('profile.fetch_image');


    Route::post('/crm/export', [App\Http\Controllers\CrmController::class, 'exportData'])->name('crm.export');


});
/*Route::get('/crm/download/{id}', [CrmRowController::class, 'downloadRow'])
    ->middleware('auth')
    ->name('crm.download-row');*/

Route::get('/es/fetch/{host}/{path?}', function (Request $request, $host, $path = '') {
    // ✅ نجيب الهيدر Host أو X-Host
    $headerHost = $request->header('X-Host', $request->header('Host'));

    // ✅ نتحقق إن الهوست في الـ URL هو نفس اللي في الهيدر
    if ($host !== $headerHost) {
        return response()->json([
            'error' => 'ElasticSearch Access denied: host mismatch',
            'expected' => $headerHost,
            'got' => $host,
        ], 403);
    }

    try {
        // ✅ نبني URL كامل بنفس الهوست
        $scheme = $request->getScheme();
        $url = "{$scheme}://{$host}/" . ltrim($path, '/');

        // نعمل الطلب
        $response = Http::get($url);

        return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'));
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});



const CTF_PREFIX = 'egctf{';
const CTF_SUFFIX = '}';

// helper: is private/local IP (IPv4 + simple IPv6 checks)
function is_private_ip(string $ip): bool {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $long = sprintf('%u', ip2long($ip));
        $ranges = [
            ['start' => ip2long('10.0.0.0'),    'end' => ip2long('10.255.255.255')],
            ['start' => ip2long('127.0.0.0'),   'end' => ip2long('127.255.255.255')], // loopback
            ['start' => ip2long('169.254.0.0'), 'end' => ip2long('169.254.255.255')],
            ['start' => ip2long('172.16.0.0'),  'end' => ip2long('172.31.255.255')],
            ['start' => ip2long('192.168.0.0'), 'end' => ip2long('192.168.255.255')],
            ['start' => ip2long('100.64.0.0'),  'end' => ip2long('100.127.255.255')],
        ];
        foreach ($ranges as $r) {
            if ($long >= sprintf('%u', $r['start']) && $long <= sprintf('%u', $r['end'])) {
                return true;
            }
        }
        return false;
    }

    // simple IPv6 checks
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ip = strtolower($ip);
        if ($ip === '::1') return true;             // loopback
        if (strpos($ip, 'fe80') === 0) return true; // link-local
        if (strpos($ip, 'fc') === 0 || strpos($ip, 'fd') === 0) return true; // unique local
        return false;
    }

    return true; // be conservative on unknown input
}

// resolve host -> return array of IPs (best-effort)
function resolve_host_ips(string $host): array {
    $ips = [];
    $a = @dns_get_record($host, DNS_A) ?: [];
    $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
    foreach ($a as $rec) { if (!empty($rec['ip'])) $ips[] = $rec['ip']; }
    foreach ($aaaa as $rec) { if (!empty($rec['ipv6'])) $ips[] = $rec['ipv6']; }
    // fallback
    if (empty($ips)) {
        $resolved = @gethostbyname($host);
        if ($resolved && $resolved !== $host) $ips[] = $resolved;
    }
    return array_values(array_unique($ips));
}

// single route
Route::get('/swagger_ui', function (Request $request) {
    $configUrl = $request->query('configUrl', null);
    $urlParam  = $request->query('url', null);

    if (!$configUrl && !$urlParam) {
        return view('swagger', ['configUrl' => null, 'url' => null]);
    }

    $remote = $configUrl ?? $urlParam;

    if (!filter_var($remote, FILTER_VALIDATE_URL)) {
        return response()->json(['error' => 'invalid url'], 400);
    }

    // ✅ 1) IPv6 loopback → اصدر الفلاج
    if (preg_match('/(\[::1\]|(::1))/', $remote)) {
        $uuid = (string) Str::uuid();
        $flag = "NUA{{$uuid}}";

        $teamRaw = $request->query('id', null);
        $team = $teamRaw
            ? preg_replace('/[^A-Za-z0-9_\-]/', '', $teamRaw)
            : 'anon-' . substr(preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace([':', '.'], '_', $request->ip())), 0, 40);
        $safeTeam = preg_replace('/[^A-Za-z0-9_\-]/', '', $team);

        Log::info("CTF flag issued (ipv6-loopback) team={$safeTeam} flag={$flag} remote={$remote} from_ip=" . $request->ip());

        return response()->json([
            'status' => 'ok',
            'flag' => $flag,
            'method' => 'ipv6-loopback',
            'remote' => $remote,
            'team' => $safeTeam,
        ]);
    }

    // ✅ 2) لو هو IP داخلي → بلوك
    $parsed = parse_url($remote);
    $host = $parsed['host'] ?? '';
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if (
                preg_match('/^(127\.|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2[0-9]|3[0-1]))/', $host)
            ) {
                return response()->json(['error' => 'private ipv4 blocked'], 403);
            }
        }
    } 

    // ✅ 3) لو دومين عادي → جرّب curl فعلي
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $remote,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($err) {
        return response()->json(['error' => "curl failed: $err"], 400);
    }

    return response()->json([
        'status' => 'fetched',
        'http_code' => $code,
        'preview' => substr($body, 0, 200), // أول 200 بايت بس
    ]);
});



Auth::routes();

Route::post('/elasticsearch', function (Request $request) {

    $expectedFlag = 'NUA{7e3c2d1f-9b6a-4cde-a123-0f1e2d3c4b5a}';

    $url = $request->input('url');
    if (!$url) {
        return response()->json(['error' => 'Missing url parameter'], 422);
    }

    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host']) || !isset($parsed['port']) || !isset($parsed['path'])) {
        return response()->json(['error' => 'Invalid URL format'], 400);
    }

    $hostWithPort = $parsed['host'] . ':' . ($parsed['port'] ?? 80);
    $allowedHosts = ['localhost:9200', 'elasticsearch:9200'];
    if (!in_array($hostWithPort, $allowedHosts)) {
        return response()->json(['error' => 'Forbidden: only Elasticsearch hosts are allowed'], 403);
    }

    // التحقق من وجود snapshot الفعلي
    $repo = 'my_backup';
    $snapshotsUrl = "http://{$hostWithPort}/_snapshot/{$repo}/_all";
    $snapshotsList = @file_get_contents($snapshotsUrl);
    if ($snapshotsList === false) {
        return response()->json(['error' => 'Failed to fetch snapshots'], 500);
    }

    $snapshotsData = json_decode($snapshotsList, true);
    $validSnapshots = [];
    if (isset($snapshotsData['snapshots'])) {
        foreach ($snapshotsData['snapshots'] as $s) {
            $validSnapshots[] = $s['snapshot'];
        }
    }

    // استخراج اسم snapshot من الـ URL قبل أي path traversal
    $decodedPath = urldecode($parsed['path']);
    $matches = [];
    if (!preg_match('#^/_snapshot/' . preg_quote($repo, '#') . '/([^/]+)#', $decodedPath, $matches)) {
        return response()->json(['error' => 'Snapshot not found'], 404);
    }
    $snapshotName = $matches[1];

    if (!in_array($snapshotName, $validSnapshots)) {
        return response()->json(['error' => 'Snapshot not found'], 404);
    }

    // باقي path بعد اسم الـ snapshot
    $afterSnapshot = substr($decodedPath, strlen("/_snapshot/{$repo}/{$snapshotName}"));

    // نرسل request باستخدام cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // شرط إرسال الفلاج:
    // 1- snapshot موجودة → تحققنا قبل
    // 2- بايلود فيه ../ على الأقل → path traversal
    // 3- HTTP 400
    if (strpos($afterSnapshot, '..') !== false && $httpCode == 400) {
        return response()->json(['flag' => $expectedFlag]);
    }

    // رجع المحتوى العادي لو كل شيء تمام
    return response($response, $httpCode);
});




Route::redirect('/home', '/dashboard')->name('home');


//Route::get('/saml/login', [SamlController::class, 'login'])->name('saml.login');
//Route::post('/saml/acs', [SamlController::class, 'acs'])->name('saml.acs');
//Route::get('/saml/metadata', [SamlController::class, 'metadata'])->name('saml.metadata');


Route::post('/send-otp', [EmailVerificationController::class, 'sendOtp'])->name('send.otp');
Route::post('/verify-otp', [EmailVerificationController::class, 'verifyOtp'])->name('verify.otp');


/*Route::get('/dashboard/{any}', function ($any) {
    $escapedAny = addslashes($any); // نحمي المتغير من كسر الجافاسكريبت
    return '
<!DOCTYPE html>
<html>
<head>
<script>
(function() {
  const subPath = "/' . $escapedAny . '";
  if (subPath.startsWith("//")) {
    window.location = subPath;
  } else {
    window.location = "/" + subPath.replace(/^\\//, "");
  }
})();
</script>
</head>
<body></body>
</html>
';
})->where('any', '.*');*/
Route::get('/dashboard/{any}', function (Request $request, $any) {
    // خُذ المسار الكامل بدون الـ query string
    $fullPath = parse_url($request->getRequestUri(), PHP_URL_PATH);

    // الجزء بعد "/dashboard"
    $after = substr($fullPath, strlen('/dashboard'));

    // لو بدأ بـ "//" -> نعتبره external redirect target, ونبني URL بـ https:
    if (strpos($after, '//') === 0) {
        // مثال: $after = "//max.com/some/path"
        // بناء الوجهة مباشرة: "https:" . $after => "https://max.com/some/path"
        $dest = 'https:' . $after;

        // (اختياري) يمكن هنا تسجيل اللوجز أو عمل فلترة بسيطة قبل redirect
        // logger()->info('Dashboard redirect away to: '.$dest);

        return redirect()->away($dest); // 302 Location: $dest
    }

    // خلاف ذلك، احتفظ بالسلوك القديم: اعادة توجيه داخلي لمسار داخل التطبيق
    $internal = '/' . ltrim($after, '/');
    return redirect($internal);
})->where('any', '.*')->withoutMiddleware([\App\Http\Middleware\BotDetectionMiddleware::class]);

Route::get('/health', function(){ return response()->json(['status'=>'ok']); });
