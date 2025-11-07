@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
  <style>
    body {
      background: #f7f9fb;
    }

    .page-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }

    .page-title h3 {
      font-weight: 700;
      color: #1f4f82;
    }

    .page-title span {
      color: #777;
      font-size: 0.9rem;
    }

    .form-section {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
      padding: 2rem;
      position: relative;
      overflow: hidden;
      border-left: 6px solid #2a72b5;
    }

    .form-label {
      font-weight: 600;
      color: #1f4f82;
    }

    .form-control {
      border-radius: 8px;
      padding: 0.7rem 0.9rem;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
      border-color: #d9e3f0;
      transition: all 0.2s;
    }

    .form-control:focus {
      border-color: #2a72b5;
      box-shadow: 0 0 0 0.2rem rgba(42, 114, 181, 0.15);
    }

    .btn-primary {
      background: linear-gradient(135deg, #1f4f82, #2a72b5);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.25s ease-in-out;
      box-shadow: 0 3px 10px rgba(42, 114, 181, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(31, 79, 130, 0.35);
    }

    .btn-secondary {
      border-radius: 8px;
      background-color: #e8edf3;
      color: #333;
      transition: all 0.25s;
    }

    .btn-secondary:hover {
      background-color: #d9e3f0;
      transform: translateY(-1px);
    }

    .upload-section {
      border: 2px dashed #b8c9e6;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      background-color: #f9fbfd;
      transition: all 0.2s;
    }

    .upload-section:hover {
      border-color: #2a72b5;
      background-color: #f1f7ff;
    }

    .cke_notification {
      display: none !important;
    }

    #fetchResult {
      transition: all 0.3s;
    }

    .form-text {
      font-size: 0.85rem;
      color: #6b7a8f;
    }
  </style>

  <div class="page-title">
    <div>
      <h3><i class="bi bi-person-gear me-2"></i>Edit Account</h3>
      <span>Update your personal details and preferences</span>
    </div>
  </div>

  <div class="form-section">
    <form action="{{ route('profile.update') }}" method="POST">
      @csrf

      {{-- Profile Image Upload --}}
      <div class="upload-section mb-4">
        <i class="bi bi-image-fill text-primary fs-3 mb-2"></i>
        <h6 class="fw-bold mb-2">Profile Image URL</h6>
        <input type="url" id="image_url_ajax" class="form-control mb-2" placeholder="https://{{ request()->getHost() }}/storage/avatar.png" />
        <small class="form-text d-block mb-2">Allowed: .png, .jpg, .jpeg, .svg</small>
        <button id="fetchImageBtn" class="btn btn-warning btn-sm fw-semibold">
          <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload
        </button>
        <div id="fetchResult" class="mt-3"></div>
      </div>

      {{-- Name --}}
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control"
          value="{{ old('name', $user->name) }}" required>
      </div>

      {{-- Email --}}
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control"
          value="{{ old('email', $user->email) }}" required>
      </div>

      {{-- Password --}}
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current">
      </div>

      {{-- Description --}}
      <div class="mb-3">
        <label for="description" class="form-label">Profile Description</label>
        <textarea name="description" id="description" class="form-control" rows="6">{{ old('description', $user->description) }}</textarea>
        <small class="form-text">Markdown allowed</small>
      </div>

      {{-- Buttons --}}
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn btn-primary px-4">
          <i class="bi bi-save2-fill me-1"></i> Save Changes
        </button>
        <a href="{{ route('profile.show', $user->id) }}" class="btn btn-secondary px-4">
          <i class="bi bi-x-circle me-1"></i> Cancel
        </a>
      </div>
    </form>
  </div>

  {{-- CKEditor + AJAX --}}
  <script src="https://cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
  <script>
    CKEDITOR.replace('description', {
      height: 200,
      removePlugins: 'notification,about'
    });

    document.getElementById('fetchImageBtn').addEventListener('click', async function (e) {
      e.preventDefault();
      const url = document.getElementById('image_url_ajax').value.trim();
      const resultDiv = document.getElementById('fetchResult');
      resultDiv.innerHTML = '<div class="text-muted">Uploading...</div>';

      if (!url) return resultDiv.innerHTML = '<div class="alert alert-danger">Enter a URL first</div>';

      const allowed = /\.(png|jpe?g|svg)$/i;
      if (!allowed.test(url)) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Only .png, .jpg, .jpeg, or .svg files are allowed.</div>';
        return;
      }

      try {
        const resp = await fetch("{{ route('profile.fetch_image') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ image_url: url })
        });
        const data = await resp.json();

        if (!resp.ok) {
          resultDiv.innerHTML = `<div class="alert alert-danger"><strong>Error:</strong> ${data.error || JSON.stringify(data)}</div>`;
          return;
        }

        resultDiv.innerHTML = `<div class="alert alert-success">✅ Saved: ${data.stored || ''}<br>📁 Final URL: ${data.final_url || ''}</div>`;
        setTimeout(() => location.reload(), 1000);
      } catch (err) {
        resultDiv.innerHTML = `<div class="alert alert-danger">Upload failed: ${err.message}</div>`;
      }
    });
  </script>
</div>
@endsection
