<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectPeriodicRequests
{
    protected int $windowSeconds = 10;   // نافذة مراقبة 10 ثواني
    protected int $delaySeconds = 10;    // المدة اللي يتحظر فيها
    protected float $jitterThreshold = 0.3; // CV قليل => سلوك آلي

    public function handle(Request $request, Closure $next)
    {
        try {
            $identity = $request->user()?->id ? 'user:' . $request->user()->id : 'ip:' . $request->ip();
            $timestampsKey = "timestamps_{$identity}";
            $blockedKey = "blocked_{$identity}";

            // لو محظور
            if (Cache::has($blockedKey)) {
                $ttl = Cache::get($blockedKey . '_ttl', $this->delaySeconds);
                return response("Access denied — please wait {$ttl} seconds.", 429);
            }

            $now = microtime(true);
            $timestamps = Cache::get($timestampsKey, []);
            $timestamps[] = $now;

            // نظف الطوابع القديمة (أقدم من 10 ثواني)
            $timestamps = array_filter($timestamps, fn($t) => $t >= $now - $this->windowSeconds);
            Cache::put($timestampsKey, $timestamps, $this->windowSeconds + 2);

            // لو أقل من 2 طلب => ما نعملش حاجة
            if (count($timestamps) < 2) {
                return $next($request);
            }

            // نحسب الفروقات الزمنية بين كل طلبين
            $diffs = [];
            for ($i = 1; $i < count($timestamps); $i++) {
                $diffs[] = $timestamps[$i] - $timestamps[$i - 1];
            }

            $mean = array_sum($diffs) / count($diffs);
            $variance = 0;
            foreach ($diffs as $d) {
                $variance += pow($d - $mean, 2);
            }
            $stddev = sqrt($variance / count($diffs));
            $cv = $mean > 0 ? $stddev / $mean : INF;

            // لو الطلبات شبه ثابتة (CV قليل) وعدد الطلبات كبير => سلوك آلي
            if ($cv < $this->jitterThreshold && count($timestamps) >= $this->windowSeconds) {
                Cache::put($blockedKey, true, $this->delaySeconds);
                Cache::put($blockedKey . '_ttl', $this->delaySeconds, $this->delaySeconds);
                return response("Access denied — automated pattern detected. Wait {$this->delaySeconds} seconds.", 429);
            }

            return $next($request);
        } catch (\Throwable $e) {
            Log::error("SecurityMiddleware error: " . $e->getMessage());
            // بدون debug info للعميل
            //return response("Something went wrong.", 500);
            return $next($request);
        }
    }
}
