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
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">

    <!-- Page-specific CSS -->
	@if (isset($pageCss))
		<link href="{{ asset($pageCss) }}" rel="stylesheet">
	@endif

	<!-- Yield and stack for styles -->
	@yield('styles')
	@stack('styles')

</head>

<body data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
    <div class="container py-3 mobile-safe-area">

        <header>
            {{-- Mobile-Only Sticky Header --}}
            <div class="mobile-sticky-header">
                {{-- LEFT: Hamburger Menu Button --}}
                <button class="mobile-hamburger-btn" id="mobileHamburgerBtn" aria-label="Open menu" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>

                {{-- CENTER: Logo --}}
                <a href="{{ route('landing') }}" class="mobile-brand-center">
                    <img src="{{ asset('images/logo.png') }}" alt="SoVest" width="28" height="28">
                    <span>SoVest</span>
                </a>

                {{-- RIGHT: Profile Picture Only --}}
                <div class="mobile-header-right">
                    @auth
                        @php
                            $mobileHeaderPic = isset($Curruser['profile_picture']) && $Curruser['profile_picture']
                                ? asset('images/profile_pictures/' . $Curruser['profile_picture'])
                                : asset('images/default.png');
                        @endphp
                        <a href="{{ route('user.account') }}" class="mobile-profile-link">
                            <img src="{{ $mobileHeaderPic }}" alt="Profile">
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="mobile-icon-btn" aria-label="Login">
                            <i class="bi bi-person-circle"></i>
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Hamburger Menu Slide-out Drawer (Mobile Only) --}}
            <div class="mobile-hamburger-menu" id="mobileHamburgerMenu" aria-hidden="true">
                <div class="hamburger-menu-backdrop" id="hamburgerBackdrop"></div>
                <div class="hamburger-menu-content">
                    <div class="hamburger-menu-header">
                        <span class="hamburger-menu-title">Menu</span>
                        <button class="hamburger-close-btn" id="hamburgerCloseBtn" aria-label="Close menu">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <nav class="hamburger-menu-nav">
                        <a href="{{ route('user.settings') }}" class="hamburger-menu-item">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                        <a href="{{ route('predictions.index') }}" class="hamburger-menu-item">
                            <i class="bi bi-graph-up"></i>
                            <span>My Predictions</span>
                        </a>
                        <a href="{{ route('scoring.algorithm') }}" class="hamburger-menu-item">
                            <i class="bi bi-lightbulb"></i>
                            <span>Algo 101</span>
                        </a>
                        <a href="{{ route('feedback') }}" class="hamburger-menu-item">
                            <i class="bi bi-chat-left-text"></i>
                            <span>Feedback</span>
                        </a>
                    </nav>
                    @auth
                    <div class="hamburger-menu-footer">
                        <a href="{{ route('logout') }}" class="hamburger-menu-item logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                    @endauth
                </div>
            </div>

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
                        <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('leaderboard') ? 'active' : '' }}"
                            href="{{ route('user.leaderboard') }}">Leaderboard</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('groups*') ? 'active' : '' }}"
                            href="{{ route('groups.index') }}">Groups</a>
                    </li>
                </ul>

                {{-- Right: Profile Dropdown with Mobile Actions --}}
                @auth
                @php
                    $profilePicture = $Curruser['profile_picture']
                        ? asset('images/profile_pictures/' . $Curruser['profile_picture'])
                        : asset('images/default.png');
                @endphp
                    <div class="mobile-header-actions">
                        {{-- Mobile Create Prediction Button --}}
                        <a href="{{ route('predictions.create') }}" class="mobile-create-btn mobile-only" title="Create Prediction">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>

                    <div class="menu position-relative">
                        <button id="dropdownButton" class="nav-dropdown ">
                        <img src="{{ $profilePicture }}" alt="Profile Picture" class="pfp" />
                        </button>

                        <div id="dropdownMenu" class="drop-down-menu d-none">
                            <a href="{{ route('user.account') }}" class="drop-down-items">My Account</a>
                            <a href="{{ route('predictions.index') }}" class="drop-down-items">My Predictions</a>
                            <a href="{{ route('scoring.algorithm') }}" class="drop-down-items">Scoring Algo 101</a>
                            <a href="{{ route('feedback') }}" class="drop-down-items">Feedback</a>
                            <a href="{{ route('user.settings') }}" class="drop-down-items">Settings</a>
                            <a href="{{ route('logout') }}" class="drop-down-items logout">Logout</a>

                            {{-- Mobile Only: Active Predictions Section --}}
                            <div class="dropdown-predictions-section mobile-only">
                                <div style="padding: 0.5rem 0.75rem; font-weight: 600; font-size: 0.8rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="bi bi-lightning-charge" style="color: #10b981;"></i> Active Predictions
                                </div>
                                @if(!empty($Userpredictions) && count($Userpredictions) > 0)
                                    @foreach($Userpredictions->take(3) as $pred)
                                        <a href="{{ route('predictions.view', ['id' => $pred->prediction_id]) }}" class="dropdown-prediction-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="symbol">{{ $pred->stock->symbol ?? 'N/A' }}</span>
                                                <span class="badge {{ $pred->prediction_type == 'Bullish' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $pred->prediction_type }}
                                                </span>
                                            </div>
                                            <div class="company">{{ $pred->stock->company_name ?? '' }}</div>
                                        </a>
                                    @endforeach
                                    @if(count($Userpredictions) > 3)
                                        <a href="{{ route('predictions.index') }}" class="dropdown-prediction-item" style="text-align: center; color: #10b981;">
                                            View all {{ count($Userpredictions) }} predictions
                                        </a>
                                    @endif
                                @else
                                    <div style="padding: 0.75rem; text-align: center; color: #6b7280; font-size: 0.8rem;">
                                        No active predictions
                                    </div>
                                @endif
                            </div>
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

    {{-- Mobile Bottom Navigation Bar (visible only on mobile) --}}
    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        {{-- Home --}}
        <a href="{{ route('user.home') }}" class="mobile-nav-item {{ request()->is('home') ? 'active' : '' }}" aria-label="Home">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>

        {{-- Groups --}}
        <a href="{{ route('groups.index') }}" class="mobile-nav-item {{ request()->is('groups*') ? 'active' : '' }}" aria-label="Groups">
            <i class="bi bi-people"></i>
            <span>Groups</span>
        </a>

        {{-- Create Prediction --}}
        <a href="{{ route('predictions.create') }}" class="mobile-nav-item create-btn {{ request()->is('predictions/create') ? 'active' : '' }}" aria-label="Create Prediction">
            <i class="bi bi-plus-lg"></i>
            <span>Create</span>
        </a>

        {{-- Leaderboard --}}
        <a href="{{ route('user.leaderboard') }}" class="mobile-nav-item {{ request()->is('leaderboard') ? 'active' : '' }}" aria-label="Leaderboard">
            <i class="bi bi-trophy"></i>
            <span>Leaders</span>
        </a>

        {{-- Search --}}
        <a href="{{ route('search') }}" class="mobile-nav-item {{ request()->is('search') ? 'active' : '' }}" aria-label="Search">
            <i class="bi bi-search"></i>
            <span>Search</span>
        </a>
    </nav>

    {{-- Global Prediction Modal Component --}}
    <x-prediction-modal />

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    {{-- Prediction Modal JS --}}
    <script src="{{ asset('js/prediction-modal.js') }}" type="module"></script>

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

    <!-- Hamburger Menu Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerBtn = document.getElementById('mobileHamburgerBtn');
            const hamburgerMenu = document.getElementById('mobileHamburgerMenu');
            const closeBtn = document.getElementById('hamburgerCloseBtn');
            const backdrop = document.getElementById('hamburgerBackdrop');

            function openMenu() {
                if (hamburgerMenu) {
                    hamburgerMenu.classList.add('open');
                    hamburgerBtn.setAttribute('aria-expanded', 'true');
                    hamburgerMenu.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeMenu() {
                if (hamburgerMenu) {
                    hamburgerMenu.classList.remove('open');
                    hamburgerBtn.setAttribute('aria-expanded', 'false');
                    hamburgerMenu.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }
            }

            if (hamburgerBtn) {
                hamburgerBtn.addEventListener('click', openMenu);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeMenu);
            }

            if (backdrop) {
                backdrop.addEventListener('click', closeMenu);
            }

            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && hamburgerMenu && hamburgerMenu.classList.contains('open')) {
                    closeMenu();
                }
            });
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