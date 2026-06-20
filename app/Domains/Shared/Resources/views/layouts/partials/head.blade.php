@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ config('app.name', 'Laravel') }}</title>
@endif

<!-- Favicons -->
<link rel="icon" href="{{ $theme->asset('favicons/favicon.ico?v=20260425') }}" sizes="any">
<link rel="icon" type="image/png" sizes="48x48" href="{{ $theme->asset('favicons/favicon-48x48.png?v=20260425') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $theme->asset('favicons/favicon-32x32.png?v=20260425') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $theme->asset('favicons/favicon-16x16.png?v=20260425') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $theme->asset('favicons/favicon-180x180.png?v=20260425') }}">
@php
    $themeColor = match (true) {
        ($appearance ?? 'light') !== 'dark' => '#f7eadf',
        ($theme->value ?? '') === 'spring' => '#080c09',
        ($theme->value ?? '') === 'winter' => '#0a0e14',
        ($theme->value ?? '') === 'autumn' => '#0e0a08',
        default => '#0c0e10',
    };
@endphp
<meta name="theme-color" content="{{ $themeColor }}">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

@stack('meta')

<!-- Assets -->
@stack('styles')
@vite(['app/Domains/Shared/Resources/css/app.css', 'app/Domains/Shared/Resources/js/app.js'])
