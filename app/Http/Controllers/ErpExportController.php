<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DOMDocument;
use XSLTProcessor;

class ErpExportController extends Controller
{
    public function export(Request $request)
    {
        // ✅ استقبل JSON body
        $data = $request->json()->all();

        $db = $data['db'] ?? 'default';
        $rows = $data['rows'] ?? [];
        $xsltString = $data['xslt'] ?? null;

        if (!$xsltString) {
            return response()->json(['error' => 'Missing XSLT input'], 400);
        }

        // ✅ نولّد XML بناءً على البيانات اللي جايه زي ما هي
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $root = $xml->createElement('customers');
        $root->setAttribute('db', $db);

        foreach ($rows as $r) {
            $c = $xml->createElement('customer');
            foreach ($r as $key => $value) {
                $c->appendChild($xml->createElement($key, htmlspecialchars($value ?? '')));
            }
            $root->appendChild($c);
        }

        $xml->appendChild($root);

        // ✅ حمّل الـ XSLT اللي جاي من الريكوست
        $xsl = new DOMDocument;
        $xsl->loadXML($xsltString);

        // ✅ طبّق التحويل
        $proc = new XSLTProcessor;
        $proc->importStylesheet($xsl);

        $result = $proc->transformToXML($xml);

        // ✅ رجّع XML كتحميل ملف
        return response($result, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="erp_export.xml"');
    }
}
