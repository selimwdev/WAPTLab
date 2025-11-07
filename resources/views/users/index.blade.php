@extends('layouts.app')

@section('content')
<style>
  body {
    background: #f7f9fb;
  }

  .users-header {
    background: linear-gradient(135deg, #1f4f82, #2a72b5);
    color: #fff;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  .users-header h3 {
    font-weight: 600;
    margin: 0;
  }

  .card-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
    box-shadow: 0 3px 15px rgba(0,0,0,0.05);
  }

  .table {
    margin-bottom: 0;
  }

  .table thead {
    background: #f4f8fc;
  }

  .table thead th {
    color: #1f4f82;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    border-bottom: 2px solid #e0e6ed;
  }

  .table tbody tr:hover {
    background-color: #f4f9ff;
  }

  .table td {
    vertical-align: middle;
    font-size: 0.95rem;
  }

  .empty-list {
    text-align: center;
    color: #777;
    padding: 1.5rem;
  }

  .btn-outline-light {
    border-color: rgba(255,255,255,0.7);
    color: #fff;
    transition: all 0.2s ease-in-out;
  }

  .btn-outline-light:hover {
    background-color: rgba(255,255,255,0.15);
    transform: translateY(-2px);
  }
</style>

<div class="container mt-4">
  <!-- Header -->
  <div class="users-header d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
      <h3><i class="bi bi-people-fill me-2"></i> Users</h3>
      <span class="small text-light">CRM Main — User Directory</span>
    </div>
    <a href="{{ route('dashboard', ['db' => 'hr']) }}" class="btn btn-outline-light btn-sm">
      <i class="bi bi-arrow-left-circle me-1"></i> Back
    </a>
  </div>

  <!-- Users Table -->
  <div class="card-section">
    @if($users->isEmpty())
      <div class="empty-list">
        <i class="bi bi-info-circle me-1"></i> No users found.
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Role</th>
              <th>Name</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            @foreach($users as $user)
              <tr>
                <td class="text-secondary fw-medium">{{ $user->role ?? '—' }}</td>
                <td class="fw-semibold">{{ $user->name ?? '—' }}</td>
                <td class="text-muted">{!! $user->email ?? '—' !!}</td> {{-- غير مُفلتر --}}
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
