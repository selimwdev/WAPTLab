<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // لو عندك جدول users

class EmailStatusController extends Controller
{
    public function check(Request $request)
    {
        $email = $request->input('email');

        if (!$email) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email field is required',
            ], 400);
        }

        // افترض إن عندك جدول users
        $exists = User::where('email', $email)->exists();

        return response()->json([
            'status' => 'success',
            'email' => $email,
            'exists' => $exists,
            'message' => $exists ? 'Email exists in the system' : 'Email not found',
        ]);
    }
}
