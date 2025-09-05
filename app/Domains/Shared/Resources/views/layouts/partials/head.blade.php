@hasSection('title')
    <title>@yield('title')</title>
@else
    <title>{{ config('app.name', 'Laravel') }}</title>
@endif

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=manrope:300,400,500,600,700,800&display=swap" rel="stylesheet" />
<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<!-- Quill, temporary solution -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

@stack('meta')

<!-- Assets -->
@stack('styles')
@vite(['app/Domains/Shared/Resources/css/app.scss', 'app/Domains/Shared/Resources/js/app.js'])
