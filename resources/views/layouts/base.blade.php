<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'COSPARK Insurance Group')</title>
@hasSection('description')
<meta name="description" content="@yield('description')">
@endif
<meta name="theme-color" content="#0A111C">
<meta property="og:title" content="@yield('title', 'COSPARK Insurance Group')">
@hasSection('description')
<meta property="og:description" content="@yield('description')">
@endif
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif&family=JetBrains+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ \App\Support\AssetVersion::for('assets/css/style.css') }}">
</head>
<body data-page="{{ $page ?? '' }}">
@yield('body')
<script src="{{ asset('assets/js/main.js') }}?v={{ \App\Support\AssetVersion::for('assets/js/main.js') }}"></script>
@stack('scripts')
</body>
</html>
