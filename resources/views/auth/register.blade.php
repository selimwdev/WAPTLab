@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-white text-center py-3"
                     style="background: linear-gradient(135deg, #2c3e50, #4b6584);">
                    <h3 class="mb-0 fw-bold">Create Account</h3>
                </div>

                <div class="card-body p-4">

                    {{-- STEP 1: إرسال كود OTP --}}
                    @if (!session('verified'))
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope-fill text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-2 text-muted">Enter your email to receive a verification code</h5>
                        </div>

                        <form method="POST" action="{{ route('send.otp') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input id="email" type="email"
                                       class="form-control form-control-lg @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                @error('email')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                Send Verification Code <i class="bi bi-send ms-1"></i>
                            </button>
                        </form>
                    @endif

                    {{-- STEP 2: إدخال كود OTP للتحقق --}}
                    @if (session('register_otp') && !session('verified'))
                        <hr>
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-2 text-muted">Enter the verification code sent to:</h5>
                            <p class="small text-secondary">{{ session('register_otp.email') }}</p>
                        </div>

                        <form method="POST" action="{{ route('verify.otp') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('register_otp.email') }}">
                            <div class="mb-3">
                                <label for="otp" class="form-label fw-semibold">OTP Code</label>
                                <input id="otp" type="text" maxlength="6"
                                       class="form-control form-control-lg text-center @error('otp') is-invalid @enderror"
                                       name="otp" required placeholder="••••••">
                                @error('otp')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                Verify Code <i class="bi bi-check-circle ms-1"></i>
                            </button>
                            <div class="text-center small mt-3">
                                Didn't receive the code?
                                <a href="{{ route('send.otp') }}" class="text-decoration-none text-primary fw-semibold">
                                    Resend OTP
                                </a>
                            </div>
                        </form>
                    @endif

                    {{-- STEP 3: فور التحقق، يظهر فورم التسجيل --}}
                    @if (session('verified'))
                        <hr>
                        <div class="text-center mb-4">
                            <i class="bi bi-person-circle text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-2 text-muted">Complete your registration</h5>
                            <p class="small text-secondary">{{ session('verified_email') }}</p>
                        </div>

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ session('verified_email') }}">

                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input id="name" type="text"
                                       class="form-control form-control-lg @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input id="password" type="password"
                                       class="form-control form-control-lg @error('password') is-invalid @enderror"
                                       name="password" required autocomplete="new-password">
                                @error('password')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password-confirm" class="form-label fw-semibold">Confirm Password</label>
                                <input id="password-confirm" type="password"
                                       class="form-control form-control-lg"
                                       name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                Register <i class="bi bi-person-plus ms-1"></i>
                            </button>
                        </form>
                    @endif

                    {{-- رسائل النجاح أو الخطأ --}}
                    @if (session('success'))
                        <div class="alert alert-success mt-3 text-center">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Optional Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection
