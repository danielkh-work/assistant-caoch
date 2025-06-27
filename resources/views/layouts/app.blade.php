<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>{{env('APP_NAME')}}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700">
    <link rel="stylesheet" href="{{asset('assets/plugins/fontawesome-free/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/docs.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/highlighter.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/adminlte.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/css/custom.css')}}">
    <link href="{{asset('assets/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/css/toastr.min.css')}}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .left-side, .right-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .left-side {
            background-color: #f8f9fa;
        }
        .right-side {
            background-color: #ffffff;
            padding: 2rem;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body >
    <div class="container">
    @yield('content')
    </div>

</body>
</html>
