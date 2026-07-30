<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Book Your Stay') | {{ config('app.name') }}</title>
    @include('partials.favicon')

    <link rel="stylesheet" href="/landing/css/bootstrap.min.css">
    <link rel="stylesheet" href="/landing/css/all.min.css">
    <link rel="stylesheet" href="/landing/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/landing/css/menu.css">
    <link rel="stylesheet" href="/landing/css/style.css">
    <link rel="stylesheet" href="/landing/css/responsive.css">
    <link rel="stylesheet" href="/landing/css/booking.css">
</head>
<body class="booking-page">

    @include('partials.site-header')

    <main class="booking-main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    @include('partials.site-footer')

    <script src="{{ asset('landing/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('landing/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('landing/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('landing/js/header.js') }}"></script>
    <script src="{{ asset('landing/js/main.js') }}"></script>
    @yield('scripts')
</body>
</html>
