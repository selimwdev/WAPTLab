<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * Send results link email (safe simulated SSTI).
     */
    public function sendResultsEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'path' => 'required|string' // token/path returned by save-csv
        ]);

        $to = $request->input('to');
        $subject = $request->input('subject');
        $description = $request->input('description', '');
        $path = $request->input('path');

        // 1) Build the URL the recipient will click (safe)
        $resultsUrl = url('/crm/view') . '?path=' . urlencode($path); // path is token

        // 2) DETECT suspicious SSTI-like patterns in description (don't execute)
        $dangerPatterns = [
    '/\{\{\s*[^\}]*\([^\}]*\)\s*\}\}/is',

    '/\{\!\!\s*[^\}]*\([^\}]*\)\s*\!\!\}/is',

    '/\{\!\!.*?\!\!\}/is',

    '/\{\{\s*(?:config|env|request|session|app|route|view|old|csrf_token)\s*\(/i',

    '/@(?:include|includeIf|includeWhen|component|each|extends|section|yield|stack|push)\s*\(/i',
    '/@php\b/i',

    '/<\?(?:php)?/i',

    '/\beval\s*\(/i',
    '/\bassert\s*\(/i',

    '/`[^`]+`/i',
];

        foreach ($dangerPatterns as $pat) {
            if (preg_match($pat, $description)) {
                Log::warning('Training: suspicious SSTI payload in description', [
                    'user_id' => optional(auth()->user())->id,
                    'to' => $to,
                    'path' => $path,
                    'pattern' => $pat,
                    'snippet' => substr($description, 0, 300)
                ]);
                // reject to avoid rendering in email; respond with 422 so UI shows rejection
                return response()->json([
                    'status' => 'rejected',
                    'reason' => 'Description contains disallowed expressions (simulated SSTI detection)'
                ], 422);
            }
        }

        // 3) Safe rendering of description: allow only whitelist placeholders replaced server-side
        $allowedReplacements = [
            'name' => optional(auth()->user())->name ?? 'Training User',
            'date' => now()->toDateString(),
            'id' => 'TRAIN-RESULTS',
            'flag' => 'NUA{SSTI_IS_COOL}'
            // add any other allowed keys here
        ];

        $renderedDescription = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($allowedReplacements) {
            $k = $m[1];
            return $allowedReplacements[$k] ?? $m[0]; // unknown placeholders left as-is
        }, $description);

        // 4) Compose email body (do NOT use unescaped Blade eval). We send raw text/HTML.
        $body = "Hi,\n\n" .
                "A results link was shared with you.\n\n" .
                "Description:\n" . $renderedDescription . "\n\n" .
                "View results: " . $resultsUrl . "\n\n" .
                "This is a simulated training email.";

        // 5) Send email (MailHog capture in dev)
        Mail::raw($body, function ($msg) use ($to, $subject) {
            $msg->to($to)->subject($subject);
        });

        Log::info('Training: results email sent', [
            'user_id' => optional(auth()->user())->id,
            'to' => $to,
            'path' => $path
        ]);

        return response()->json(['status' => 'sent']);
    }
}
