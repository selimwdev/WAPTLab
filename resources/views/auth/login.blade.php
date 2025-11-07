@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <!-- HEADER -->
                <div class="card-header text-center py-4" 
                     style="background: linear-gradient(135deg, #007bff, #004085); color: #fff;">
                    <h3 class="mb-0 fw-bold">Secure Login</h3>
                    <p class="mb-0 small text-light-50">Sign in to your account securely</p>
                </div>

                <div class="card-body p-4 bg-light">
                    <!-- STEP 1: EMAIL -->
                    <div id="step-email">
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope-check text-primary" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-dark fw-semibold">Enter your email to continue</h5>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark">Email Address</label>
                            <input type="email" id="email" class="form-control form-control-lg shadow-sm" placeholder="you@company.com" required autofocus>
                        </div>

                        <button id="nextBtn" class="btn btn-primary w-100 btn-lg shadow-sm">
                            Continue <i class="bi bi-arrow-right-circle ms-1"></i>
                        </button>
                        

                        <div id="emailError" class="alert alert-danger mt-3 d-none text-center py-2"></div>
                    </div>

                    <!-- STEP 2: PASSWORD -->
                    <div id="step-password" style="display:none;">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="text-center mb-4">
                                <i class="bi bi-lock-fill text-success" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 fw-semibold text-dark">Welcome back</h5>
                                <p class="small text-muted mb-0" id="user-email-preview"></p>
                            </div>

                            <input type="hidden" name="email" id="hidden-email">

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold text-dark">Password</label>
                                <input type="password" name="password" id="password" class="form-control form-control-lg shadow-sm" required placeholder="Enter your password">
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label small text-dark" for="remember">Remember me</label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a class="text-decoration-none small text-primary fw-semibold" href="{{ route('password.request') }}">
                                        Forgot Password?
                                    </a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-lg shadow-sm">
                                Login <i class="bi bi-box-arrow-in-right ms-1"></i>
                            </button>

                            <button type="button" id="backBtn" class="btn btn-outline-secondary w-100 mt-3">
                                <i class="bi bi-arrow-left-circle me-1"></i> Back
                            </button>
                        </form>


                    </div>
                </div>

                <!-- FOOTER -->
                <div class="card-footer text-center small text-muted bg-white py-3">
                    <span>&copy; {{ date('Y') }} Your Company. All rights reserved.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bootstrap Icons --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<script>
document.addEventListener('DOMContentLoaded', () => {
    const stepEmail = document.getElementById('step-email');
    const stepPassword = document.getElementById('step-password');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const emailInput = document.getElementById('email');
    const hiddenEmail = document.getElementById('hidden-email');
    const emailError = document.getElementById('emailError');
    const userEmailPreview = document.getElementById('user-email-preview');

    nextBtn.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        emailError.classList.add('d-none');

        if (!email) {
            showError("Please enter your email.");
            return;
        }

        nextBtn.disabled = true;
        nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Checking...';

        try {
            const response = await fetch("{{ route('check_email_status') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.exists) {
                hiddenEmail.value = email;
                userEmailPreview.textContent = email;
                fadeTransition(stepEmail, stepPassword);
            } else {
                showError("Email not found. Please check or register.");
            }
        } catch (err) {
            showError("Error checking email. Try again.");
        }

        nextBtn.disabled = false;
        nextBtn.innerHTML = 'Continue <i class="bi bi-arrow-right-circle ms-1"></i>';
    });

    backBtn.addEventListener('click', () => {
        fadeTransition(stepPassword, stepEmail);
    });

    function fadeTransition(hideEl, showEl) {
        hideEl.style.opacity = '0';
        setTimeout(() => {
            hideEl.style.display = 'none';
            showEl.style.display = 'block';
            setTimeout(() => showEl.style.opacity = '1', 100);
        }, 200);
    }

    function showError(message) {
        emailError.textContent = message;
        emailError.classList.remove('d-none');
    }
});
</script>
@endsection
