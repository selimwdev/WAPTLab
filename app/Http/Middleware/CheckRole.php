<?php
namespace App\Http\Middleware;
use Closure;

class CheckRole {
    public function handle($request, Closure $next, $role) {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        if ($user->role !== $role) abort(403, 'Unauthorized');

        $db = $request->query('db');
        if ($db && $db !== $user->role) abort(403, 'DB and role mismatch');

        return $next($request);
    }
}
