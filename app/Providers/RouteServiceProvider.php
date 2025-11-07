<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // التحقق من نسخة البروتوكول
            $protocol = $request->server('SERVER_PROTOCOL');

            // لو الطلب جاي بـ HTTP/1.1 → نطبّق limit 10 requests/min
            if (str_starts_with($protocol, 'HTTP/1.1')) {
                return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
            }

            // لو الطلب جاي بـ HTTP/2 → بدون أي limit
            return Limit::none();
        });

        $this->routes(function () {
            Route::middleware(['api', 'throttle:api'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware(['web', 'throttle:api'])
                ->group(base_path('routes/web.php'));
        });
    }
}
