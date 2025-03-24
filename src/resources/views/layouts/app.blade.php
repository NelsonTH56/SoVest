<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="SoVest - Social Stock Predictions Platform">
	<title>@yield('title', $pageTitle ?? 'SoVest')</title>

	<!-- Bootstrap CSS -->
	<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

	<!-- Favicon -->
	<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
	<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
	<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
	<link rel="manifest" href="{{ asset('images/site.webmanifest') }}">

    <!-- Page-specific CSS -->
	@if (isset($pageCss))
		<link href="{{ asset($pageCss) }}" rel="stylesheet">
	@endif

	<!-- Yield and stack for styles -->
	@yield('styles')
	@stack('styles')

    <style type="text/css">
        /* body {
            background-color: #2c2c2c;
            color: #d4d4d4;
        }

        .navbar {
            background-color: #1f1f1f;
        }

        .btn-primary {
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        } */

        /* TODO: Move to stylescheet */
        .navbar-nav {
            list-style: none;
            padding-left: 0;
        }

        .navbar-nav .nav-item {
            display: inline-block;
        }

        .active {
            font-weight: bold;
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

                <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
                    <ul class="navbar-nav d-flex flex-row">
                        <li class="nav-item me-3">
                            <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('home') ? 'active' : '' }}"
                                href="{{ route('user.home') }}">Home</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('search') ? 'active' : '' }}"
                                href="{{ route('search') }}">Search</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('predictions/trending') ? 'active' : '' }}"
                                href="{{ route('predictions.trending') }}">Trending</a>
                        </li>
                        <li class="nav-item me-3">
                            <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('about') ? 'active' : '' }}"
                                href="{{ route('about') }}">About Us</a>
                        </li>

                        @auth
                            <li class="nav-item me-3">
                                <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('predictions') ? 'active' : '' }}"
                                    href="{{ route('predictions.index') }}">My Predictions</a>
                            </li>
                            <li class="nav-item me-3">
                                <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('account') ? 'active' : '' }}"
                                    href="{{ route('user.account') }}">My Account</a>
                            </li>
                            <li class="nav-item">
                                <a class="py-2 link-body-emphasis text-decoration-none"
                                    href="{{ route('logout') }}">Logout</a>
                            </li>
                        @else
                            <li class="nav-item me-3">
                                <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('login') ? 'active' : '' }}"
                                    href="{{ route('login') }}">Login</a>
                            </li>
                            <li class="nav-item">
                                <a class="py-2 link-body-emphasis text-decoration-none {{ request()->is('register') ? 'active' : '' }}"
                                    href="{{ route('register.form') }}">Sign Up</a>
                            </li>
                        @endauth
                    </ul>
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


    <!-- Page-specific JavaScript -->
    @if (isset($pageJs))
        <script src="{{ asset($pageJs) }}"></script>
    @endif

    <!-- Yield and stack for scripts -->
    @yield('scripts')
    @stack('scripts')
</body>

</html>