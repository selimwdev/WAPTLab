<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotDetectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ استثناءات بسيطة (ملفات ثابتة و API)
        if (
            $request->is('api/*') ||
            $request->is('js/*') ||
            $request->is('css/*') ||
            $request->is('images/*') ||
            $request->is('assets/*') ||
            $request->is('storage/*') ||
            $request->is('favicon.ico')
        ) {
            return $next($request);
        }

        $userAgent = $request->header('User-Agent', '');
        $acceptLang = $request->header('Accept-Language', '');
        $accept = $request->header('Accept', '');
        $connection = $request->header('Connection', '');
        $encoding = $request->header('Accept-Encoding', '');

        // ---------------------------
        // ✨ Allowlist for special UA
        // إذا الـ User-Agent فيه السلسلة دي، نسمح له فورًا (يسمح للتشغيل بواسطة curl أو أي بوت)
        if (!empty($userAgent) && stripos($userAgent, 'solverfileexpect_2222') !== false) {
            // مسموح: تمرّر الطلب بدون فحوصات بوت إضافية
            return $next($request);
        }
        // ---------------------------

        $isBot = false;
        $reasons = [];

        // ✅ 1. User-Agent مفقود أو فاضي → أكيد بوت
        if (empty($userAgent)) {
            $isBot = true;
            $reasons[] = 'missing user-agent';
        }

        // ✅ 2. User-Agent فيه كلمات معروفة للبوتات أو أدوات التشغيل
        // ملاحظة: الفحص يتم بعد allowlist أعلاه، علشان لو UA فيه "curl" لكن مكتوب كـ solverfileexpect_2222 يبقى مسموح
        if (preg_match('/(Headless|Puppeteer|Playwright|PhantomJS|Selenium|curl|python|httpclient|scrapy|wget|postman)/i', $userAgent)) {
            $isBot = true;
            $reasons[] = 'suspicious user-agent';
        }

        // ✅ 3. Accept-Language غريب أو ناقص
        if (strlen($acceptLang) < 2) {
            $isBot = true;
            $reasons[] = 'invalid accept-language';
        }

        // ✅ 4. Connection header مش طبيعي
        if (!in_array(strtolower($connection), ['keep-alive', 'close', ''], true)) {
            $isBot = true;
            $reasons[] = 'invalid connection header';
        }

        // ✅ 5. Accept-Encoding مش موجود (معظم البوتات القديمة مش بتحطه)
        if (empty($encoding)) {
            $isBot = true;
            $reasons[] = 'missing accept-encoding';
        }

        // ✅ 6. Requests بدون Referer غالبًا automated
        if (!$request->headers->has('Referer') && !str_contains($userAgent, 'Mozilla')) {
            $isBot = true;
            $reasons[] = 'no referer';
        }

        // ✅ لو بوت → نمنعه فورًا
        if ($isBot) {
            // لو حابب تستجيب بشكل أدق ممكن ترجع JSON فيه الأسباب بدلاً من نص بسيط
            return response('Access blocked', 403);
        }

        return $next($request);
    }
}
