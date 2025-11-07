<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserOtpController extends Controller
{
    public function showForm()
    {
        $user = Auth::user();
        return view('auth.otp-settings', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'otp_enabled' => 'required|boolean',
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Incorrect password.');
        }

        $user->otp_enabled = $request->otp_enabled;
        $user->save();

        return back()->with('success', 'OTP setting updated successfully.');
    }
}
