<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class OtpController extends Controller
{
    public function showForm()
    {
        if (!session('otp_pending')) {
            return redirect()->route('login');
        }

        $userId = session('otp_user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        return view('auth.otp', ['email' => $user->email]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId = session('otp_user_id');
        $user = User::find($userId);

        if (!$user || !$user->otp_code || $user->otp_expires_at < Carbon::now()) {
            session()->forget(['otp_user_id','otp_pending']);
            return redirect()->route('login')->with('error', 'OTP expired. Login again.');
        }

        if ($request->otp != $user->otp_code) {
            return back()->with('error', 'Invalid OTP code.');
        }

        
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        
        Auth::login($user);
        session()->forget(['otp_user_id','otp_pending']);
        session(['2fa_passed' => true]);

        return redirect()->intended('/dashboard');
    }
}
