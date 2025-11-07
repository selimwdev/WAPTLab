<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function fetchImageFromUrl(Request $request)
    {
        $request->validate([
            'image_url' => ['required', 'url'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['ok' => false, 'error' => 'unauthenticated'], 401);
        }

        $imageUrl = $request->input('image_url');

        // Parse only first host (no DNS resolution)
        $parsed = parse_url($imageUrl);
        $host = strtolower($parsed['host'] ?? '');

        // Block local/private/internal addresses by pattern only (no DNS)
        $blocked = false;
        $blockedPatterns = [
            '/^localhost$/',
            '/^127\./',
            '/^10\./',
            '/^192\.168\./',
            '/^172\.(1[6-9]|2[0-9]|3[0-1])\./',
            '/::1/',
        ];
        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $host)) {
                $blocked = true;
                break;
            }
        }

        if ($blocked) {
            return response()->json([
                'ok' => false,
                'error' => 'initial host blocked',
                'initial_host' => $host
            ], 403);
        }

        // Continue fetching
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $body = @curl_exec($ch);
        $err  = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($err || empty($body)) {
            return response()->json([
                'ok' => false,
                'error' => 'curl_failed',
                'curl_error' => $err,
                'curl_info' => $info
            ], 500);
        }

        // ====== تخزين الصورة وتحديث قاعدة البيانات ======
        $mime = $info['content_type'] ?? '';
        $extension = null;

        if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
            $extension = 'jpg';
        } elseif (str_contains($mime, 'png')) {
            $extension = 'png';
        } elseif (str_contains($mime, 'svg')) {
            $extension = 'svg';
        } else {
            $pathExt = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (in_array(strtolower($pathExt), ['png', 'jpg', 'jpeg', 'svg'])) {
                $extension = strtolower($pathExt) === 'jpeg' ? 'jpg' : strtolower($pathExt);
            }
        }

        if (! $extension) {
            return response()->json([
                'ok' => false,
                'error' => 'unsupported_type',
                'mime' => $mime,
                'final_url' => $info['url'] ?? $imageUrl,
            ], 415);
        }

        // اسم الملف داخل avatars
        $fileName = 'avatar_' . $user->id . '_' . time() . '.' . $extension;
        $relativePath = 'avatars/' . $fileName;

        try {
            \Storage::disk('public')->put($relativePath, $body);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'storage_failed',
                'exception' => $e->getMessage(),
            ], 500);
        }

        // تحديث عمود avatar
        $user->avatar = 'storage/' . $relativePath;
        $user->save();

        $finalUrl = $info['url'] ?? $imageUrl;

// نتحقق هل الرابط يحتوي على IP محلي
/*
if (preg_match('/(127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|::1|localhost)/', $finalUrl)) {
    $finalUrl = 'NUA{' . (string) Str::uuid() . '}';
}*/

if (preg_match('#(localhost|localdomain|local|gateway|router|internal|127\.|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|169\.254\.|::1|fc00:|fd00:|fe80:)#', $finalUrl)){
$finalUrl = 'NUA{' . (string) Str::uuid() . '}';
}



        return response()->json([
            'ok' => true,
            'message' => 'fetched_and_saved',
            'stored' => $relativePath,
            'final_url' => $finalUrl,
            'avatar' => $user->avatar
        ]);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('profile.show', compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'description' => ['nullable', 'string'],
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('profile.show', $user->id)->with('success', 'Profile updated!');
    }
}
