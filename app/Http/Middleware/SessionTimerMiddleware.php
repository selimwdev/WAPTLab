<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;

class SessionTimerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        
        $protectedPaths = [
            'dashboard*',
            'crm*',
            'users*',
            'entity-values*',
            'attributes*',
            'profile*',
            'v1/crm*',
        ];

        $applyCheck = false;
        foreach ($protectedPaths as $pattern) {
            if ($request->is($pattern)) {
                $applyCheck = true;
                break;
            }
        }

        
        if (!$applyCheck) {
            return $next($request);
        }

        
        $cookieName = '_session_timer_token';
        $delayCookie = '_session_delay_until';
        $token = $request->cookie($cookieName);
        $delayUntil = $request->cookie($delayCookie);
        $now = Carbon::now();

        
        if ($delayUntil && $now->lessThan(Carbon::parse($delayUntil))) {
            $wait = Carbon::parse($delayUntil)->diffInSeconds($now);
            return response("Please wait {$wait} seconds before continuing.", 429);
        }

        
        if (!$token) {
            $newToken = bin2hex(random_bytes(16));
            $response = $next($request);
            return $response->cookie($cookieName, $newToken, 60);
        }

        
        if ($token && !$delayUntil) {
            $response = $next($request);
            $delayTime = $now->addSeconds(10);
            return $response->cookie($delayCookie, $delayTime->toDateTimeString(), 1)
                            ->withoutCookie($cookieName);
        }

        return $next($request);
    }
}
