@extends('layouts.main_layout')
@section('content')

<div class="container">
    <h4 class="mb-4">Change Password for {{ $user->name }}</h4>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

    <form method="POST" action="{{ route('users.update-password', $user->id) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">
            Update Password
        </button>

        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            Cancel
        </a>
    </form>
</div>

@endsection