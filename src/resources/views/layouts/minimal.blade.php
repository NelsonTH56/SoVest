<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SoVest - Social Stock Predictions Platform">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SoVest')</title>

    <!-- CRITICAL: Apply dark mode IMMEDIATELY before any rendering -->
    <script>
        (function() {
            var darkMode = localStorage.getItem('darkMode');
            var isDark = darkMode === 'enabled';
            var bgColor = isDark ? '#1a1a1a' : '#ffffff';
            var textColor = isDark ? '#e5e7eb' : '#111827';
            var html = document.documentElement;

            html.classList.add('no-transition');

            if (isDark) {
                html.classList.add('dark-mode');
            }

            document.write('<style>html,body{background-color:' + bgColor + ' !important;color:' + textColor + ' !important;}</style>');
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'enabled') {
                document.body.classList.add('dark-mode');
            }

            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    document.documentElement.classList.remove('no-transition');
                });
            });
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
    @yield('styles')
</head>

<body>
    <div class="container py-4">
        @yield('content')
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
