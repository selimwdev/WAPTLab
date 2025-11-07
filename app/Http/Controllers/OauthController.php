<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\OauthClient;
use App\Models\OauthAuthCode;
use Carbon\Carbon;

class OauthController extends Controller
{
    public function showAuthorizeForm(Request $request)
    {
        // استخدم القيم الافتراضية لو المستخدم مدخلش حاجه
        $client_id = $request->query('client_id', 'crm_main_client_123');
        $redirect_uri = $request->query('redirect_uri', 'https://sfs.example/callback');
        $state = $request->query('state', 'xyz123');

        $client = OauthClient::where('client_id', $client_id)->first();
        if (!$client) {
            return abort(400, 'Invalid client_id');
        }

        // لو مش عامل لوجين، يرجعه لصفحة اللوجين
        if (!Auth::check()) {
            return redirect()->guest(route('login') . '?next=' . urlencode($request->fullUrl()));
        }

        return view('oauth.authorize', compact('client', 'redirect_uri', 'state'));
    }

   public function approve(Request $request)
{
    $request->validate([
        'client_id' => 'required',
        'redirect_uri' => 'required|url',
        'action' => 'required|string',
        'state' => 'nullable|string',
    ]);

    $client = OauthClient::where('client_id', $request->client_id)->first();
    if (!$client) {
        return abort(400, 'Invalid client');
    }

    // --------------------------
    // Naive wildcard: accept any host that ENDS WITH "trusted.com"
    // --------------------------
$allowedSuffix = parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST);

    $reqHost = parse_url($request->redirect_uri, PHP_URL_HOST);
    if (empty($reqHost)) {
        return abort(400, 'Invalid redirect_uri host');
    }

    // Normalize host (lowercase + IDN -> ASCII if available)
    $reqHost = strtolower(@idn_to_ascii($reqHost, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $reqHost);

    if (!Str::endsWith($reqHost, $allowedSuffix)) {
        return abort(400, 'Invalid redirect_uri');
    }

    // باقي الكود كما هو
    if ($request->action !== 'approve') {
        return redirect()->route('oauth.authorize');
    }

    $code = Str::random(40);
    OauthAuthCode::create([
        'user_id' => Auth::id(),
        'code' => $code,
        'client_id' => $client->client_id,
        'redirect_uri' => $request->redirect_uri,
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

       $params = [
        'code' => $code,
    ];

    if ($request->state) {
        $params['state'] = $request->state;
    }

    // احصل الهوست المطلوب والهوست الحالي للسيرفر
    $reqHost = parse_url($request->redirect_uri, PHP_URL_HOST) ?: '';
    $serverHost = parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST) ?: '';

    if ($reqHost !== '' && $serverHost !== '' && $reqHost !== $serverHost && Str::endsWith($reqHost, $serverHost)) {
    // اطبع الفلاج مباشرة
    $flag = 'NUA{' . (string) Str::uuid() . '}';
    return response($flag);
}


    // بُني الـ URL بطريقة آمنة مع http_build_query
    $sep = (parse_url($request->redirect_uri, PHP_URL_QUERY) ? '&' : '?');
    $url = $request->redirect_uri . $sep . http_build_query($params);

    return redirect()->away($url);

}



    public function denied()
    {
        return view('dashboard.index');
    }
}
