@extends('layouts.app')

@section('content')
<div class="container py-5">
  <style>
    body {
      background-color: #f7f9fb;
    }

    .otp-header {
      background: linear-gradient(135deg, #1f4f82, #2a72b5);
      color: #fff;
      padding: 1.2rem 1.5rem;
      border-radius: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .otp-header h3 {
      font-weight: 600;
      margin: 0;
    }

    .otp-header span {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.85);
    }

    .otp-card {
      background: #fff;
      border-radius: 14px;
      padding: 2rem;
      margin-top: 1.5rem;
      box-shadow: 0 6px 20px rgba(0,0,0,0.06);
      border-left: 5px solid #2a72b5;
      transition: all 0.3s ease;
    }

    .otp-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(31,79,130,0.12);
    }

    .form-label {
      font-weight: 600;
      color: #1f4f82;
    }

    .form-select,
    .form-control {
      border-radius: 8px;
      padding: 0.65rem 0.9rem;
      border: 1px solid #d9e3f0;
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
      transition: all 0.2s;
    }

    .form-select:focus,
    .form-control:focus {
      border-color: #2a72b5;
      box-shadow: 0 0 0 0.2rem rgba(42,114,181,0.15);
    }

    .btn-primary {
      background: linear-gradient(135deg, #1f4f82, #2a72b5);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.25s ease-in-out;
      box-shadow: 0 3px 10px rgba(42,114,181,0.3);
      padding: 0.6rem 1.4rem;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(31,79,130,0.35);
    }

    .alert {
      border-radius: 10px;
      font-weight: 500;
    }

    .alert-success {
      background-color: #e6f4ea;
      color: #146c43;
      border: none;
    }

    .alert-danger {
      background-color: #fde8e8;
      color: #842029;
      border: none;
    }
  </style>

  <div class="otp-header">
    <h3><i class="bi bi-shield-lock me-2"></i> OTP Settings</h3>
    <span>Manage your Two-Factor Authentication security</span>
  </div>

  <div class="otp-card">
    @if(session('success'))
      <div class="alert alert-success mb-3">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger mb-3">
        <i class="bi bi-x-circle-fill me-1"></i> {{ session('error') }}
      </div>
    @endif

    <form action="{{ route('otp-settings.update') }}" method="POST" class="mt-3">
      @csrf

      <div class="mb-4">
        <label for="otp_enabled" class="form-label">
          <i class="bi bi-lock-fill me-1 text-primary"></i> Enable OTP
        </label>
        <select name="otp_enabled" id="otp_enabled" class="form-select">
          <option value="1" {{ $user->otp_enabled ? 'selected' : '' }}>Yes — Enable Two-Factor Authentication</option>
          <option value="0" {{ !$user->otp_enabled ? 'selected' : '' }}>No — Disable OTP</option>
        </select>
        <small class="form-text text-muted">
          When enabled, OTP verification will be required at login.
        </small>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">
          <i class="bi bi-key-fill me-1 text-primary"></i> Confirm Password
        </label>
        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your account password" required>
      </div>

      <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save2-fill me-1"></i> Save Settings
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
