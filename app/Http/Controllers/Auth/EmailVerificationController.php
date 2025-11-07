<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class EmailVerificationController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        Session::put('register_otp', [
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => $expiresAt
        ]);

        
        Mail::raw("Your verification code is: $otp", function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Email Verification Code');
        });

        return back()->with('success', 'Verification code sent to your email!');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);

        $sessionData = Session::get('register_otp');

        if (!$sessionData || $sessionData['email'] !== $request->email) {
            return back()->withErrors(['email' => 'No OTP sent for this email']);
        }

        if ($sessionData['expires_at'] < now()) {
            return back()->withErrors(['otp' => 'OTP expired']);
        }

        if ($sessionData['otp'] != $request->otp) {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        
        Session::put('verified_email', $request->email);
        return redirect()->route('register')->with('verified', true);
    }
}
