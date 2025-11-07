<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckOtp
{
    public function handle(Request $request, Closure $next)
    {
        
        if (session('otp_pending')) {
            return redirect()->route('otp.form');
        }

        return $next($request);
    }
}
