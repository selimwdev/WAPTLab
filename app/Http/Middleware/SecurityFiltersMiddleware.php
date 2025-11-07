<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityFiltersMiddleware
{
    protected array $ignoredKeys = [
        '_token', '_method', '_previous', '_wysihtml5_mode', 'submit', 'csrf_token', 'button', 'action'
    ];

    public function handle(Request $request, Closure $next)
    {
    // --- Simple & strict global dangerous-check ---

$normalize = function (string $s) {
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = urldecode($s);
    $s = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
        return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UTF-16BE');
    }, $s);
    return $s;
};

$funcPattern = '/(?:\b|window\.|document\.)(?:alert|eval|prompt|confirm|setTimeout|setInterval|open|document\.write|document\.writeln)\s*\(/i';
$locationPattern = '/\b(?:window\.|document\.)?location(?:\.href|\.replace|\.assign)?\s*(?:=|\(|:)/i';
$jsSchemePattern = '/javascript\s*:/i';

$toCheck = [];
foreach ($request->all() as $v) {
    if (is_string($v)) {
        $toCheck[] = $v;
    } else {
        $toCheck[] = json_encode($v);
    }
}

$raw = $request->getContent();
if (!empty($raw)) {
    $toCheck[] = $raw;
}

foreach ($toCheck as $chunk) {
    $chunkNorm = $normalize((string)$chunk);

    if (preg_match($funcPattern, $chunkNorm, $m)) {
        Log::debug('SecurityFiltersMiddleware blocked func', [
            'matched' => $m[0],
            'preview' => substr($chunkNorm, 0, 200)
        ]);
        return $this->blockDetailed('Blocked dangerous function call detected', ['matched' => $m[0]]);
    }

    if (preg_match($locationPattern, $chunkNorm, $m)) {
        Log::debug('SecurityFiltersMiddleware blocked location', [
            'matched' => $m[0],
            'preview' => substr($chunkNorm, 0, 200)
        ]);
        return $this->blockDetailed('Blocked document/location usage detected', ['matched' => $m[0]]);
    }

    if (preg_match($jsSchemePattern, $chunkNorm, $m)) {
        Log::debug('SecurityFiltersMiddleware blocked javascript: scheme', [
            'matched' => $m[0],
            'preview' => substr($chunkNorm, 0, 200)
        ]);
        return $this->blockDetailed('Blocked javascript: scheme', ['matched' => $m[0]]);
    }
}


        // Merge all inputs + files
        $inputs = $request->all();
        $files = $request->allFiles();

        foreach ($files as $key => $file) {
            $inputs[$key] = $file;
        }

        // Inspect all user inputs recursively
        foreach ($inputs as $key => $value) {
            $resp = $this->inspectValue($key, $value);
            if ($resp instanceof Response) {
                return $resp;
            }
        }

        return $next($request);
    }

    protected function inspectValue(string $keyPath, $value)
    {
        if (in_array($keyPath, $this->ignoredKeys, true)) {
            return null;
        }

        // Recurse arrays
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $childKey = $keyPath === '' ? (string)$k : ($keyPath . '.' . (string)$k);
                $res = $this->inspectValue($childKey, $v);
                if ($res instanceof Response) return $res;
            }
            return null;
        }

        // If value is a file, read its content (only if small)
        if ($value instanceof UploadedFile) {
            $filename = $value->getClientOriginalName();
            $content = '';
            try {
                // Limit read to 1MB to avoid memory issues
                $content = file_get_contents($value->getRealPath(), false, null, 0, 1024*1024);
            } catch (\Throwable $e) {
                Log::warning("SecurityFiltersMiddleware: failed to read file {$filename}");
            }
            return $this->inspectValue("[file:$filename]", $content);
        }

        // Only process strings
        if (!is_string($value)) {
            return null;
        }

        if (trim($value) === '') {
            return null;
        }

        // ---------- SSRF ----------
        if ($this->looksLikeUrl($value)) {
            $host = $this->extractHostFromUrl($value);
            if ($host !== null && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                if ($this->isPrivateIPv4($host)) {
                    $detail = ['reason' => 'private IPv4 host detected'];
                    Log::debug('SecurityFiltersMiddleware blocked input', $detail);
                    return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
                }
            }
        }

        // ---------- XSS & dangerous client-side functions ----------
        $dangerousFns = [
            'alert', 'confirm', 'prompt', 'eval', 'Function', 'execScript',
            'setTimeout', 'setInterval', 'open', 'postMessage',
            'document.write', 'document.writeln', 'window.open',
        ];
        $fnPattern = '/(?<![\\w$.=])(?:' . implode('|', array_map('preg_quote', $dangerousFns)) . ')\\s*\\(/i';
        if (preg_match($fnPattern, $value, $m, PREG_OFFSET_CAPTURE)) {
            $match = $m[0][0] ?? null;
            $detail = ['matched'=>$match, 'key'=>$keyPath, 'value_preview'=>$this->preview($value)];
            Log::debug('SecurityFiltersMiddleware blocked input', $detail);
            return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
        }

        $navPattern = '/\b(?:(?:window|document)\.)?(?:location|location\.href)\b\s*(?:=|:)|\blocation\.replace\s*\(|\blocation\.assign\s*\(/i';
        if (preg_match($navPattern, $value, $m, PREG_OFFSET_CAPTURE)) {
            $match = $m[0][0] ?? null;
            $detail = ['matched'=>$match, 'key'=>$keyPath, 'value_preview'=>$this->preview($value)];
            Log::debug('SecurityFiltersMiddleware blocked input', $detail);
            return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
        }

        $domSinksPattern = '/\b(?:innerHTML|outerHTML|insertAdjacentHTML|document\.createElement)\b\s*(?:=|\()/i';
        if (preg_match($domSinksPattern, $value, $m, PREG_OFFSET_CAPTURE)) {
            $match = $m[0][0] ?? null;
            $detail = ['matched'=>$match, 'key'=>$keyPath, 'value_preview'=>$this->preview($value)];
            Log::debug('SecurityFiltersMiddleware blocked input', $detail);
            return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
        }
        
        // ---------- Dangerous HTML tags (excluding Markdown-safe ones) ----------
$dangerousTagsPattern = '/<\s*\/?\s*(script|iframe|object|embed|svg|link|style|meta|base|form|video|audio|source|frame|frameset)\b[^>]*>/i';
if (preg_match($dangerousTagsPattern, $value, $m, PREG_OFFSET_CAPTURE)) {
    $match = $m[0][0] ?? null;
    $detail = [
        'matched' => $match,
        'key' => $keyPath,
        'value_preview' => $this->preview($value)
    ];
    Log::debug('SecurityFiltersMiddleware blocked HTML tag', $detail);
    return $this->blockDetailed('SecurityFiltersMiddleware blocked HTML tag', $detail);
}

// ---------- Allow inline handlers, but block direct dangerous function calls ----------
// فلتر بسيط: افحص كل inline event handlers (on*) وامنع وجود استدعاءات دوال خطرة مثل alert( أو eval(
// فلتر inline event handlers (on*) لمنع استدعاء دوال خطيرة زي alert(


// ---------- Allow inline handlers, but block direct dangerous function calls ----------





        // ---------- SSTI ----------
        if (preg_match('/(?<!@)\{\{.*?\}\}/s', $value, $m, PREG_OFFSET_CAPTURE)) {
            $match = $m[0][0] ?? null;
            $detail = ['matched'=>$match, 'key'=>$keyPath, 'value_preview'=>$this->preview($value)];
            Log::debug('SecurityFiltersMiddleware blocked input', $detail);
            return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
        }

        // ---------- SQL Injection ----------
                //$sqlKeywords = ['select','insert','update','delete','union','where','limit','group\s+by','order\s+by','into','having', 'from'];
        $sqlKeywords = ['and', 'or', 'union', 'where', 'limit'];

        $pattern = '/\b(' . implode('|', $sqlKeywords) . ')\b/i';
        if (preg_match_all($pattern, $value, $matches, PREG_OFFSET_CAPTURE)) {
            $found = array_map(fn($m)=>strtolower($m[0]), $matches[0]);
            $distinct = array_values(array_unique($found));
            if (count($distinct) >= 2) {
                $detail = ['matched_keywords'=>$distinct,'key'=>$keyPath,'value_preview'=>$this->preview($value)];
                Log::debug('SecurityFiltersMiddleware blocked input', $detail);
                return $this->blockDetailed('SecurityFiltersMiddleware blocked input', $detail);
            }
        }

        return null;
    }

    protected function looksLikeUrl(string $s): bool
    {
        return (bool) preg_match('/https?:\/\//i', $s) || (bool) filter_var($s, FILTER_VALIDATE_URL);
    }

    protected function extractHostFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) return null;
        return $parts['host'] ?? null;
    }

    protected function isPrivateIPv4(string $ip): bool
    {
        $long = sprintf('%u', ip2long($ip));
        $ranges = [
            ['10.0.0.0','10.255.255.255'],
            ['172.16.0.0','172.31.255.255'],
            ['192.168.0.0','192.168.255.255'],
            ['127.0.0.0','127.255.255.255'],
            ['169.254.0.0','169.254.255.255'],
        ];
        foreach ($ranges as [$start,$end]) {
            if ($long >= sprintf('%u', ip2long($start)) && $long <= sprintf('%u', ip2long($end))) return true;
        }
        return false;
    }

    protected function preview(string $s,int $max=200): string
    {
        if (mb_strlen($s) <= $max) return $s;
        return mb_substr($s,0,50) . ' ...[truncated]... ' . mb_substr($s,-50);
    }

    protected function blockDetailed(string $message,array $detail): Response
    {
        return response()->json(array_merge([
            'error'=>true,
            'message'=>$message,
        ], $detail), 400);
    }
}
