<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OauthAuthCode;
use App\Models\OauthClient;
use App\Models\OauthToken;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OauthApiController extends Controller
{
    public function token(Request $request)
    {
        $request->validate([
            'grant_type' => 'required|string',
            'code' => 'required_if:grant_type,authorization_code',
            'client_id' => 'required',
            'client_secret' => 'required',
            'redirect_uri' => 'required_if:grant_type,authorization_code|url'
        ]);

        if ($request->grant_type !== 'authorization_code') {
            return response()->json(['error' => 'unsupported_grant_type'], 400);
        }

        $client = OauthClient::where('client_id', $request->client_id)->first();
        if (!$client || $client->client_secret !== $request->client_secret) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $code = OauthAuthCode::where('code', $request->code)
            ->where('client_id', $client->client_id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$code) return response()->json(['error' => 'invalid_grant'], 400);

        // check redirect_uri matches saved one
        if ($code->redirect_uri !== $request->redirect_uri) {
            return response()->json(['error' => 'redirect_uri_mismatch'], 400);
        }

        // generate token
        $token = Str::random(80);
        $expires = Carbon::now()->addHours(1);

        OauthToken::create([
            'user_id' => $code->user_id,
            'client_id' => $client->client_id,
            'token' => hash('sha256', $token), 
            'expires_at' => $expires,
        ]);

        // delete code after use
        $code->delete();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expires->diffInSeconds(Carbon::now())
        ]);
    }
}
