<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\User;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard'; // بعد تسجيل الدخول

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    // بعد تسجيل الدخول
    protected function authenticated(Request $request, $user)
    {
        if ($user->otp_enabled) {
            // توليد OTP
            $otp = rand(100000, 999999);
            $user->otp_code = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(5);
            $user->save();

            // إرسال OTP
            Mail::raw("Your OTP code is: $otp", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Your OTP Code');
            });

            // حفظ المستخدم في session مؤقت
            session([
                'otp_user_id' => $user->id,
                'otp_pending' => true
            ]);

            // تسجيل خروج مؤقت
            auth()->logout();

            return redirect()->route('otp.form');
        }

        return redirect()->intended($this->redirectPath());
    }
}
