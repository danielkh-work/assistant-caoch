@extends('layouts.app')
@section('content')
    <style>
       body {
            background-color: #ffca2d !important;
        }
        .left-side {
    background-image: url('assets/img/signup-Cai9C02I.png');
    background-size: cover;       /* Adjust image to cover entire div */
    background-position: center;  /* Center the image */
    background-repeat: no-repeat; /* Prevent repeating */
}
    </style>

<div class="left-side">
    {{-- <img src="{{asset('assets/img/signup-Cai9C02I.png')}}" alt="Logo" class="img-fluid"> --}}
</div>
<div class="right-side">
   <form class="login-form" method="POST" action="{{ route('login') }}">
        @csrf

        <h2 class="mb-4">Login</h2>
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" name="email" class="form-control" required=""
                            placeholder="Your Email Address">

            <span class="form-bar"></span>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror

        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required="" placeholder="Password">
            <span class="form-bar"></span>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror

        </div>
        <button type="submit" class="btn btn-primary w-100">Sign In</button>
    </form>
</div>

@endsection
