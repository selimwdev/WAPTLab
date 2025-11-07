@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-9 col-md-10">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                
                {{-- Header --}}
                <div class="card-header bg-primary text-white py-4 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0 fw-bold">{{ $user->name }}</h3>
                        <span class="text-light opacity-75 small">{{ ucfirst($user->role ?? 'User') }}</span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-light btn-sm fw-semibold px-3 shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                    </a>
                </div>

                {{-- Body --}}
                <div class="card-body bg-light px-4 py-5">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <img src="{{ asset($user->avatar ?? 'images/default-avatar.png') }}" 
                                 alt="Profile Image" 
                                 class="rounded-circle border border-4 border-white shadow"
                                 style="width:130px; height:130px; object-fit:cover;">
                            <div class="position-absolute bottom-0 end-0 translate-middle p-1 bg-success border border-light rounded-circle" 
                                 title="Active">
                            </div>
                        </div>
                        <h4 class="mt-3 fw-bold text-dark">{{ $user->name }}</h4>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                    </div>

                    <hr class="my-4">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="bg-white rounded-4 p-4 shadow-sm h-100 border-start border-4 border-primary-subtle">
                                <h6 class="text-uppercase text-muted small mb-2">Full Name</h6>
                                <p class="fw-semibold text-dark mb-0">{{ $user->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-white rounded-4 p-4 shadow-sm h-100 border-start border-4 border-primary-subtle">
                                <h6 class="text-uppercase text-muted small mb-2">Email Address</h6>
                                <p class="fw-semibold text-dark mb-0">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="bg-white rounded-4 p-4 shadow-sm border-start border-4 border-primary-subtle">
                            <h6 class="text-uppercase text-muted small mb-3">Profile Description</h6>
                            <div class="fs-6 text-dark" style="min-height:100px;">
                                {!! $user->description ? $user->description : '<em class="text-muted">No description provided.</em>' !!}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="card-footer text-center bg-white border-0 py-3">
                    <span class="text-muted small">
                        © {{ date('Y') }} — <strong>Enterprise CRM Portal</strong>. All rights reserved.
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
