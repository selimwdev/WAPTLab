<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\OauthToken;
use Carbon\Carbon;
use App\Models\User;

class AuthenticateToken
{
    public function handle($request, Closure $next)
    {
        $header = $request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $tokenPlain = substr($header, 7);
        $tokenHash = hash('sha256', $tokenPlain);

        $rec = OauthToken::where('token', $tokenHash)->where('expires_at', '>', Carbon::now())->first();
        if (!$rec) return response()->json(['error' => 'Unauthorized'], 401);

        // attach user
        $request->user_from_token = \App\Models\User::find($rec->user_id);

        return $next($request);
    }
}
