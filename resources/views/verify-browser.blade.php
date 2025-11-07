@extends('layouts.app')

@section('content')
<div class="container text-center mt-5">
    <h3>Browser Verification</h3>
    <p>Please confirm you’re not a robot.</p>

    <form action="/verify-browser" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">I'm Human</button>
    </form>
</div>
@endsection
