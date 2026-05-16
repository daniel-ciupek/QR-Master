<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#6366f1">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="QR-Master">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">

        <title inertia>{{ config('app.name') }}</title>

        {{-- Dark mode: apply class before CSS loads to prevent flash --}}
        <script>
            (function () {
                try {
                    var m = localStorage.getItem('qrmaster-color-mode') || 'system';
                    if (m === 'dark' || (m === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="h-full font-sans antialiased">
        @inertia
    </body>
</html>
