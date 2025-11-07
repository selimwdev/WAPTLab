@extends('layouts.app')

@section('content')
<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold text-primary mb-0">
            <i class="bi bi-file-earmark-arrow-up me-2"></i> CSV Import
        </h3>
        <a href="{{ route('dashboard', ['db' => 'hr']) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Back
        </a>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Upload Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('csv.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- File Input -->
                <div class="mb-4">
                    <label for="csv" class="form-label fw-semibold">
                        <i class="bi bi-upload me-1 text-primary"></i> Choose File
                    </label>
                    <input type="file" name="csv" id="csv" accept=".csv" required class="form-control shadow-sm">
                    <div class="form-text text-muted mt-1">
                        Allowed formats: <code>.csv</code>
                    </div>
                    <!-- <input type="text" name="filetype" value="0">
                    <input type="text" name="async" value="0"> 
                    -->
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Import
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Optional Custom Styles -->
<style>
    .form-label {
        font-weight: 500;
    }
    .card {
        border-radius: 10px;
    }
    .btn-primary {
        transition: all 0.2s ease-in-out;
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
</style>
@endsection
