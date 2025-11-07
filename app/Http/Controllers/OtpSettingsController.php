<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class OtpSettingsController extends Controller
{
    public function index()
    {
        
        $users = User::all();
        return view('otp-settings.index', compact('users'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'otp_enabled' => 'required|boolean',
        ]);

        $user->otp_enabled = $request->otp_enabled;
        $user->save();

        return back()->with('success', 'OTP setting updated for user: ' . $user->email);
    }
}
