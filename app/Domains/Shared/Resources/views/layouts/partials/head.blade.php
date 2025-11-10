@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ config('app.name', 'Laravel') }}</title>
@endif

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
