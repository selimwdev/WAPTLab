@extends('layouts.app')

@section('content')
<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold text-primary mb-0">
            <i class="bi bi-plus-circle-dotted me-2"></i> Create New Value
        </h3>
        <a href="{{ route('attributes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Back
        </a>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('entity-values.store') }}" method="POST" id="valueForm">
                @csrf

                <!-- Attribute Selection -->
                <h5 class="fw-semibold text-secondary mb-3">
                    <i class="bi bi-ui-checks-grid me-2"></i> Select Attributes
                </h5>
                <div id="attributesList" class="row">
                    @foreach($attributes as $attr)
                        <div class="col-md-4 mb-2">
                            <div class="form-check border rounded p-2 hover-card">
                                <input 
                                    class="form-check-input attr-checkbox" 
                                    type="checkbox" 
                                    value="{{ $attr->id }}" 
                                    id="attr{{ $attr->id }}"
                                    data-name="{{ $attr->name }}"
                                >
                                <label class="form-check-label" for="attr{{ $attr->id }}">
                                    <i class="bi bi-gear-wide-connected text-primary me-1"></i>
                                    {{ $attr->name }} 
                                    <span class="text-muted small">({{ $attr->data_type }})</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Selected Attributes Inputs -->
                <div id="selectedAttributes" class="mt-4"></div>

                <!-- Save Button -->
                <button type="submit" class="btn btn-primary mt-3 px-4 shadow-sm d-none" id="saveBtn">
                    <i class="bi bi-save2 me-1"></i> Save Values
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Inline Styles -->
<style>
    .hover-card {
        transition: all 0.2s ease-in-out;
    }
    .hover-card:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    label.form-label {
        font-weight: 500;
    }
</style>

<!-- Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = document.querySelectorAll('.attr-checkbox');
        const selectedContainer = document.getElementById('selectedAttributes');
        const saveBtn = document.getElementById('saveBtn');

        checkboxes.forEach(chk => {
            chk.addEventListener('change', () => {
                const attrId = chk.value;
                const attrName = chk.dataset.name;

                if (chk.checked) {
                    const div = document.createElement('div');
                    div.classList.add('mb-3');
                    div.id = `attrInput${attrId}`;
                    div.innerHTML = `
                        <label class="form-label">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> ${attrName}
                        </label>
                        <input type="hidden" name="attributes[${attrId}][id]" value="${attrId}">
                        <input type="text" name="attributes[${attrId}][value]" 
                               class="form-control shadow-sm" 
                               placeholder="Enter value for ${attrName}">
                    `;
                    selectedContainer.appendChild(div);
                } else {
                    const toRemove = document.getElementById(`attrInput${attrId}`);
                    if (toRemove) toRemove.remove();
                }

                
                saveBtn.classList.toggle('d-none', selectedContainer.children.length === 0);
            });
        });
    });
</script>
@endsection
