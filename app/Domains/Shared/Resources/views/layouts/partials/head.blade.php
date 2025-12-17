@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ config('app.name', 'Laravel') }}</title>
@endif

<!-- Favicons -->
<link rel="icon" href="{{ $theme->asset('favicons/favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" sizes="48x48" href="{{ $theme->asset('favicons/favicon-48x48.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ $theme->asset('favicons/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ $theme->asset('favicons/favicon-16x16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $theme->asset('favicons/favicon-180x180.png') }}">
<meta name="theme-color" content="#f7eadf">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

@stack('meta')

<!-- Assets -->
@stack('styles')
@vite(['app/Domains/Shared/Resources/css/app.scss', 'app/Domains/Shared/Resources/js/app.js'])
