@extends('layouts.app')
@section('content')
<div class="container">
    <h3>Authorize {{ $client->name }}</h3>
    <p>Application <strong>{{ $client->name }}</strong> is requesting access to your account.</p>
    <form method="POST" action="{{ route('oauth.approve') }}">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->client_id }}">
        <input type="hidden" name="redirect_uri" value="{{ $redirect_uri }}">
        <input type="hidden" name="state" value="{{ $state }}">
        <button name="action" value="approve" class="btn btn-success">Approve</button>
        <button name="action" value="deny" class="btn btn-danger">Deny</button>
    </form>
</div>
@endsection
