<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Masuk') — SPK PMB</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-toast />
    <main class="auth-page">
        @yield('content')
    </main>
</body>
</html>

