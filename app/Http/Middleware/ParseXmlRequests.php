<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ParseXmlRequests
{
    public function handle(Request $request, Closure $next)
    {
        $contentType = $request->header('Content-Type');

        if ($contentType && str_contains($contentType, 'xml')) {
            $content = $request->getContent();

            try {
                $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOENT | LIBXML_DTDLOAD);

                $json = json_encode($xml);
                $array = json_decode($json, true);

                if (is_array($array)) {
                    $request->merge($array);
                }

            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Invalid XML format',
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        return $next($request);
    }
}
