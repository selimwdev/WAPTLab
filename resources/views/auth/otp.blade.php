@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-white text-center py-3"
                     style="background: linear-gradient(135deg, #2c3e50, #4b6584);">
                    <h3 class="mb-0 fw-bold">Verify OTP</h3>
                </div>

                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                        <h5 class="mt-2 text-muted">Enter the verification code sent to your email</h5>
                        <p class="small text-secondary">{{ session('email') }}</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger text-center">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('otp.verify') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ session('email') }}">

                        <div class="mb-3">
                            <label for="otp" class="form-label fw-semibold">OTP Code</label>
                            <input type="text" name="otp" id="otp" class="form-control form-control-lg text-center"
                                   placeholder="••••••" maxlength="6" required autofocus>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            Verify <i class="bi bi-check-circle ms-1"></i>
                        </button>

                        <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 mt-3">
                            <i class="bi bi-arrow-left-circle me-1"></i> Back to Login
                        </a>
                    </form>
                </div>

                <div class="card-footer text-center text-muted small bg-light">
                    <span>Didn’t receive the code?</span>
                    <a href="#" class="text-decoration-none fw-semibold text-primary ms-1">
                        Resend OTP
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Optional Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@endsection
