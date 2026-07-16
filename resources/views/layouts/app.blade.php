<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Maryam Go' }}</title>

    <!-- Panggil Asset CSS & JS Bawaan Laravel Vite (Tailwind, dll) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans antialiased">
    
    <!-- Tempat Komponen Livewire (Login/Dashboard) Di-render -->
    {{ $slot }}

</body>
</html>