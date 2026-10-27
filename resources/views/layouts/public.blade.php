<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Immora')</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-paper text-ink">
    <header class="border-b border-line bg-white">
        <div class="mx-auto flex h-16 max-w-[640px] items-center px-5"><x-logo /></div>
    </header>
    <main class="mx-auto w-full max-w-[640px] flex-1 px-5 py-10">
        <x-flash />
        @yield('content')
    </main>
</body>
</html>
