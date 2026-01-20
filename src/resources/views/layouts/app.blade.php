<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="SoVest - Social Stock Predictions Platform">
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<title>@yield('title', $pageTitle ?? 'SoVest')</title>

	<!-- Vite Assets -->
	@vite(['resources/css/app.css', 'resources/js/app.js'])

	<!-- Bootstrap CSS (for backward compatibility) -->
	<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

	<!-- Bootstrap Icons -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">

    <!-- Main CSS file (legacy) -->
    <link rel="stylesheet" href="css/index.css">

    <!-- Page-specific CSS -->
	@if (isset($pageCss))
		<link href="{{ asset($pageCss) }}" rel="stylesheet">
	@endif

	<!-- Yield and stack for styles -->
	@yield('styles')
	@stack('styles')

    <!-- Layout Dark Mode Styles -->
    <style>
        /* Global text transition for smooth dark/light mode switching */
        *, *::before, *::after {
            transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* Header and Navigation */
        header .border-bottom {
            border-color: #e5e7eb !important;
        }

        body.dark-mode header .border-bottom {
            border-color: #404040 !important;
        }

        header .fs-4 {
            color: #111827;
            font-weight: 700;
        }

        body.dark-mode header .fs-4 {
            color: #f3f4f6;
        }

        nav .link-body-emphasis {
            color: #374151;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        nav .link-body-emphasis:hover {
            color: #10b981;
        }

        body.dark-mode nav .link-body-emphasis {
            color: #d1d5db;
        }

        body.dark-mode nav .link-body-emphasis:hover {
            color: #6ee7b7;
        }

        nav .link-body-emphasis.active {
            color: #10b981;
            font-weight: 600;
        }

        body.dark-mode nav .link-body-emphasis.active {
            color: #6ee7b7;
        }

        /* Dropdown Menu */
        .drop-down-menu {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode .drop-down-menu {
            background: #2d2d2d;
            border-color: #404040;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }

        .drop-down-items {
            color: #374151;
            transition: all 0.2s ease;
        }

        .drop-down-items:hover {
            background: #f3f4f6;
            color: #10b981;
        }

        body.dark-mode .drop-down-items {
            color: #d1d5db;
        }

        body.dark-mode .drop-down-items:hover {
            background: #404040;
            color: #6ee7b7;
        }

        /* Page Header */
        .pricing-header h1 {
            color: #111827;
        }

        body.dark-mode .pricing-header h1 {
            color: #f3f4f6;
        }

        .pricing-header .text-body-secondary {
            color: #6b7280 !important;
        }

        body.dark-mode .pricing-header .text-body-secondary {
            color: #9ca3af !important;
        }

        /* Footer */
        footer.border-top {
            border-color: #e5e7eb !important;
        }

        body.dark-mode footer.border-top {
            border-color: #404040 !important;
        }

        footer h5 {
            color: #111827;
            font-weight: 600;
        }

        body.dark-mode footer h5 {
            color: #f3f4f6;
        }

        footer .text-body-secondary {
            color: #6b7280 !important;
        }

        body.dark-mode footer .text-body-secondary {
            color: #9ca3af !important;
        }

        footer .link-secondary {
            color: #6b7280 !important;
            transition: color 0.2s ease;
        }

        footer .link-secondary:hover {
            color: #10b981 !important;
        }

        body.dark-mode footer .link-secondary {
            color: #9ca3af !important;
        }

        body.dark-mode footer .link-secondary:hover {
            color: #6ee7b7 !important;
        }

        footer li {
            color: #6b7280;
        }

        body.dark-mode footer li {
            color: #9ca3af;
        }

        /* Modals */
        .modal-content {
            background: white;
            color: #111827;
        }

        body.dark-mode .modal-content {
            background: #2d2d2d !important;
            color: #e5e7eb !important;
            border: 1px solid #404040;
        }

        body.dark-mode .modal-header {
            border-bottom-color: #404040;
        }

        body.dark-mode .modal-title {
            color: #f3f4f6;
        }

        body.dark-mode .modal-body {
            color: #d1d5db;
        }

        body.dark-mode .modal-body p {
            color: #d1d5db;
        }

        body.dark-mode .modal-body a {
            color: #6ee7b7;
        }

        body.dark-mode .modal-body a:hover {
            color: #10b981;
        }

        body.dark-mode .modal-footer {
            border-top-color: #404040;
        }

        /* Profile Picture */
        .pfp {
            border: 2px solid #e5e7eb;
            border-radius: 50%;
            transition: border-color 0.2s ease;
        }

        body.dark-mode .pfp {
            border-color: #10b981;
        }

        .pfp:hover {
            border-color: #10b981;
        }

        /* Container text colors */
        .container {
            color: #111827;
        }

        body.dark-mode .container {
            color: #e5e7eb;
        }

        /* Inline style overrides for dark mode */
        body.dark-mode [style*="color: #111827"],
        body.dark-mode [style*="color:#111827"] {
            color: #f3f4f6 !important;
        }

        body.dark-mode [style*="color: #333"],
        body.dark-mode [style*="color:#333"] {
            color: #f3f4f6 !important;
        }

        body.dark-mode [style*="color: #374151"],
        body.dark-mode [style*="color:#374151"] {
            color: #d1d5db !important;
        }

        body.dark-mode [style*="color: #6b7280"],
        body.dark-mode [style*="color:#6b7280"] {
            color: #9ca3af !important;
        }

        body.dark-mode [style*="color: #555"],
        body.dark-mode [style*="color:#555"] {
            color: #9ca3af !important;
        }

        /* Background overrides */
        body.dark-mode [style*="background: white"],
        body.dark-mode [style*="background:white"],
        body.dark-mode [style*="background-color: white"],
        body.dark-mode [style*="background-color:white"],
        body.dark-mode [style*="background: #fff"],
        body.dark-mode [style*="background:#fff"],
        body.dark-mode [style*="background-color: #fff"],
        body.dark-mode [style*="background-color:#fff"],
        body.dark-mode [style*="background: #ffffff"],
        body.dark-mode [style*="background:#ffffff"],
        body.dark-mode [style*="background-color: #ffffff"],
        body.dark-mode [style*="background-color:#ffffff"] {
            background-color: #2d2d2d !important;
        }

        /* Border overrides */
        body.dark-mode [style*="border-color: #e5e7eb"],
        body.dark-mode [style*="border-color:#e5e7eb"] {
            border-color: #404040 !important;
        }
    </style>

</head>

<body>
    <div class="container py-3">

        <header>
            <div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
                <a href="{{ route('landing') }}"
                    class="d-flex align-items-center link-body-emphasis text-decoration-none">
                    <img src="{{ asset('images/logo.png') }}" width="50px" alt="SoVest Logo" class="me-2">
                    <span class="fs-4">SoVest</span>
                </a>

                <nav class="d-flex align-items-center mt-2 mt-md-0 ms-md-auto">
                {{-- Left: Horizontal Nav Items --}}
                <ul class="navbar-nav d-flex flex-row me-3">
                    <li class="nav-item me-3">
                        <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('home') ? 'active' : '' }}"
                            href="{{ route('user.home') }}">Home</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('predictions/trending') ? 'active' : '' }}"
                            href="{{ route('predictions.trending') }}">Trending</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('scoring-algorithm') ? 'active' : '' }}"
                            href="{{ route('scoring.algorithm') }}">Scoring Algo 101</a>
                    </li>
                    <!-- REDIRECTING TO HOME PAGE FOR SOME REASON
                    <li class="nav-item me-3">
                        <a class="py-2 link-body-emphasis text-decoration-none {{ Route::is('user.leaderboard') ? 'active' : '' }}"
                            href="{{ route('user.leaderboard') }}">Leaderboard</a>
                    </li>  -->
                </ul>

                {{-- Right: Profile Dropdown --}}
                @auth
                @php
                    $profilePicture = $Curruser['profile_picture']
                        ? asset('images/profile_pictures/' . $Curruser['profile_picture']) 
                        : asset('images/default.png');
                @endphp
                                <div class="menu position-relative">
                        <button id="dropdownButton" class="nav-dropdown ">
                        <img src="{{ $profilePicture }}" alt="Profile Picture" class="pfp" />
                        </button>

                        <div id="dropdownMenu" class="drop-down-menu d-none">
                            <a href="{{ route('user.account') }}" class="drop-down-items">My Account</a>
                            <a href="{{ route('predictions.index') }}" class="drop-down-items">My Predictions</a>
                            <a href="{{ route('user.settings') }}" class="drop-down-items">Settings</a>
                            <a href="{{ route('logout') }}" class="drop-down-items logout">Logout</a>
                        </div>
                    </div>

                    {{-- JS: Toggle dropdown --}}
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const button = document.getElementById('dropdownButton');
                            const menu = document.getElementById('dropdownMenu');

                            button.addEventListener('click', function (e) {
                                e.stopPropagation();
                                menu.classList.toggle('d-none');
                            });

                            document.addEventListener('click', function (e) {
                                if (!button.contains(e.target) && !menu.contains(e.target)) {
                                    menu.classList.add('d-none');
                                }
                            });
                        });
                    </script>
                @endauth
            </nav>
            </div>

            @if (!empty($pageHeader))
                <div class="pricing-header p-3 pb-md-4 mx-auto text-center">
                    <h1 class="display-4 fw-normal">{{ $pageHeader }}</h1>
                    @if (!empty($pageSubheader))
                        <p class="fs-5 text-body-secondary">{{ $pageSubheader }}</p>
                    @endif
                </div>
            @endif
        </header>
        <main>
            @yield('content')
        </main>
        <footer class="pt-4 my-md-5 pt-md-5 border-top">
            <div class="row">
                <div class="col-12 col-md">
                    <img class="mb-2" src="{{ asset('images/logo.png') }}" alt="SoVest Logo" width="24" height="24">
                    <small class="d-block mb-3 text-body-secondary">&copy; {{ date('Y') }} SoVest</small>
                </div>
                <div class="col-6 col-md">
                    <h5>Features</h5>
                    <ul class="list-unstyled text-small">
                        <li><a class="link-secondary text-decoration-none" href="{{ route('search') }}">Stock Search</a>
                        </li>
                        <li><a class="link-secondary text-decoration-none"
                                href="{{ route('predictions.trending') }}">Trending Predictions</a></li>
                        <li><a class="link-secondary text-decoration-none"
                                href="{{ route('user.leaderboard') }}">Leaderboard</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md">
                    <h5>Resources</h5>
                    <ul class="list-unstyled text-small">
                        <li><a class="link-secondary text-decoration-none" href="#" id="aboutLink"
                                data-bs-toggle="modal" data-bs-target="#aboutModal">About SoVest</a></li>
                        <li><a class="link-secondary text-decoration-none" href="#" id="privacyLink"
                                data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a></li>
                        <li><a class="link-secondary text-decoration-none" href="#" id="contactLink"
                                data-bs-toggle="modal" data-bs-target="#contactModal">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md">
                    <h5>Connect</h5>
                    <ul class="list-unstyled text-small">
                        <li><a class="link-secondary text-decoration-none" href="#"><i class="bi bi-twitter"></i>
                                Twitter</a></li>
                        <li><a class="link-secondary text-decoration-none" href="#"><i class="bi bi-facebook"></i>
                                Facebook</a></li>
                        <li><a class="link-secondary text-decoration-none" href="#"><i class="bi bi-instagram"></i>
                                Instagram</a></li>
                    </ul>
                </div>
            </div>
        </footer>

        <!-- Modals -->
        <div class="modal fade" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title" id="aboutModalLabel">About SoVest</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>SoVest is a social platform for stock predictions and investment insights. Our mission is to
                            democratize stock prediction by allowing users to share their predictions and build
                            reputation based on accuracy.</p>
                        <p>After becoming interested in investing at an early age, Nate and Nelson started an investment
                            club at their Alma Mater. During this time, WallStreetBets, a subreddit dedicated to sharing
                            stock and option adive and wins was becoming extremely popular due to the Game Stop short
                            squeeze. Before the massive influx of users, genuinely good information and research could
                            be found on WallStreetBets, but with the massive influx of users, it has become more
                            about to Pump and Dump schemes rather than sharing quality information. SoVest has been
                            created to give people looking for quality research a place to go, where it is impossible to
                            fall victim to pump and dumps, because the Contributor's reputation is tied to every post.
                        </p>
                        <p>Created by Nate Pedigo and Nelson Hayslett.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>SoVest takes your privacy seriously. We collect only the information necessary to provide our
                            service and will never share your personal information with third parties without your
                            consent.</p>
                        <p>For more details, please contact us directly.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactModalLabel">Contact Us</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Have questions or suggestions? Reach out to us!</p>
                        <p>Email: <a href="mailto:contact@sovest.example.com">contact@sovest.example.com</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- Global Dark Mode Script -->
    <script>
        // Apply dark mode on page load if enabled
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'enabled') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>

    <!-- Page-specific JavaScript -->
    @if (isset($pageJs))
        <script src="{{ asset($pageJs) }}"></script>
    @endif

    <!-- Yield and stack for scripts -->
    @yield('scripts')
    @stack('scripts')
</body>

</html>