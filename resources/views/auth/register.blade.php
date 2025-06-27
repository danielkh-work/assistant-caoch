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
 <img src="{{asset('assets/images/favicon.png')}}" alt="Logo" class="img-fluid">
</div>
<div class="right-side">
    <form method="POST" class="login-form" action="{{ route('register') }}">

     @csrf

     <h2 class="mb-4">{{ __('Register') }}</h2>
     <div class="mb-3">
        <label for="email" class="form-label">Name</label>
        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
     </div>
     <div class="mb-3">
         <label for="email" class="form-label">Email address</label>
         <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

         @error('email')
             <span class="invalid-feedback" role="alert">
                 <strong>{{ $message }}</strong>
             </span>
         @enderror

     </div>
     <div class="mb-3">
         <label for="password" class="form-label">Password</label>
         <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

         @error('password')
             <span class="invalid-feedback" role="alert">
                 <strong>{{ $message }}</strong>
             </span>
         @enderror

     </div>
     <div class="mb-3">
         <label for="password" class="form-label">Confirmation Password</label>
         <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

     </div>
     <button type="submit" class="btn btn-primary">
        {{ __('Register') }}
    </button>
 </form>
</div>


@endsection
