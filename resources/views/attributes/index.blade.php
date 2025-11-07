@extends('layouts.app')

@section('content')
<style>
  body {
    background: #f7f9fb;
  }

  .attributes-header {
    background: linear-gradient(135deg, #1f4f82, #2a72b5);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  .attributes-header h3 {
    font-weight: 600;
    margin: 0;
  }

  .form-control {
    border-radius: 8px;
    padding: 0.6rem 0.9rem;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
  }

  .btn-primary {
    background-color: #2a72b5;
    border-color: #2a72b5;
    border-radius: 8px;
    transition: all 0.2s ease-in-out;
  }

  .btn-primary:hover {
    background-color: #1f4f82;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }

  .list-group {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
  }

  .list-group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.15s ease-in-out;
  }

  .list-group-item:hover {
    background-color: #f4f9ff;
  }

  .list-group-item i {
    color: #1f4f82;
    font-size: 16px;
  }

  .form-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
  }

  .empty-list {
    text-align: center;
    color: #777;
    padding: 1rem;
  }
</style>

<div class="container mt-4">
  <div class="attributes-header d-flex justify-content-between align-items-center flex-wrap mb-4">
    <h3><i class="bi bi-tags me-2"></i> Attributes</h3>
    <span class="small text-light">Manage custom attribute definitions</span>
  </div>

  <div class="form-section">
    <form method="POST" action="{{ route('attributes.store') }}">
      @csrf
      <div class="row g-3 align-items-center">
        <div class="col-md-5">
          <input type="text" name="name" class="form-control" placeholder="Attribute Name" required>
        </div>
        <div class="col-md-5">
          <input type="text" name="data_type" class="form-control" placeholder="Data Type (e.g. string, int)" required>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100">
            <i class="bi bi-plus-circle me-1"></i> Add
          </button>
        </div>
      </div>
    </form>
  </div>

  <div class="mt-4">
    @if($attributes->isEmpty())
      <div class="empty-list">
        <i class="bi bi-info-circle me-1"></i> No attributes defined yet.
      </div>
    @else
      <ul class="list-group">
        @foreach($attributes as $attr)
          <li class="list-group-item">
            <div>
              <i class="bi bi-check2-circle me-2"></i>
              <strong>{{ $attr->name }}</strong> 
              <span class="text-muted">({{ $attr->data_type }})</span>
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</div>
@endsection
