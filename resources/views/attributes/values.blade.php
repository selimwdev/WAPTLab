@extends('layouts.app')

@section('content')
<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold text-primary mb-0">
            <i class="bi bi-sliders2 me-2"></i> Attribute Values — {{ $attribute->name }}
        </h3>
        <a href="{{ route('attributes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @elseif(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Add Value Form -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('attributes.values.store', $attribute->id) }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-muted">
                            <i class="bi bi-diagram-3 me-1"></i> Entity
                        </label>
                        <select name="entity_id" class="form-select" required>
                            <option value="">Select Entity...</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id }}">
                                    {{ $entity->namespace }} ({{ $entity->entity_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-medium text-muted">
                            <i class="bi bi-pencil-square me-1"></i> Value
                        </label>
                        <input type="text" name="value" class="form-control" placeholder="Enter value..." required>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Value
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Values List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light fw-semibold">
            <i class="bi bi-list-ul me-1"></i> Existing Values
        </div>

        <ul class="list-group list-group-flush">
            @forelse($values as $val)
                <li class="list-group-item d-flex justify-content-between align-items-center hover-bg-light">
                    <div>
                        <i class="bi bi-tag text-primary me-2"></i>
                        <strong>{{ $val->value }}</strong>
                        <span class="text-muted small">— for entity:</span>
                        <em class="text-dark">{{ $val->entity->namespace ?? 'N/A' }}</em>
                    </div>
                    <form method="POST" action="{{ route('attributes.values.destroy', [$attribute->id, $val->id]) }}" onsubmit="return confirm('Delete this value?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">
                    <i class="bi bi-inbox me-1"></i> No values found for this attribute.
                </li>
            @endforelse
        </ul>
    </div>
</div>

<style>
    .hover-bg-light:hover {
        background-color: #f9f9f9;
        transition: background-color 0.3s ease;
    }
</style>
@endsection
