<?php

use Illuminate\Http\Request;
use App\Http\Controllers\OauthApiController;
use App\Http\Controllers\Api\EmailStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/oauth/token', [OauthApiController::class, 'token'])->name('oauth.token');
Route::withoutMiddleware([\Fruitcake\Cors\HandleCors::class])
    ->post('/check_email_status', [EmailStatusController::class, 'check'])
    ->withoutMiddleware('throttle') //  rate limit
    ->name('check_email_status');

Route::middleware('auth.token')->get('/user', function (Request $request) {
    // middleware سيضع المستخدم في request->user_from_token
    return response()->json($request->user_from_token);
});



Route::middleware('auth:sanctum')->group(function(){
    //Route::get('/dashboard/data', [DashboardController::class,'data']);
   // Route::get('/search', [DashboardController::class,'search']);



   /* Route::get('/dashboard/data', function (Request $request) {
    $db = $request->query('db', 'hr'); // القيمة الافتراضية hr

    // تعيين اسم الـ index في Elasticsearch
    $index = $db . '_data';

    // استعلام إلى Elasticsearch
    $response = Http::get("http://localhost:9200/{$index}/_search");

    if ($response->failed()) {
        return response()->json(['error' => 'Failed to connect to Elasticsearch'], 500);
    }

    $data = $response->json();

    // استخراج البيانات من hits
    $rows = collect($data['hits']['hits'])->map(function ($hit) {
        return $hit['_source'];
    });

    return response()->json($rows);
});
Route::get('/search', function (Request $request) {
    $db = $request->query('db', 'hr');
    $q = $request->query('q', '');
    $index = $db . '_data';

    // طلب بحث في Elasticsearch
    $response = Http::post("http://localhost:9200/{$index}/_search", [
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
*/
});
