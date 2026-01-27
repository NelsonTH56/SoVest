@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/leaderboard-card.css') }}">
<style>
    .prediction-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .prediction-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
    }

    .text-decoration-none {
        color: inherit;
    }

    .text-decoration-none:hover {
        color: inherit;
    }

    /* Unread prediction indicator */
    .unread-indicator {
        width: 10px;
        height: 10px;
        background-color: #3b82f6;
        border-radius: 50%;
        flex-shrink: 0;
        margin-right: 0.75rem;
        animation: pulse-unread 2s infinite;
    }

    @keyframes pulse-unread {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.7;
            transform: scale(0.9);
        }
    }

    body.dark-mode .unread-indicator {
        background-color: #60a5fa;
    }
</style>
@endsection

@section('content')
    <div class="container mt-4">
        {{-- Welcome Header --}}
        <div class="text-center animate-fade-in mb-4">
            <h1 class="mb-2" style="font-size: 2.5rem; font-weight: 800;">
                <span class="welcome-text">Welcome to </span>
                <span class="gradient-text">SoVest</span>
            </h1>
            <p class="welcome-subtext" style="font-size: 1.1rem; font-weight: 500;">Your community for market predictions and insights</p>
        </div>

        {{-- Mobile Hot Stocks Card Carousel --}}
        <div class="mobile-hot-cards-wrapper">
            <div class="mobile-hot-cards-header">
                <h6 class="mobile-hot-cards-title">
                    <i class="bi bi-people" style="color: #10b981;"></i>
                    Top Contributors
                </h6>
            </div>
            <div class="mobile-hot-cards-carousel" id="mobileHotCardsCarousel">
                {{-- Create New Card --}}
                <a href="{{ route('predictions.create') }}" class="mobile-hot-card create-card">
                    <div class="hot-card-icon-create">
                        <i class="bi bi-plus-lg"></i>
                    </div>
                    <span class="hot-card-label">New Prediction</span>
                </a>

                {{-- Hot Prediction Cards --}}
                @if(isset($hotPredictions))
                    @foreach($hotPredictions as $hot)
                    <a href="{{ route('user.profile', ['id' => $hot->user->id]) }}?prediction={{ $hot->prediction_id }}"
                       class="mobile-hot-card {{ $hot->prediction_type == 'Bullish' ? 'bullish' : 'bearish' }}">
                        <div class="hot-card-top">
                            <span class="hot-card-symbol">{{ $hot->stock->symbol }}</span>
                            <span class="hot-card-badge-mini {{ $hot->prediction_type == 'Bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                                <i class="bi bi-{{ $hot->prediction_type == 'Bullish' ? 'arrow-up' : 'arrow-down' }}"></i>
                            </span>
                        </div>
                        <div class="hot-card-price">${{ number_format($hot->target_price, 2) }}</div>
                        <div class="hot-card-user-info">
                            <span class="hot-card-name">{{ $hot->user->first_name }}</span>
                            <span class="hot-card-score">
                                <i class="bi bi-star-fill"></i>
                                {{ number_format($hot->user->reputation_score) }}
                            </span>
                        </div>
                        <div class="hot-card-engagement">
                            <span class="hot-card-votes-up"><i class="bi bi-hand-thumbs-up-fill"></i> {{ $hot->upvotes ?? 0 }}</span>
                        </div>
                    </a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Story Viewer Overlay (Mobile Only) --}}
        <div id="story-viewer" class="story-viewer" style="display: none;" aria-hidden="true" role="dialog" aria-label="Prediction Stories">
            {{-- Progress Bars Container --}}
            <div class="story-progress-container">
                @if(isset($hotPredictions))
                    @foreach($hotPredictions as $index => $hot)
                        <div class="story-progress-bar" data-index="{{ $index }}">
                            <div class="story-progress-fill"></div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Header: Close button and user info --}}
            <div class="story-header">
                <a href="#" class="story-user-link" data-user-id="">
                    <img src="" alt="" class="story-user-avatar">
                    <div class="story-user-info">
                        <span class="story-user-name"></span>
                        <span class="story-user-rep"><i class="bi bi-star-fill"></i> <span class="rep-value"></span></span>
                    </div>
                </a>
                <button class="story-close-btn" aria-label="Close stories">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            {{-- Story Content Area --}}
            <div class="story-content">
                <div class="story-stock-info">
                    <span class="story-stock-symbol"></span>
                    <span class="story-prediction-badge"></span>
                </div>

                <div class="story-price-info">
                    <div class="story-target">
                        <span class="label">Target Price</span>
                        <span class="value"></span>
                    </div>
                </div>

                <div class="story-reasoning"></div>

                <div class="story-votes">
                    <span class="story-upvotes"><i class="bi bi-hand-thumbs-up-fill"></i> <span class="count">0</span></span>
                    <span class="story-downvotes"><i class="bi bi-hand-thumbs-down-fill"></i> <span class="count">0</span></span>
                </div>

                <div class="story-end-date">
                    <i class="bi bi-clock"></i> Ends <span class="date"></span>
                </div>
            </div>

            {{-- Navigation Areas (invisible touch zones) --}}
            <div class="story-nav-prev" role="button" aria-label="Previous prediction" tabindex="0"></div>
            <div class="story-nav-next" role="button" aria-label="Next prediction" tabindex="0"></div>

            {{-- Accessibility: Previous/Next buttons --}}
            <button class="story-btn-prev" aria-label="Previous prediction">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="story-btn-next" aria-label="Next prediction">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

    <div class="row mobile-bottom-padding">
    {{-- Left Sidebar: Leaderboard --}}
    <div class="col-lg-3 col-md-4 order-2 order-lg-1 mobile-hide-sidebar">
        <div class="sticky-top" style="top: 1rem;">
            {{-- Leaderboard Card --}}
            <div class="card mb-4 leaderboard-card">
                <div class="card-body" style="padding: 1.25rem;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-trophy-fill" style="color: #f59e0b;"></i>
                            Leaderboard
                        </h5>
                        <a href="{{ route('user.leaderboard') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 20px; font-size: 0.8rem;">
                            View All
                        </a>
                    </div>

                    @if(!empty($leaderboardUsers) && count($leaderboardUsers) > 0)
                        <div class="leaderboard-list">
                            @foreach($leaderboardUsers as $index => $leader)
                                <div class="leaderboard-item d-flex align-items-center justify-content-between" style="padding: 0.5rem 0.5rem; margin-bottom: 0.25rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        {{-- Rank with medal icon for top 3 --}}
                                        @if($index === 0)
                                            <i class="bi bi-1-circle-fill" style="color: #fbbf24; font-size: 1.25rem;"></i>
                                        @elseif($index === 1)
                                            <i class="bi bi-2-circle-fill" style="color: #9ca3af; font-size: 1.25rem;"></i>
                                        @elseif($index === 2)
                                            <i class="bi bi-3-circle-fill" style="color: #cd7f32; font-size: 1.25rem;"></i>
                                        @else
                                            <span class="rank-number" style="width: 1.25rem; text-align: center; font-weight: 600; font-size: 0.85rem; color: #6b7280;">{{ $index + 1 }}</span>
                                        @endif
                                        {{-- User Name --}}
                                        <span class="leaderboard-name" style="font-weight: 600; font-size: 0.9rem;">
                                            {{ $leader['first_name'] }} {{ substr($leader['last_name'], 0, 1) }}.
                                        </span>
                                    </div>
                                    {{-- Reputation Score --}}
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi bi-star-fill" style="color: #f59e0b; font-size: 0.75rem;"></i>
                                        <span class="leaderboard-score" style="font-weight: 700; font-size: 0.85rem; color: #10b981;">
                                            {{ number_format($leader['reputation_score']) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center mb-0" style="padding: 1rem;">
                            <i class="bi bi-trophy" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            No rankings yet
                        </p>
                    @endif
                </div>
            </div>

            {{-- Desktop Hot Posts Carousel --}}
            @if(isset($hotPredictions) && count($hotPredictions) >= 3)
            <div class="desktop-hot-carousel-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 hot-carousel-title">
                        <i class="bi bi-fire" style="color: #ef4444;"></i>
                        Hot posts from the top 10%
                    </h6>
                    <button class="hot-carousel-pause-btn" id="hotCarouselPauseBtn" aria-label="Pause carousel" title="Pause auto-rotation">
                        <i class="bi bi-pause-fill"></i>
                    </button>
                </div>
                <div class="desktop-hot-carousel" id="desktopHotCarousel">
                    <div class="hot-carousel-track" id="hotCarouselTrack">
                        @foreach($hotPredictions as $index => $hot)
                        <a href="{{ route('user.profile', ['id' => $hot->user->id]) }}?prediction={{ $hot->prediction_id }}"
                           class="hot-carousel-card"
                           data-index="{{ $index }}"
                           data-prediction-id="{{ $hot->prediction_id }}">
                            <div class="hot-card-header">
                                <span class="hot-card-symbol {{ $hot->prediction_type == 'Bullish' ? 'bullish' : 'bearish' }}">
                                    {{ $hot->stock->symbol }}
                                </span>
                                <span class="hot-card-badge {{ $hot->prediction_type == 'Bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                                    <i class="bi bi-{{ $hot->prediction_type == 'Bullish' ? 'arrow-up' : 'arrow-down' }}"></i>
                                </span>
                            </div>
                            <div class="hot-card-price">
                                ${{ number_format($hot->target_price, 2) }}
                            </div>
                            <div class="hot-card-user">
                                <span class="hot-card-username">{{ $hot->user->first_name }}</span>
                                <span class="hot-card-rep">
                                    <i class="bi bi-star-fill"></i>
                                    {{ number_format($hot->user->reputation_score) }}
                                </span>
                            </div>
                            <div class="hot-card-votes">
                                <span class="hot-card-upvotes"><i class="bi bi-hand-thumbs-up-fill"></i> {{ $hot->upvotes ?? 0 }}</span>
                                <span class="hot-card-downvotes"><i class="bi bi-hand-thumbs-down-fill"></i> {{ $hot->downvotes ?? 0 }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="hot-carousel-indicators" id="hotCarouselIndicators">
                    @foreach($hotPredictions as $index => $hot)
                    <button class="hot-carousel-dot {{ $index === 2 ? 'active' : '' }}"
                            data-index="{{ $index }}"
                            aria-label="Go to slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Center Column: Main Content Feed --}}
    <div class="col-lg-6 col-md-8 order-1 order-lg-2">
        {{-- Quick Search Bar --}}
        <div class="quick-search-container mb-4">
            <form action="{{ url('search') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="query" placeholder="Search stocks, predictions, or users..." class="form-control search-input-modern" />
                <button type="submit" class="btn btn-primary" style="border-radius: 0.75rem; padding: 0.75rem 1.5rem;">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <style>
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dark mode welcome text */
        body.dark-mode .welcome-text {
            color: #f3f4f6 !important;
        }

        body.dark-mode .welcome-subtext {
            color: #9ca3af !important;
        }

        /* Modern search input */
        .search-input-modern {
            border-radius: 0.75rem;
            padding: 0.75rem 1.25rem;
            border: 2px solid #e5e7eb;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input-modern:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        body.dark-mode .search-input-modern {
            background-color: #2a2a2a;
            border-color: #404040;
            color: #e5e7eb;
        }

        body.dark-mode .search-input-modern:focus {
            border-color: #10b981;
            background-color: #2d2d2d;
        }

        body.dark-mode .search-input-modern::placeholder {
            color: #6b7280;
        }

        /* Feed section header */
        .feed-header {
            color: #111827;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        body.dark-mode .feed-header {
            color: #f3f4f6;
            border-bottom-color: #404040;
        }

        /* Feed header container with dropdown */
        .feed-header-container {
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        body.dark-mode .feed-header-container {
            border-bottom-color: #404040;
        }

        /* Feed header inline - no divider */
        .feed-header-inline {
            display: flex;
            align-items: center;
        }

        /* Inline sort dropdown button */
        .sort-dropdown-btn-inline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0;
            background: transparent;
            border: none;
            color: #111827;
            font-size: 1.25rem;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .sort-dropdown-btn-inline:hover {
            color: #10b981;
        }

        .sort-dropdown-btn-inline:focus {
            outline: none;
        }

        .sort-dropdown-btn-inline .sort-label-text {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sort-dropdown-btn-inline .chevron-icon {
            font-size: 0.875rem;
            color: #6b7280;
            transition: transform 0.2s ease;
        }

        .sort-dropdown-btn-inline.open .chevron-icon {
            transform: rotate(180deg);
        }

        body.dark-mode .sort-dropdown-btn-inline {
            color: #f3f4f6;
        }

        body.dark-mode .sort-dropdown-btn-inline:hover {
            color: #10b981;
        }

        body.dark-mode .sort-dropdown-btn-inline .chevron-icon {
            color: #9ca3af;
        }

        /* Sort Dropdown Styles */
        .sort-dropdown-wrapper {
            position: relative;
        }

        .sort-dropdown-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.625rem;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sort-dropdown-btn:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        .sort-dropdown-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            border-color: #10b981;
        }

        .sort-dropdown-btn .chevron-icon {
            transition: transform 0.2s ease;
        }

        .sort-dropdown-btn.open .chevron-icon {
            transform: rotate(180deg);
        }

        body.dark-mode .sort-dropdown-btn {
            background: #2a2a2a;
            border-color: #404040;
            color: #e5e7eb;
        }

        body.dark-mode .sort-dropdown-btn:hover {
            background: #333333;
            border-color: #525252;
        }

        /* Dropdown Menu */
        .sort-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            min-width: 200px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s ease;
            z-index: 100;
            overflow: hidden;
        }

        .sort-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        body.dark-mode .sort-dropdown-menu {
            background: #1f1f1f;
            border-color: #404040;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Dropdown Items */
        .sort-dropdown-item {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .sort-dropdown-item:last-child {
            border-bottom: none;
        }

        .sort-dropdown-item:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .sort-dropdown-item.active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .sort-dropdown-item i {
            flex-shrink: 0;
            width: 18px;
            text-align: center;
        }

        .sort-dropdown-item span:first-of-type {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .sort-dropdown-item .sort-description {
            width: 100%;
            margin-left: calc(18px + 0.75rem);
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: 400;
            margin-top: -0.25rem;
        }

        body.dark-mode .sort-dropdown-item {
            color: #e5e7eb;
            border-bottom-color: #2a2a2a;
        }

        body.dark-mode .sort-dropdown-item:hover {
            background: #2a2a2a;
            color: #f3f4f6;
        }

        body.dark-mode .sort-dropdown-item.active {
            background: rgba(16, 185, 129, 0.15);
        }

        body.dark-mode .sort-dropdown-item .sort-description {
            color: #6b7280;
        }

        /* Mobile adjustments for sort dropdown */
        @media (max-width: 767.98px) {
            /* Container: flex row, no wrap, items aligned */
            .feed-header-container {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 0.5rem;
                padding: 0 0 0.75rem 0;
            }

            /* Header text: smaller to fit with button */
            .feed-header-container .feed-header {
                font-size: 0.95rem !important;
                white-space: nowrap;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                border-bottom: none !important;
                flex-shrink: 1;
                min-width: 0;
            }

            /* Dropdown wrapper: keep size, position relative for menu */
            .feed-header-container .sort-dropdown-wrapper {
                flex-shrink: 0;
                position: relative;
            }

            /* Dropdown button: compact */
            .feed-header-container .sort-dropdown-btn {
                padding: 0.375rem 0.5rem;
            }

            /* Dropdown menu: positioned to right of button, not off-screen */
            .feed-header-container .sort-dropdown-menu {
                position: absolute;
                top: calc(100% + 0.5rem);
                left: 0;
                right: auto;
                min-width: 200px;
            }

            .sort-dropdown-item {
                padding: 0.875rem 1rem;
                gap: 0.625rem;
            }

            .sort-dropdown-item span:first-of-type {
                font-size: 0.875rem;
            }

            .sort-dropdown-item .sort-description {
                margin-left: calc(18px + 0.625rem);
                font-size: 0.7rem;
            }
        }

        /* Dark mode: sidebar cards */
        body.dark-mode .user-prediction-card {
            background: #2a2a2a !important;
            border-color: #404040 !important;
        }

        body.dark-mode .user-prediction-card:hover {
            background: #2d2d2d !important;
            border-color: #10b981 !important;
        }

        body.dark-mode .user-prediction-card h6 {
            color: #f3f4f6 !important;
        }

        body.dark-mode .user-prediction-card p {
            color: #9ca3af !important;
        }

        /* Dark mode: engagement bar */
        body.dark-mode .prediction-card .border-top {
            border-color: #404040 !important;
        }

        /* Hover effects for vote buttons */
        .vote-btn:hover {
            transform: scale(1.05);
        }

        .upvote-btn:hover {
            background: rgba(16, 185, 129, 0.2) !important;
            border-color: #10b981 !important;
        }

        .downvote-btn:hover {
            background: rgba(239, 68, 68, 0.2) !important;
            border-color: #ef4444 !important;
        }

        .vote-btn.voted-up {
            background: rgba(16, 185, 129, 0.25) !important;
            border-color: #10b981 !important;
        }

        .vote-btn.voted-down {
            background: rgba(239, 68, 68, 0.25) !important;
            border-color: #ef4444 !important;
        }

        /* Dark mode vote buttons */
        body.dark-mode .upvote-btn {
            background: rgba(16, 185, 129, 0.15) !important;
        }

        body.dark-mode .downvote-btn {
            background: rgba(239, 68, 68, 0.15) !important;
        }

        body.dark-mode .upvote-btn:hover {
            background: rgba(16, 185, 129, 0.25) !important;
        }

        body.dark-mode .downvote-btn:hover {
            background: rgba(239, 68, 68, 0.25) !important;
        }

        .user-prediction-card:hover {
            border-color: #10b981 !important;
            background: #ffffff !important;
        }

        body.dark-mode .user-prediction-card:hover {
            background: #333333 !important;
        }

        /* Dark mode: "Ends" text in user prediction cards */
        body.dark-mode .user-prediction-card .border-top {
            border-color: #404040 !important;
        }

        body.dark-mode .user-prediction-card .border-top small.text-muted {
            color: #9ca3af !important;
        }

        /* Prediction card text colors for dark mode */
        body.dark-mode .prediction-card .fw-bold {
            color: #f3f4f6 !important;
        }

        body.dark-mode .prediction-card h5 {
            color: #f3f4f6 !important;
        }

        body.dark-mode .prediction-card .reasoning-text {
            color: #d1d5db !important;
        }

        body.dark-mode .prediction-card small {
            color: #9ca3af !important;
        }

        /* Card text dark mode */
        body.dark-mode .card-body h5,
        body.dark-mode .card-body h6 {
            color: #f3f4f6 !important;
        }

        /* No predictions message */
        body.dark-mode .prediction-card h4 {
            color: #f3f4f6 !important;
        }

        /* Comments toggle button */
        .comments-toggle:hover {
            background: rgba(59, 130, 246, 0.2) !important;
            border-color: #3b82f6 !important;
        }

        body.dark-mode .comments-toggle {
            background: rgba(59, 130, 246, 0.15) !important;
        }

        body.dark-mode .comments-toggle:hover {
            background: rgba(59, 130, 246, 0.25) !important;
        }

        /* ========== REASONING TEXT EXPAND/COLLAPSE - ALL SCREEN SIZES ========== */
        .reasoning-text {
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .reasoning-text.truncated {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .reasoning-text.truncated.expanded {
            -webkit-line-clamp: unset;
            display: block;
            overflow: visible;
        }

        .reasoning-expand-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #3b82f6;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 0.5rem;
            cursor: pointer;
            padding: 0.5rem 0.75rem;
            margin-left: -0.75rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease;
            user-select: none;
        }

        .reasoning-expand-hint:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }

        .reasoning-expand-hint:active {
            background-color: rgba(59, 130, 246, 0.15);
        }

        .reasoning-expand-hint i {
            transition: transform 0.2s ease;
        }

        .reasoning-expand-hint.expanded i {
            transform: rotate(180deg);
        }

        body.dark-mode .reasoning-expand-hint {
            color: #60a5fa;
        }

        body.dark-mode .reasoning-expand-hint:hover {
            background-color: rgba(96, 165, 250, 0.1);
        }

        body.dark-mode .reasoning-expand-hint:active {
            background-color: rgba(96, 165, 250, 0.15);
        }

        /* Comments section styles */
        .comments-section {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 1000px;
            }
        }

        .comment-input {
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }

        .comment-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        body.dark-mode .comment-input {
            background-color: #2a2a2a;
            border-color: #404040;
            color: #e5e7eb;
        }

        body.dark-mode .comment-input:focus {
            border-color: #3b82f6;
            background-color: #2d2d2d;
        }

        body.dark-mode .comment-input::placeholder {
            color: #6b7280;
        }

        /* Individual comment styles */
        .comment-item {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            background: rgba(0, 0, 0, 0.02);
            border-left: 3px solid #e5e7eb;
        }

        .comment-item:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        body.dark-mode .comment-item {
            background: rgba(255, 255, 255, 0.03);
            border-left-color: #404040;
        }

        body.dark-mode .comment-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.9rem;
            color: #111827;
        }

        body.dark-mode .comment-author {
            color: #f3f4f6;
        }

        .comment-content {
            font-size: 0.9rem;
            color: #374151;
            margin-top: 0.25rem;
            line-height: 1.5;
        }

        body.dark-mode .comment-content {
            color: #d1d5db;
        }

        .comment-meta {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .reply-item {
            margin-left: 1.5rem;
            padding-left: 0.75rem;
            border-left: 2px solid rgba(59, 130, 246, 0.3);
        }

        .no-comments-msg {
            text-align: center;
            padding: 1rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .reply-btn {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 0;
        }

        .reply-btn:hover {
            color: #3b82f6;
        }

        .reply-form {
            margin-top: 0.5rem;
            display: none;
        }

        .reply-form.show {
            display: block;
        }

        /* ========== ENHANCED MOBILE-SPECIFIC STYLES (DENSE FEED) ========== */
        @media (max-width: 767.98px) {
            /* Hide welcome header on mobile */
            .animate-fade-in.mb-4 {
                display: none !important;
            }

            /* Hide search bar on mobile - use bottom nav search instead */
            .quick-search-container {
                display: none !important;
            }

            /* Feed header - compact */
            .feed-header:not(.feed-header-container .feed-header) {
                font-size: 1.1rem !important;
                margin-bottom: 1rem !important;
                padding-bottom: 0.75rem;
            }

            /* Prediction cards - DENSE layout (Reddit/Twitter style) */
            .prediction-card {
                margin: 0 0 0.875rem 0 !important;
                width: 100% !important;
                padding: 1rem !important;
                border-radius: 0.875rem !important;
            }

            /* Subtle tap effect instead of hover lift */
            .prediction-card:hover {
                transform: none !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
            }

            .prediction-card:active {
                transform: scale(0.995);
            }

            /* Profile section - HORIZONTAL (no stacking) */
            .prediction-card .d-flex.justify-content-between.align-items-start {
                flex-direction: row !important;
                gap: 0 !important;
            }

            .prediction-card .d-flex.justify-content-between.align-items-start > div:last-child {
                align-self: flex-start;
            }

            /* Smaller profile images (40px) */
            .prediction-card .rounded-circle {
                width: 40px !important;
                height: 40px !important;
                min-width: 40px;
                min-height: 40px;
            }

            .prediction-card .ms-3 {
                margin-left: 0.75rem !important;
            }

            /* Compact user info */
            .prediction-card .fw-bold {
                font-size: 0.9rem !important;
                margin-bottom: 0.125rem !important;
            }

            .prediction-card small.text-muted {
                font-size: 0.75rem !important;
            }

            /* Compact date badge */
            .prediction-card .badge[style*="background-color: rgba(16, 185, 129"] {
                padding: 0.3rem 0.6rem !important;
                font-size: 0.75rem !important;
            }

            /* Stock symbol & company - compact */
            .prediction-card h5 {
                font-size: 1.1rem !important;
                margin-bottom: 0.5rem !important;
            }

            .prediction-card h5 span[style*="color: #6b7280"] {
                font-size: 0.85rem !important;
            }

            /* Compact prediction badges */
            .prediction-badge-vibrant {
                padding: 0.35rem 0.9rem !important;
                font-size: 0.75rem !important;
            }

            /* Target price and badge layout - tighter */
            .prediction-card .d-flex.align-items-center.gap-3 {
                flex-wrap: wrap;
                gap: 0.5rem !important;
            }

            .prediction-card .d-flex.align-items-center.gap-3 span[style*="font-size: 1.15rem"] {
                font-size: 1rem !important;
            }

            .prediction-card .d-flex.align-items-center.gap-3 span[style*="font-size: 0.95rem"] {
                font-size: 0.8rem !important;
            }

            /* Reasoning text - 2 lines with truncation */
            .prediction-card .reasoning-text {
                font-size: 0.875rem !important;
                line-height: 1.5 !important;
                margin-bottom: 0.75rem !important;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            /* Mobile-specific touch enhancements for reasoning */
            .prediction-card .reasoning-text.truncated {
                -webkit-tap-highlight-color: rgba(59, 130, 246, 0.1);
                touch-action: manipulation;
            }

            .reasoning-expand-hint {
                -webkit-tap-highlight-color: rgba(59, 130, 246, 0.2);
                touch-action: manipulation;
            }

            /* Engagement bar - HORIZONTAL (compact) */
            .prediction-card .border-top {
                margin-top: 0.75rem !important;
                padding-top: 0.75rem !important;
            }

            .prediction-card .border-top .d-flex.justify-content-between {
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 0.5rem !important;
            }

            .prediction-card .border-top .d-flex.justify-content-between > div {
                justify-content: flex-start !important;
            }

            /* Compact vote buttons */
            .vote-btn {
                padding: 0.3rem 0.6rem !important;
                font-size: 0.8rem;
                min-height: 36px;
                border-radius: 18px !important;
            }

            .vote-btn i {
                font-size: 0.95rem !important;
            }

            .vote-btn span {
                font-size: 0.8rem !important;
            }

            /* Compact status badge */
            .prediction-card .badge.bg-success,
            .prediction-card .badge.bg-secondary,
            .prediction-card .badge.bg-warning {
                padding: 0.25rem 0.5rem !important;
                font-size: 0.7rem !important;
            }

            /* Comments toggle - compact */
            .comments-toggle {
                min-height: 36px;
                padding: 0.3rem 0.6rem !important;
            }

            .comments-toggle i {
                font-size: 0.95rem !important;
            }

            /* Accuracy display - compact */
            .prediction-card .d-flex.align-items-center.gap-2 i[class*="bi-bullseye"] {
                font-size: 0.9rem !important;
            }

            .prediction-card .d-flex.align-items-center.gap-2 span[style*="font-weight: 700"] {
                font-size: 0.85rem !important;
            }

            /* Comments section - improved spacing */
            .comments-section {
                margin-top: 0.75rem;
            }

            .comments-section .d-flex.gap-2 {
                flex-direction: row;
                gap: 0.5rem !important;
            }

            .comments-section .comment-input {
                flex: 1;
                min-height: 44px;
            }

            .comments-section .submit-comment,
            .comments-section .btn-primary.btn-sm {
                min-width: 44px;
                min-height: 44px;
            }

            /* Comment items - better touch targets */
            .comment-item {
                padding: 0.875rem !important;
                margin-bottom: 0.75rem !important;
                border-radius: 0.75rem;
            }

            .comment-author {
                font-size: 0.9rem;
            }

            .comment-content {
                font-size: 0.9rem;
                line-height: 1.6;
            }

            .reply-item {
                margin-left: 1rem !important;
                padding-left: 0.875rem;
            }

            .reply-btn {
                padding: 0.5rem 0;
                min-height: 44px;
            }

            /* Reply form */
            .reply-form .d-flex.gap-2 {
                flex-direction: row;
            }

            .reply-form .reply-input {
                flex: 1;
                min-height: 40px;
            }

            /* Container padding - breathing room */
            .container.mt-4 {
                margin-top: 1rem !important;
            }

            /* Row padding */
            .row {
                margin-left: -0.5rem;
                margin-right: -0.5rem;
            }

            .row > [class*="col-"] {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }

        /* Small mobile (under 480px) additional tweaks */
        @media (max-width: 479.98px) {
            .animate-fade-in h1 {
                font-size: 1.5rem !important;
            }

            .welcome-subtext {
                font-size: 0.9rem !important;
            }

            /* Search stacks vertically on very small screens */
            .quick-search-container .d-flex {
                flex-direction: column;
            }

            .quick-search-container button[type="submit"] {
                width: 100%;
            }

            /* Prediction cards */
            .prediction-card {
                padding: 1rem !important;
            }

            .prediction-card h5 {
                font-size: 1rem !important;
            }

            .prediction-card .badge {
                font-size: 0.75rem !important;
                padding: 0.35rem 0.7rem !important;
            }

            /* Engagement bar stacks */
            .vote-btn {
                padding: 0.4rem 0.75rem !important;
            }
        }

        /* Tablet optimization (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .prediction-card {
                padding: 1.5rem !important;
            }
        }

        /* Hide sidebar content on mobile - now accessed via bottom nav */
        @media (max-width: 767.98px) {
            .mobile-hide-sidebar {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .mobile-hide-sidebar {
                display: block !important;
            }
        }

        /* ========== MOBILE HOT STOCKS CARD CAROUSEL (NEW) ========== */
        @media (max-width: 767.98px) {
            .mobile-hot-cards-wrapper {
                margin: 0 -0.75rem 1.25rem -0.75rem;
                padding: 0.75rem 0;
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.03) 0%, rgba(59, 130, 246, 0.03) 100%);
                border-bottom: 1px solid #e5e7eb;
            }

            body.dark-mode .mobile-hot-cards-wrapper {
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, rgba(59, 130, 246, 0.06) 100%);
                border-bottom-color: #333;
            }

            .mobile-hot-cards-header {
                padding: 0 1rem 0.5rem;
            }

            .mobile-hot-cards-title {
                font-size: 0.85rem;
                font-weight: 700;
                color: #374151;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            body.dark-mode .mobile-hot-cards-title {
                color: #e5e7eb;
            }

            .mobile-hot-cards-carousel {
                display: flex;
                gap: 0.75rem;
                padding: 0.25rem 1rem 0.5rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                scroll-snap-type: x mandatory;
            }

            .mobile-hot-cards-carousel::-webkit-scrollbar {
                display: none;
            }

            .mobile-hot-card {
                flex-shrink: 0;
                width: 110px;
                height: 130px;
                background: #ffffff;
                border-radius: 0.875rem;
                padding: 0.75rem;
                text-decoration: none;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                border: 1px solid #e5e7eb;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                scroll-snap-align: start;
                transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            }

            .mobile-hot-card:active {
                transform: scale(0.97);
            }

            body.dark-mode .mobile-hot-card {
                background: #2a2a2a;
                border-color: #404040;
            }

            /* Create card styling */
            .mobile-hot-card.create-card {
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
                border: 2px dashed #10b981;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
            }

            .hot-card-icon-create {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.25rem;
            }

            .hot-card-label {
                font-size: 0.7rem;
                font-weight: 600;
                color: #10b981;
                text-align: center;
            }

            /* Bullish/Bearish card accent */
            .mobile-hot-card.bullish {
                border-left: 3px solid #10b981;
            }

            .mobile-hot-card.bearish {
                border-left: 3px solid #ef4444;
            }

            .hot-card-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .hot-card-symbol {
                font-size: 0.85rem;
                font-weight: 800;
                color: #111827;
                letter-spacing: -0.02em;
            }

            body.dark-mode .hot-card-symbol {
                color: #f3f4f6;
            }

            .hot-card-badge-mini {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.65rem;
            }

            .hot-card-badge-mini.badge-bullish {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
            }

            .hot-card-badge-mini.badge-bearish {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
            }

            .hot-card-price {
                font-size: 1.1rem;
                font-weight: 700;
                color: #10b981;
                text-align: center;
                margin: 0.25rem 0;
            }

            .mobile-hot-card.bearish .hot-card-price {
                color: #ef4444;
            }

            .hot-card-user-info {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.125rem;
            }

            .hot-card-name {
                font-size: 0.7rem;
                font-weight: 600;
                color: #374151;
                max-width: 90px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            body.dark-mode .hot-card-name {
                color: #e5e7eb;
            }

            .hot-card-score {
                font-size: 0.6rem;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 0.2rem;
            }

            .hot-card-score i {
                color: #f59e0b;
                font-size: 0.55rem;
            }

            .hot-card-engagement {
                display: flex;
                justify-content: center;
                margin-top: 0.25rem;
            }

            .hot-card-votes-up {
                font-size: 0.6rem;
                color: #10b981;
                display: flex;
                align-items: center;
                gap: 0.2rem;
            }
        }

        /* Hide new card carousel on desktop */
        @media (min-width: 768px) {
            .mobile-hot-cards-wrapper {
                display: none !important;
            }
        }

        /* ========== MOBILE HOT PREDICTIONS CAROUSEL (OLD - HIDDEN) ========== */
        @media (max-width: 767.98px) {
            /* Old carousel styles - hidden by default */
            .mobile-hot-carousel-wrapper {
                display: none !important;
            }

            body.dark-mode .mobile-hot-carousel-wrapper {
                border-bottom-color: #404040;
            }

            .mobile-hot-carousel {
                display: flex;
                gap: 0.875rem;
                padding: 0.875rem 0;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
            }

            .mobile-hot-carousel::-webkit-scrollbar {
                display: none;
            }

            .hot-prediction-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.375rem;
                text-decoration: none;
                flex-shrink: 0;
                min-width: 68px;
            }

            .hot-avatar-wrapper {
                width: 64px;
                height: 64px;
                border-radius: 50%;
                padding: 3px;
                background: #e5e7eb;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .hot-prediction-item:active .hot-avatar-wrapper {
                transform: scale(0.95);
            }

            /* Solid green ring for bullish */
            .hot-avatar-wrapper.ring-bullish {
                background: #10b981;
                box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            }

            /* Solid red ring for bearish */
            .hot-avatar-wrapper.ring-bearish {
                background: #ef4444;
                box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
            }

            /* Create new button style */
            .hot-avatar-wrapper.create-new {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            }

            .hot-avatar {
                width: 100%;
                height: 100%;
                border-radius: 50%;
                overflow: hidden;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            body.dark-mode .hot-avatar {
                background: #1f1f1f;
            }

            /* Symbol displayed inside the circle */
            .hot-avatar.symbol-avatar {
                background: #f8fafc;
            }

            body.dark-mode .hot-avatar.symbol-avatar {
                background: #1f1f1f;
            }

            .hot-symbol-text {
                font-size: 0.75rem;
                font-weight: 700;
                color: #1f2937;
                letter-spacing: -0.02em;
                text-transform: uppercase;
            }

            body.dark-mode .hot-symbol-text {
                color: #f3f4f6;
            }

            /* Bullish symbol styling */
            .ring-bullish .hot-symbol-text {
                color: #059669;
            }

            body.dark-mode .ring-bullish .hot-symbol-text {
                color: #34d399;
            }

            /* Bearish symbol styling */
            .ring-bearish .hot-symbol-text {
                color: #dc2626;
            }

            body.dark-mode .ring-bearish .hot-symbol-text {
                color: #f87171;
            }

            .hot-avatar.create-avatar {
                background: transparent;
                color: white;
                font-size: 1.5rem;
            }

            /* User info below the circle */
            .hot-user-info {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.125rem;
                max-width: 68px;
            }

            .hot-user-name {
                font-size: 0.7rem;
                font-weight: 600;
                color: #374151;
                max-width: 68px;
                text-align: center;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                line-height: 1.2;
            }

            body.dark-mode .hot-user-name {
                color: #e5e7eb;
            }

            .hot-user-score {
                display: flex;
                align-items: center;
                gap: 0.2rem;
                font-size: 0.625rem;
                font-weight: 500;
                color: #6b7280;
            }

            .hot-user-score i {
                font-size: 0.5rem;
                color: #f59e0b;
            }

            body.dark-mode .hot-user-score {
                color: #9ca3af;
            }

            /* ========== STORY VIEWER STYLES ========== */
            .story-viewer {
                position: fixed;
                inset: 0;
                z-index: 9999;
                background: #000;
                display: flex;
                flex-direction: column;
                touch-action: pan-y;
            }

            /* Progress Bars */
            .story-progress-container {
                display: flex;
                gap: 4px;
                padding: 12px 12px 8px;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                z-index: 10;
            }

            .story-progress-bar {
                flex: 1;
                height: 3px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 2px;
                overflow: hidden;
            }

            .story-progress-fill {
                height: 100%;
                background: #fff;
                width: 0;
                transition: width 0.1s linear;
            }

            .story-progress-bar.completed .story-progress-fill {
                width: 100%;
            }

            .story-progress-bar.active .story-progress-fill {
                animation: story-progress 8s linear forwards;
            }

            @keyframes story-progress {
                from { width: 0; }
                to { width: 100%; }
            }

            /* Header */
            .story-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 48px 16px 12px;
                position: relative;
                z-index: 10;
            }

            .story-user-link {
                display: flex;
                align-items: center;
                gap: 10px;
                text-decoration: none;
                color: #fff;
            }

            .story-user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #10b981;
            }

            .story-user-info {
                display: flex;
                flex-direction: column;
            }

            .story-user-name {
                font-weight: 600;
                font-size: 0.95rem;
                color: #fff;
            }

            .story-user-rep {
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.7);
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .story-user-rep i {
                color: #f59e0b;
                font-size: 0.65rem;
            }

            .story-close-btn {
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.1);
                border: none;
                border-radius: 50%;
                color: #fff;
                font-size: 1.25rem;
                cursor: pointer;
                transition: background 0.2s;
            }

            .story-close-btn:hover,
            .story-close-btn:active {
                background: rgba(255, 255, 255, 0.2);
            }

            /* Content Area */
            .story-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 24px;
                gap: 20px;
                transition: transform 0.3s ease-out, opacity 0.3s ease-out;
            }

            .story-content.slide-left {
                transform: translateX(-100%);
                opacity: 0;
            }

            .story-content.slide-right {
                transform: translateX(100%);
                opacity: 0;
            }

            .story-stock-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .story-stock-symbol {
                font-size: 2rem;
                font-weight: 800;
                color: #fff;
                font-family: ui-monospace, SFMono-Regular, monospace;
            }

            .story-prediction-badge {
                padding: 6px 16px;
                border-radius: 9999px;
                font-size: 0.875rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .story-prediction-badge.bullish {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: #fff;
            }

            .story-prediction-badge.bearish {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: #fff;
            }

            .story-price-info {
                display: flex;
                gap: 24px;
            }

            .story-target {
                display: flex;
                flex-direction: column;
            }

            .story-target .label {
                font-size: 0.75rem;
                color: rgba(255, 255, 255, 0.6);
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .story-target .value {
                font-size: 1.75rem;
                font-weight: 700;
                color: #10b981;
            }

            .story-reasoning {
                font-size: 1rem;
                line-height: 1.7;
                color: rgba(255, 255, 255, 0.9);
                max-height: 200px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .story-votes {
                display: flex;
                gap: 20px;
            }

            .story-upvotes, .story-downvotes {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 1rem;
                font-weight: 600;
            }

            .story-upvotes {
                color: #10b981;
            }

            .story-downvotes {
                color: #ef4444;
            }

            .story-end-date {
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.6);
                display: flex;
                align-items: center;
                gap: 6px;
            }

            /* Navigation Touch Zones */
            .story-nav-prev, .story-nav-next {
                position: absolute;
                top: 100px;
                bottom: 100px;
                width: 30%;
                z-index: 5;
                cursor: pointer;
            }

            .story-nav-prev {
                left: 0;
            }

            .story-nav-next {
                right: 0;
            }

            /* Accessible Navigation Buttons */
            .story-btn-prev, .story-btn-next {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                border: none;
                color: #fff;
                font-size: 1.25rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.3s;
                z-index: 6;
            }

            .story-viewer:hover .story-btn-prev,
            .story-viewer:hover .story-btn-next,
            .story-btn-prev:focus,
            .story-btn-next:focus {
                opacity: 1;
            }

            .story-btn-prev {
                left: 12px;
            }

            .story-btn-next {
                right: 12px;
            }

            .story-btn-prev:disabled,
            .story-btn-next:disabled {
                opacity: 0.3 !important;
                cursor: not-allowed;
            }
        }

        @media (min-width: 768px) {
            .mobile-hot-carousel-wrapper {
                display: none !important;
            }

            /* Hide story viewer on desktop */
            .story-viewer {
                display: none !important;
            }
        }

        /* ========== DESKTOP HOT POSTS CAROUSEL ========== */
        .desktop-hot-carousel-container {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            margin-top: 0;
        }

        body.dark-mode .desktop-hot-carousel-container {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(59, 130, 246, 0.08) 100%);
            border-color: #404040;
        }

        .hot-carousel-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        body.dark-mode .hot-carousel-title {
            color: #e5e7eb;
        }

        .hot-carousel-pause-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .hot-carousel-pause-btn:hover {
            background: rgba(107, 114, 128, 0.2);
            color: #374151;
        }

        .hot-carousel-pause-btn.paused {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        body.dark-mode .hot-carousel-pause-btn {
            background: rgba(156, 163, 175, 0.15);
            color: #9ca3af;
        }

        body.dark-mode .hot-carousel-pause-btn:hover {
            background: rgba(156, 163, 175, 0.25);
            color: #e5e7eb;
        }

        .desktop-hot-carousel {
            position: relative;
            overflow: hidden;
            height: 140px;
            margin: 0 -0.5rem;
        }

        .hot-carousel-track {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 0.5rem;
        }

        .hot-carousel-card {
            flex-shrink: 0;
            width: 100px;
            height: 110px;
            background: #ffffff;
            border-radius: 0.75rem;
            padding: 0.75rem;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(0.85);
            opacity: 0.6;
            cursor: pointer;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        body.dark-mode .hot-carousel-card {
            background: #2a2a2a;
            border-color: #404040;
        }

        /* Cards at far edges (positions 0 and 4) - smallest */
        .hot-carousel-card[data-visible-position="0"],
        .hot-carousel-card[data-visible-position="4"] {
            transform: scale(0.75);
            opacity: 0.4;
        }

        /* Cards next to center (positions 1 and 3) - medium */
        .hot-carousel-card[data-visible-position="1"],
        .hot-carousel-card[data-visible-position="3"] {
            transform: scale(0.88);
            opacity: 0.7;
        }

        /* Center card (position 2) - largest and most prominent */
        .hot-carousel-card[data-visible-position="2"] {
            transform: scale(1.05);
            opacity: 1;
            z-index: 10;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }

        body.dark-mode .hot-carousel-card[data-visible-position="2"] {
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
        }

        .hot-carousel-card:hover {
            transform: scale(1.1) !important;
            opacity: 1 !important;
            z-index: 20;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
        }

        .hot-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hot-card-symbol {
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hot-card-symbol.bullish {
            color: #10b981;
        }

        .hot-card-symbol.bearish {
            color: #ef4444;
        }

        .hot-card-badge {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
        }

        .hot-card-badge.badge-bullish {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .hot-card-badge.badge-bearish {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .hot-card-price {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin: 0.25rem 0;
        }

        body.dark-mode .hot-card-price {
            color: #f3f4f6;
        }

        .hot-card-user {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.125rem;
        }

        .hot-card-username {
            font-size: 0.65rem;
            font-weight: 600;
            color: #374151;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        body.dark-mode .hot-card-username {
            color: #e5e7eb;
        }

        .hot-card-rep {
            font-size: 0.55rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .hot-card-rep i {
            color: #f59e0b;
            font-size: 0.5rem;
        }

        body.dark-mode .hot-card-rep {
            color: #9ca3af;
        }

        .hot-card-votes {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.55rem;
            margin-top: 0.25rem;
        }

        .hot-card-upvotes {
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 0.15rem;
        }

        .hot-card-downvotes {
            color: #ef4444;
            display: flex;
            align-items: center;
            gap: 0.15rem;
        }

        .hot-card-upvotes i,
        .hot-card-downvotes i {
            font-size: 0.5rem;
        }

        /* Carousel indicators */
        .hot-carousel-indicators {
            display: flex;
            justify-content: center;
            gap: 0.375rem;
            margin-top: 0.75rem;
        }

        .hot-carousel-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            border: none;
            background: #d1d5db;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
        }

        .hot-carousel-dot:hover {
            background: #9ca3af;
            transform: scale(1.2);
        }

        .hot-carousel-dot.active {
            background: #10b981;
            width: 18px;
            border-radius: 3px;
        }

        body.dark-mode .hot-carousel-dot {
            background: #4b5563;
        }

        body.dark-mode .hot-carousel-dot:hover {
            background: #6b7280;
        }

        body.dark-mode .hot-carousel-dot.active {
            background: #10b981;
        }

        /* Handling for 3 visible cards */
        .hot-carousel-card[data-visible-position="1"].cards-3 {
            transform: scale(1.05);
            opacity: 1;
            z-index: 10;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);
            border-color: #10b981;
        }

        /* Hide on mobile */
        @media (max-width: 767.98px) {
            .desktop-hot-carousel-container {
                display: none !important;
            }
        }
        </style>

        {{-- Feed Header with Inline Sort Dropdown --}}
        @php
            $sortConfig = [
                'trending' => ['icon' => 'bi-fire', 'color' => '#ef4444', 'label' => 'Trending'],
                'recent' => ['icon' => 'bi-clock-fill', 'color' => '#3b82f6', 'label' => 'Recent'],
                'controversial' => ['icon' => 'bi-arrow-left-right', 'color' => '#f59e0b', 'label' => 'Controversial'],
            ];
            $currentSort = $sortConfig[$sort ?? 'trending'];
        @endphp
        <div class="feed-header-inline mb-3">
            <div class="sort-dropdown-wrapper">
                <button class="sort-dropdown-btn-inline" id="sortDropdownBtn" aria-expanded="false" aria-haspopup="true">
                    <i class="{{ $currentSort['icon'] }}" style="color: {{ $currentSort['color'] }};"></i>
                    <span class="sort-label-text">{{ $currentSort['label'] }} Predictions</span>
                    <i class="bi bi-chevron-down chevron-icon"></i>
                </button>
                <div class="sort-dropdown-menu" id="sortDropdownMenu" role="menu">
                    <a href="{{ url('home?sort=trending') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'trending' ? 'active' : '' }}" role="menuitem">
                        <i class="bi bi-fire" style="font-size: 1.125rem; color: #ef4444;"></i>
                        <span>Trending</span>
                        <span class="sort-description">Popular right now</span>
                    </a>
                    <a href="{{ url('home?sort=recent') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'recent' ? 'active' : '' }}" role="menuitem">
                        <i class="bi bi-clock-fill" style="font-size: 1.125rem; color: #3b82f6;"></i>
                        <span>Recent</span>
                        <span class="sort-description">Newest first</span>
                    </a>
                    <a href="{{ url('home?sort=controversial') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'controversial' ? 'active' : '' }}" role="menuitem">
                        <i class="bi bi-arrow-left-right" style="font-size: 1.125rem; color: #f59e0b;"></i>
                        <span>Controversial</span>
                        <span class="sort-description">Most debated</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Predictions Feed --}}
        @if(empty($predictions))
            <div class="prediction-card text-center" style="padding: 3rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                <h4 style="margin-bottom: 0.5rem;">No predictions yet</h4>
                <p class="text-muted">Be the first to make a prediction!</p>
            </div>
        @else
            @foreach($predictions as $index => $prediction)
                <div class="prediction-card" data-prediction-id="{{ $prediction->prediction_id }}">

                    @php
                        $profilePicture = $prediction->user->profile_picture
                            ? asset('images/profile_pictures/' . $prediction->user->profile_picture)
                            : asset('images/default.png');
                    @endphp

                    {{--  Top section: Profile on left, dates on right --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <img src="{{ $profilePicture }}" alt="{{ $prediction->user->first_name }}"
                                 class="rounded-circle" width="48" height="48"
                                 style="object-fit: cover; border: 2px solid #10b981;">
                            <div class="ms-3">
                                <div class="fw-bold" style="font-size: 1rem; margin-bottom: 0.25rem;">
                                    {{ $prediction->user->first_name }}
                                </div>
                                <small class="text-muted d-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span>{{ $prediction->user->reputation_score }} pts</span>
                                    <span class="mx-1">•</span>
                                    <span>{{ date('M j, Y', strtotime($prediction->prediction_date)) }}</span>
                                </small>
                            </div>
                        </div>

                        {{--  End Date Badge --}}
                        <div>
                            <span class="badge" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                <i class="bi bi-clock"></i> Ends {{ date('M j', strtotime($prediction->end_date)) }}
                            </span>
                        </div>
                    </div>

                {{--  Company + Reasoning --}}
                @if(!empty($prediction->reasoning))
                    @if(!empty($prediction->stock->company_name))
                        <div class="mb-3">
                            <h5 class="mb-2" style="font-size: 1.25rem; font-weight: 700;">
                                <span class="text-primary">{{ $prediction->stock->symbol }}</span>
                                <span style="color: #6b7280; font-weight: 500; font-size: 1rem;">{{ $prediction->stock->company_name }}</span>
                            </h5>
                        </div>
                    @endif

                    {{--  Prediction Type & Target Price --}}
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge prediction-badge-vibrant {{ $prediction->prediction_type == 'Bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                            <i class="bi bi-{{ $prediction->prediction_type == 'Bullish' ? 'arrow-up' : 'arrow-down' }}-circle-fill me-1"></i>
                            {{ $prediction->prediction_type }}
                        </span>
                        @if(!empty($prediction->target_price))
                            <div class="d-flex align-items-center gap-2">
                                <span style="color: #6b7280; font-size: 0.95rem;">Target Price:</span>
                                <span style="color: #10b981; font-weight: 700; font-size: 1.15rem;">${{ number_format($prediction->target_price, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <style>
                    .prediction-badge-vibrant {
                        padding: 0.5rem 1.2rem;
                        font-size: 0.875rem;
                        font-weight: 700;
                        border-radius: 9999px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
                        animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    }
                    .badge-bullish {
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                    }
                    .badge-bearish {
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        color: white;
                    }
                    @keyframes bounceIn {
                        0% {
                            transform: scale(0.3);
                            opacity: 0;
                        }
                        50% {
                            transform: scale(1.05);
                        }
                        70% {
                            transform: scale(0.9);
                        }
                        100% {
                            transform: scale(1);
                            opacity: 1;
                        }
                    }
                    </style>

                    {{--  Reasoning text with mobile expand/collapse --}}
                    <div class="reasoning-wrapper">
                        <p class="reasoning-text mb-3 truncated" style="line-height: 1.6;" data-full-text="{{ $prediction->reasoning }}">{{ $prediction->reasoning }}</p>
                        <div class="reasoning-expand-hint" style="display: none;">
                            <span class="expand-text">Show more</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </div>
                @endif

                {{--  Engagement Bar: Votes & Stats --}}
                <div class="border-top pt-3 mt-3" style="border-color: #e5e7eb !important;">
                    <div class="d-flex justify-content-between align-items-center">
                        {{-- Left: Voting --}}
                        <div class="d-flex align-items-center gap-2">
                            {{-- Upvotes --}}
                            <button class="btn btn-sm vote-btn upvote-btn d-flex align-items-center gap-2"
                                    data-id="{{ $prediction->prediction_id }}"
                                    data-action="upvote"
                                    style="background: rgba(16, 185, 129, 0.1); border: 1px solid transparent; border-radius: 20px; color: #10b981; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                                <i class="bi bi-arrow-up-circle-fill" style="font-size: 1.1rem;"></i>
                                <span id="upvotes-{{ $prediction->prediction_id }}" class="fw-bold" style="font-size: 0.85rem;">
                                    {{ $prediction->upvotes ?? 0 }}
                                </span>
                            </button>

                            {{-- Downvotes --}}
                            <button class="btn btn-sm vote-btn downvote-btn d-flex align-items-center gap-2"
                                    data-id="{{ $prediction->prediction_id }}"
                                    data-action="downvote"
                                    style="background: rgba(239, 68, 68, 0.1); border: 1px solid transparent; border-radius: 20px; color: #ef4444; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                                <i class="bi bi-arrow-down-circle-fill" style="font-size: 1.1rem;"></i>
                                <span id="downvotes-{{ $prediction->prediction_id }}" class="fw-bold" style="font-size: 0.85rem;">
                                    {{ $prediction->downvotes ?? 0 }}
                                </span>
                            </button>

                            {{-- Status --}}
                            @php
                                $statusClass = 'bg-secondary';
                                $statusText = 'Inactive';
                                $statusIcon = 'pause-circle';
                                if ($prediction->is_active == 1) {
                                    if (strtotime($prediction->end_date) > time()) {
                                        $statusClass = 'bg-success';
                                        $statusText = 'Active';
                                        $statusIcon = 'play-circle-fill';
                                    } else {
                                        $statusClass = 'bg-warning text-dark';
                                        $statusText = 'Expired';
                                        $statusIcon = 'clock-history';
                                    }
                                }
                            @endphp
                            <span class="badge {{ $statusClass }}" style="padding: 0.4rem 0.8rem;">
                                <i class="bi bi-{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                        </div>

                        {{-- Right: Accuracy & Comments Toggle --}}
                        <div class="d-flex align-items-center gap-3">
                            @if(isset($prediction->accuracy) && $prediction->accuracy !== null)
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-bullseye" style="color: #10b981; font-size: 1.1rem;"></i>
                                    <span style="font-weight: 700; font-size: 1rem;">{{ number_format($prediction->accuracy, 1) }}%</span>
                                    <span class="text-muted" style="font-size: 0.85rem;">accuracy</span>
                                </div>
                            @endif

                            {{-- Comments Toggle Button --}}
                            <button class="btn btn-sm comments-toggle d-flex align-items-center gap-2"
                                    data-prediction-id="{{ $prediction->prediction_id }}"
                                    style="background: rgba(59, 130, 246, 0.1); border: 1px solid transparent; border-radius: 20px; color: #3b82f6; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                                <i class="bi bi-chat-dots" style="font-size: 1.1rem;"></i>
                                <span class="comment-count fw-bold" style="font-size: 0.85rem;">
                                    {{ $prediction->comments_count ?? 0 }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Expandable Comments Section --}}
                <div class="comments-section" id="comments-{{ $prediction->prediction_id }}" style="display: none;">
                    <div class="border-top pt-3 mt-3" style="border-color: #e5e7eb !important;">
                        {{-- Comment Form --}}
                        @auth
                        <div class="comment-form mb-3">
                            <div class="d-flex gap-2">
                                <input type="text"
                                       class="form-control comment-input"
                                       placeholder="Add a comment..."
                                       data-prediction-id="{{ $prediction->prediction_id }}"
                                       style="border-radius: 20px; padding: 0.5rem 1rem; font-size: 0.9rem;">
                                <button class="btn btn-primary btn-sm submit-comment"
                                        data-prediction-id="{{ $prediction->prediction_id }}"
                                        style="border-radius: 20px; padding: 0.5rem 1rem;">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="text-muted mb-3" style="font-size: 0.9rem;">
                            <a href="{{ route('login') }}">Log in</a> to join the discussion.
                        </div>
                        @endauth

                        {{-- Comments List --}}
                        <div class="comments-list" id="comments-list-{{ $prediction->prediction_id }}">
                            <div class="text-center py-3 loading-comments">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2 text-muted">Loading comments...</span>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

            @endforeach

            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-4">
                {{ $predictions->links() }}
            </div>
        @endif
    </div>

    {{-- Right Sidebar --}}
    <div class="col-lg-3 col-md-12 order-3 mobile-hide-sidebar">
        <div class="sticky-top" style="top: 1rem;">
            {{-- Create Prediction CTA --}}
            <a href="{{ route('predictions.create') }}" class="btn btn-primary w-100 mb-4" style="padding: 1rem; border-radius: 0.75rem; font-weight: 600; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="bi bi-plus-circle-fill" style="font-size: 1.2rem;"></i>
                Create New Prediction
            </a>

            {{-- My Predictions Section Header --}}
            <h2 class="feed-header">
                <i class="bi bi-person-fill me-2" style="color: #3b82f6;"></i>
                My Predictions
            </h2>

            {{-- My Predictions List --}}
            @auth
                @if($Userpredictions->count() > 0)
                    @foreach($Userpredictions as $index => $prediction)
                        <a href="{{ route('predictions.view', ['id' => $prediction->prediction_id]) }}" class="text-decoration-none">
                            <div class="user-prediction-card mb-3" style="padding: 1rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #f9fafb; transition: all 0.2s;">
                                <div class="d-flex align-items-start">
                                    @if($prediction->isUnread())
                                        <div class="unread-indicator" title="New prediction"></div>
                                    @endif
                                    <div style="flex-grow: 1;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1" style="font-weight: 700; font-size: 0.95rem;">{{ $prediction->stock->symbol }}</h6>
                                                <p class="mb-1 text-muted" style="font-size: 0.85rem;">{{ $prediction->stock->company_name }}</p>
                                                <div class="d-flex align-items-center gap-2 mt-2">
                                                    <span class="badge {{ $prediction->prediction_type == 'Bullish' ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.75rem;">
                                                        {{ $prediction->prediction_type }}
                                                    </span>
                                                    <span style="font-weight: 700; font-size: 0.9rem; color: #10b981;">${{ number_format($prediction->target_price, 2) }}</span>
                                                </div>
                                            </div>
                                            {{-- Status Badge --}}
                                            @php
                                                $isActive = $prediction->is_active == 1 && strtotime($prediction->end_date) > time();
                                                $isExpired = $prediction->is_active == 1 && strtotime($prediction->end_date) <= time();
                                            @endphp
                                            <span class="badge {{ $isActive ? 'bg-success' : ($isExpired ? 'bg-warning text-dark' : 'bg-secondary') }}" style="font-size: 0.7rem;">
                                                {{ $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') }}
                                            </span>
                                        </div>
                                        <div class="mt-2 pt-2 border-top" style="border-color: #e5e7eb !important;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> Ends {{ \Carbon\Carbon::parse($prediction->end_date)->format('M j, Y') }}
                                                </small>
                                                <div class="d-flex align-items-center gap-2">
                                                    <small class="text-success"><i class="bi bi-arrow-up"></i> {{ $prediction->upvotes ?? 0 }}</small>
                                                    <small class="text-danger"><i class="bi bi-arrow-down"></i> {{ $prediction->downvotes ?? 0 }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach

                    {{-- View All Link --}}
                    <a href="{{ route('user.account') }}" class="btn btn-outline-primary w-100 mt-2" style="border-radius: 0.75rem;">
                        View All My Predictions
                    </a>
                @else
                    <div class="user-prediction-card text-center" style="padding: 2rem 1rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #f9fafb;">
                        <i class="bi bi-graph-up-arrow" style="font-size: 2.5rem; color: #9ca3af; display: block; margin-bottom: 0.75rem;"></i>
                        <p class="text-muted mb-3" style="font-size: 0.95rem;">You haven't made any predictions yet</p>
                        <a href="{{ route('predictions.create') }}" class="btn btn-primary btn-sm" style="border-radius: 0.5rem;">
                            Make Your First Prediction
                        </a>
                    </div>
                @endif
            @else
                <div class="user-prediction-card text-center" style="padding: 2rem 1rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #f9fafb;">
                    <i class="bi bi-person-circle" style="font-size: 2.5rem; color: #9ca3af; display: block; margin-bottom: 0.75rem;"></i>
                    <p class="text-muted mb-3" style="font-size: 0.95rem;">Log in to see your predictions</p>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm" style="border-radius: 0.5rem;">
                        Log In
                    </a>
                </div>
            @endauth
        </div>
    </div>
</div>
</div>

            @push('scripts')
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    // Sort Dropdown functionality
                    const sortBtn = document.getElementById('sortDropdownBtn');
                    const sortMenu = document.getElementById('sortDropdownMenu');

                    if (sortBtn && sortMenu) {
                        // Toggle dropdown on button click
                        sortBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const isOpen = sortMenu.classList.contains('show');

                            if (isOpen) {
                                sortMenu.classList.remove('show');
                                sortBtn.classList.remove('open');
                                sortBtn.setAttribute('aria-expanded', 'false');
                            } else {
                                sortMenu.classList.add('show');
                                sortBtn.classList.add('open');
                                sortBtn.setAttribute('aria-expanded', 'true');
                            }
                        });

                        // Close dropdown when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!sortBtn.contains(e.target) && !sortMenu.contains(e.target)) {
                                sortMenu.classList.remove('show');
                                sortBtn.classList.remove('open');
                                sortBtn.setAttribute('aria-expanded', 'false');
                            }
                        });

                        // Close dropdown on escape key
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape' && sortMenu.classList.contains('show')) {
                                sortMenu.classList.remove('show');
                                sortBtn.classList.remove('open');
                                sortBtn.setAttribute('aria-expanded', 'false');
                                sortBtn.focus();
                            }
                        });

                        // Keyboard navigation within dropdown
                        sortMenu.addEventListener('keydown', function(e) {
                            const items = sortMenu.querySelectorAll('.sort-dropdown-item');
                            const currentIndex = Array.from(items).indexOf(document.activeElement);

                            if (e.key === 'ArrowDown') {
                                e.preventDefault();
                                const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                                items[nextIndex].focus();
                            } else if (e.key === 'ArrowUp') {
                                e.preventDefault();
                                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                                items[prevIndex].focus();
                            }
                        });
                    }

                    const voteButtons = document.querySelectorAll(".vote-btn");

                        voteButtons.forEach(button => {
                            button.addEventListener("click", function (event) {
                                event.preventDefault(); // Prevent link navigation
                                event.stopPropagation(); // Stop event from bubbling to parent link

                                const predictionId = this.getAttribute('data-id');
                                const voteType = this.getAttribute('data-action');

                                fetch('/predictions/vote/' + predictionId, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: new URLSearchParams({
                                        vote_type: voteType,
                                        prediction_id: predictionId // ✅ Ensures Laravel gets it
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        updateVoteCount(predictionId);
                                        toggleVoteStyle(button, voteType);
                                    } else {
                                        alert(data.message || "Something went wrong.");
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    alert("Error submitting vote.");
                                });
                            });
                        });

                        function updateVoteCount(predictionId) {
                            fetch(`/predictions/${predictionId}/vote-counts`)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        document.getElementById(`upvotes-${predictionId}`).textContent = data.upvotes;
                                        document.getElementById(`downvotes-${predictionId}`).textContent = data.downvotes;
                                    }
                                });
                        }
                        function toggleVoteStyle(button, voteType){
                            const voteClass = voteType === 'upvote' ? 'voted-up' : 'voted-down';
                            if (button.classList.contains(voteClass)) {
                                button.classList.remove(voteClass);
                            } else {
                                button.classList.add(voteClass);
                            }
                        }

                    // Comments functionality
                    const loadedComments = new Set();

                    // Toggle comments section
                    document.querySelectorAll('.comments-toggle').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            const predictionId = this.getAttribute('data-prediction-id');
                            const commentsSection = document.getElementById('comments-' + predictionId);

                            if (commentsSection.style.display === 'none') {
                                commentsSection.style.display = 'block';
                                if (!loadedComments.has(predictionId)) {
                                    loadComments(predictionId);
                                    loadedComments.add(predictionId);
                                }
                            } else {
                                commentsSection.style.display = 'none';
                            }
                        });
                    });

                    // Load comments for a prediction
                    function loadComments(predictionId) {
                        const commentsList = document.getElementById('comments-list-' + predictionId);

                        fetch('/predictions/' + predictionId + '/comments')
                            .then(res => res.json())
                            .then(data => {
                                if (data.success && data.data.length > 0) {
                                    commentsList.innerHTML = data.data.map(comment => renderComment(comment, predictionId)).join('');
                                } else {
                                    commentsList.innerHTML = '<div class="no-comments-msg"><i class="bi bi-chat-square-text me-2"></i>No comments yet. Be the first to share your thoughts!</div>';
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                commentsList.innerHTML = '<div class="no-comments-msg text-danger">Error loading comments</div>';
                            });
                    }

                    // Render a single comment with replies
                    function renderComment(comment, predictionId, isReply = false) {
                        const replyClass = isReply ? 'reply-item' : '';
                        let html = `
                            <div class="comment-item ${replyClass}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="comment-author">${escapeHtml(comment.user.name)}</span>
                                        <span class="comment-meta ms-2">
                                            <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                                            ${comment.user.reputation_score} pts
                                        </span>
                                    </div>
                                    <span class="comment-meta">${comment.created_at}</span>
                                </div>
                                <div class="comment-content">${escapeHtml(comment.content)}</div>
                                <div class="mt-2">
                                    <button class="reply-btn" onclick="toggleReplyForm(${comment.comment_id}, ${predictionId})">
                                        <i class="bi bi-reply me-1"></i>Reply
                                    </button>
                                </div>
                                <div class="reply-form" id="reply-form-${comment.comment_id}">
                                    <div class="d-flex gap-2 mt-2">
                                        <input type="text" class="form-control comment-input reply-input"
                                               placeholder="Write a reply..."
                                               id="reply-input-${comment.comment_id}"
                                               onkeypress="if(event.key === 'Enter') { event.preventDefault(); submitReply(${comment.comment_id}, ${predictionId}); }"
                                               style="border-radius: 20px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                        <button class="btn btn-primary btn-sm" onclick="submitReply(${comment.comment_id}, ${predictionId})" style="border-radius: 20px;">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </div>
                                </div>
                        `;

                        // Render replies
                        if (comment.replies && comment.replies.length > 0) {
                            html += '<div class="mt-2">';
                            comment.replies.forEach(reply => {
                                html += renderComment(reply, predictionId, true);
                            });
                            html += '</div>';
                        }

                        html += '</div>';
                        return html;
                    }

                    // Submit new comment
                    document.querySelectorAll('.submit-comment').forEach(button => {
                        button.addEventListener('click', function(e) {
                            e.preventDefault();
                            const predictionId = this.getAttribute('data-prediction-id');
                            const input = document.querySelector(`.comment-input[data-prediction-id="${predictionId}"]`);
                            const content = input.value.trim();

                            if (!content) return;

                            submitComment(predictionId, content, null, input);
                        });
                    });

                    // Handle enter key for comment input
                    document.querySelectorAll('.comment-input[data-prediction-id]').forEach(input => {
                        input.addEventListener('keypress', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                const predictionId = this.getAttribute('data-prediction-id');
                                const content = this.value.trim();

                                if (!content) return;

                                submitComment(predictionId, content, null, this);
                            }
                        });
                    });

                    function submitComment(predictionId, content, parentId, inputElement) {
                        const formData = new FormData();
                        formData.append('prediction_id', predictionId);
                        formData.append('content', content);
                        if (parentId) {
                            formData.append('parent_comment_id', parentId);
                        }

                        fetch('/comments', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                inputElement.value = '';
                                loadedComments.delete(predictionId);
                                loadComments(predictionId);

                                // Update comment count
                                const countSpan = document.querySelector(`.comments-toggle[data-prediction-id="${predictionId}"] .comment-count`);
                                if (countSpan) {
                                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                                }
                            } else {
                                alert(data.message || 'Error posting comment');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Error posting comment');
                        });
                    }

                    // Escape HTML to prevent XSS
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }
                    });

                    // Global functions for reply handling
                    function toggleReplyForm(commentId, predictionId) {
                        const replyForm = document.getElementById('reply-form-' + commentId);
                        if (replyForm) {
                            replyForm.classList.toggle('show');
                            if (replyForm.classList.contains('show')) {
                                document.getElementById('reply-input-' + commentId).focus();
                            }
                        }
                    }

                    function submitReply(parentId, predictionId) {
                        const input = document.getElementById('reply-input-' + parentId);
                        const content = input.value.trim();

                        if (!content) return;

                        const formData = new FormData();
                        formData.append('prediction_id', predictionId);
                        formData.append('content', content);
                        formData.append('parent_comment_id', parentId);

                        fetch('/comments', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                input.value = '';
                                document.getElementById('reply-form-' + parentId).classList.remove('show');
                                // Reload comments
                                const commentsList = document.getElementById('comments-list-' + predictionId);
                                fetch('/predictions/' + predictionId + '/comments')
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success && data.data.length > 0) {
                                            commentsList.innerHTML = data.data.map(comment => renderCommentGlobal(comment, predictionId)).join('');
                                        }
                                    });
                            } else {
                                alert(data.message || 'Error posting reply');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('Error posting reply');
                        });
                    }

                    // Global version of renderComment for use outside DOMContentLoaded
                    function renderCommentGlobal(comment, predictionId, isReply = false) {
                        const replyClass = isReply ? 'reply-item' : '';
                        let html = `
                            <div class="comment-item ${replyClass}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="comment-author">${escapeHtmlGlobal(comment.user.name)}</span>
                                        <span class="comment-meta ms-2">
                                            <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                                            ${comment.user.reputation_score} pts
                                        </span>
                                    </div>
                                    <span class="comment-meta">${comment.created_at}</span>
                                </div>
                                <div class="comment-content">${escapeHtmlGlobal(comment.content)}</div>
                                <div class="mt-2">
                                    <button class="reply-btn" onclick="toggleReplyForm(${comment.comment_id}, ${predictionId})">
                                        <i class="bi bi-reply me-1"></i>Reply
                                    </button>
                                </div>
                                <div class="reply-form" id="reply-form-${comment.comment_id}">
                                    <div class="d-flex gap-2 mt-2">
                                        <input type="text" class="form-control comment-input reply-input"
                                               placeholder="Write a reply..."
                                               id="reply-input-${comment.comment_id}"
                                               onkeypress="if(event.key === 'Enter') { event.preventDefault(); submitReply(${comment.comment_id}, ${predictionId}); }"
                                               style="border-radius: 20px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                        <button class="btn btn-primary btn-sm" onclick="submitReply(${comment.comment_id}, ${predictionId})" style="border-radius: 20px;">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </div>
                                </div>
                        `;

                        if (comment.replies && comment.replies.length > 0) {
                            html += '<div class="mt-2">';
                            comment.replies.forEach(reply => {
                                html += renderCommentGlobal(reply, predictionId, true);
                            });
                            html += '</div>';
                        }

                        html += '</div>';
                        return html;
                    }

                    function escapeHtmlGlobal(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }

                    // ========== STORY VIEWER FUNCTIONALITY ==========
                    @php
                        $storyDataArray = isset($hotPredictions) ? $hotPredictions->map(function($p) {
                            return [
                                'id' => $p->prediction_id,
                                'userId' => $p->user_id,
                                'userName' => $p->user->first_name,
                                'userRep' => $p->user->reputation_score,
                                'userAvatar' => $p->user->profile_picture
                                    ? '/images/profile_pictures/' . $p->user->profile_picture
                                    : '/images/default.png',
                                'symbol' => $p->stock->symbol,
                                'companyName' => $p->stock->company_name,
                                'predictionType' => $p->prediction_type,
                                'targetPrice' => $p->target_price,
                                'reasoning' => $p->reasoning,
                                'endDate' => $p->end_date,
                                'upvotes' => $p->upvotes ?? 0,
                                'downvotes' => $p->downvotes ?? 0,
                            ];
                        })->toArray() : [];
                    @endphp
                    (function() {
                        // Only initialize on mobile
                        if (window.innerWidth >= 768) return;

                        // Store prediction data for stories
                        const storyData = @json($storyDataArray);

                        if (!storyData || storyData.length === 0) return;

                        // State
                        let currentIndex = 0;
                        let isOpen = false;
                        let autoAdvanceTimer = null;
                        let touchStartX = 0;
                        let touchEndX = 0;
                        const SWIPE_THRESHOLD = 50;
                        const AUTO_ADVANCE_DELAY = 8000;

                        // DOM Elements
                        const viewer = document.getElementById('story-viewer');
                        if (!viewer) return;

                        const progressBars = viewer.querySelectorAll('.story-progress-bar');
                        const userLink = viewer.querySelector('.story-user-link');
                        const userAvatar = viewer.querySelector('.story-user-avatar');
                        const userName = viewer.querySelector('.story-user-name');
                        const userRep = viewer.querySelector('.rep-value');
                        const stockSymbol = viewer.querySelector('.story-stock-symbol');
                        const predictionBadge = viewer.querySelector('.story-prediction-badge');
                        const targetValue = viewer.querySelector('.story-target .value');
                        const reasoning = viewer.querySelector('.story-reasoning');
                        const upvotesCount = viewer.querySelector('.story-upvotes .count');
                        const downvotesCount = viewer.querySelector('.story-downvotes .count');
                        const endDate = viewer.querySelector('.story-end-date .date');
                        const content = viewer.querySelector('.story-content');
                        const closeBtn = viewer.querySelector('.story-close-btn');
                        const prevBtn = viewer.querySelector('.story-btn-prev');
                        const nextBtn = viewer.querySelector('.story-btn-next');
                        const navPrev = viewer.querySelector('.story-nav-prev');
                        const navNext = viewer.querySelector('.story-nav-next');

                        // Open story viewer
                        function openStory(index) {
                            if (storyData.length === 0) return;

                            currentIndex = index;
                            isOpen = true;

                            // Show viewer
                            viewer.style.display = 'flex';
                            viewer.setAttribute('aria-hidden', 'false');
                            document.body.style.overflow = 'hidden';

                            // Update content
                            updateStory();

                            // Start auto-advance
                            startAutoAdvance();

                            // Focus for accessibility
                            closeBtn.focus();
                        }

                        // Close story viewer
                        function closeStory() {
                            isOpen = false;
                            viewer.style.display = 'none';
                            viewer.setAttribute('aria-hidden', 'true');
                            document.body.style.overflow = '';

                            stopAutoAdvance();
                            resetProgress();
                        }

                        // Update story content
                        function updateStory() {
                            const story = storyData[currentIndex];
                            if (!story) return;

                            // Update user info
                            userLink.setAttribute('data-user-id', story.userId);
                            userLink.href = '/profile/' + story.userId;
                            userAvatar.src = story.userAvatar;
                            userAvatar.alt = story.userName;
                            userName.textContent = story.userName;
                            userRep.textContent = story.userRep.toLocaleString();

                            // Update stock info
                            stockSymbol.textContent = '$' + story.symbol;
                            predictionBadge.textContent = story.predictionType;
                            predictionBadge.className = 'story-prediction-badge ' + story.predictionType.toLowerCase();

                            // Update price
                            targetValue.textContent = '$' + parseFloat(story.targetPrice).toFixed(2);

                            // Update reasoning
                            reasoning.textContent = story.reasoning || 'No reasoning provided.';

                            // Update votes
                            upvotesCount.textContent = story.upvotes;
                            downvotesCount.textContent = story.downvotes;

                            // Update end date
                            const date = new Date(story.endDate);
                            endDate.textContent = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                            // Update progress bars
                            updateProgress();

                            // Update nav buttons
                            prevBtn.disabled = currentIndex === 0;
                            nextBtn.disabled = currentIndex === storyData.length - 1;
                        }

                        // Update progress indicators
                        function updateProgress() {
                            progressBars.forEach((bar, i) => {
                                bar.classList.remove('completed', 'active');
                                const fill = bar.querySelector('.story-progress-fill');
                                fill.style.width = '0';
                                fill.style.animation = 'none';

                                if (i < currentIndex) {
                                    bar.classList.add('completed');
                                } else if (i === currentIndex) {
                                    bar.classList.add('active');
                                    // Trigger reflow and start animation
                                    void fill.offsetWidth;
                                    fill.style.animation = '';
                                }
                            });
                        }

                        // Reset all progress
                        function resetProgress() {
                            progressBars.forEach(bar => {
                                bar.classList.remove('completed', 'active');
                                const fill = bar.querySelector('.story-progress-fill');
                                fill.style.width = '0';
                                fill.style.animation = 'none';
                            });
                        }

                        // Navigate to previous story
                        function prevStory() {
                            if (currentIndex > 0) {
                                animateTransition('right', () => {
                                    currentIndex--;
                                    updateStory();
                                    restartAutoAdvance();
                                });
                            }
                        }

                        // Navigate to next story
                        function nextStory() {
                            if (currentIndex < storyData.length - 1) {
                                animateTransition('left', () => {
                                    currentIndex++;
                                    updateStory();
                                    restartAutoAdvance();
                                });
                            } else {
                                // Close at end
                                closeStory();
                            }
                        }

                        // Animate content transition
                        function animateTransition(direction, callback) {
                            content.classList.add('slide-' + direction);

                            setTimeout(() => {
                                callback();
                                content.classList.remove('slide-' + direction);
                            }, 150);
                        }

                        // Auto-advance timer
                        function startAutoAdvance() {
                            stopAutoAdvance();
                            autoAdvanceTimer = setTimeout(() => {
                                nextStory();
                            }, AUTO_ADVANCE_DELAY);
                        }

                        function stopAutoAdvance() {
                            if (autoAdvanceTimer) {
                                clearTimeout(autoAdvanceTimer);
                                autoAdvanceTimer = null;
                            }
                        }

                        function restartAutoAdvance() {
                            stopAutoAdvance();
                            startAutoAdvance();
                        }

                        // Touch/Swipe handling using Pointer Events
                        function handlePointerDown(e) {
                            touchStartX = e.clientX;
                            stopAutoAdvance();
                        }

                        function handlePointerUp(e) {
                            touchEndX = e.clientX;
                            handleSwipe();
                            startAutoAdvance();
                        }

                        function handleSwipe() {
                            const diff = touchStartX - touchEndX;

                            if (Math.abs(diff) > SWIPE_THRESHOLD) {
                                if (diff > 0) {
                                    // Swipe left = next
                                    nextStory();
                                } else {
                                    // Swipe right = prev
                                    prevStory();
                                }
                            }
                        }

                        // Event Listeners

                        // Open story from carousel items (skip the first "Create New" item)
                        document.querySelectorAll('.mobile-hot-carousel .hot-prediction-item').forEach((item, index) => {
                            // Skip first item which is "Create New Prediction"
                            if (index === 0) return;

                            item.addEventListener('click', function(e) {
                                if (window.innerWidth < 768) {
                                    e.preventDefault();
                                    openStory(index - 1); // Adjust for skipped create button
                                }
                            });
                        });

                        // Close button
                        closeBtn.addEventListener('click', closeStory);

                        // Navigation buttons
                        prevBtn.addEventListener('click', prevStory);
                        nextBtn.addEventListener('click', nextStory);

                        // Navigation touch zones
                        navPrev.addEventListener('click', prevStory);
                        navNext.addEventListener('click', nextStory);

                        // Keyboard navigation
                        viewer.addEventListener('keydown', function(e) {
                            if (!isOpen) return;

                            switch(e.key) {
                                case 'ArrowLeft':
                                    prevStory();
                                    break;
                                case 'ArrowRight':
                                case ' ':
                                    e.preventDefault();
                                    nextStory();
                                    break;
                                case 'Escape':
                                    closeStory();
                                    break;
                            }
                        });

                        // Swipe handling
                        viewer.addEventListener('pointerdown', handlePointerDown);
                        viewer.addEventListener('pointerup', handlePointerUp);

                        // Prevent scroll while story is open
                        viewer.addEventListener('touchmove', function(e) {
                            if (e.target.closest('.story-reasoning')) return; // Allow scroll in reasoning
                            e.preventDefault();
                        }, { passive: false });

                        // Pause auto-advance on touch
                        viewer.addEventListener('touchstart', stopAutoAdvance);
                        viewer.addEventListener('touchend', startAutoAdvance);

                        // Handle window resize
                        window.addEventListener('resize', function() {
                            if (window.innerWidth >= 768 && isOpen) {
                                closeStory();
                            }
                        });
                    })();

                    // ========== REASONING TEXT EXPAND/COLLAPSE (ALL SCREEN SIZES) ==========
                    (function() {
                        function initReasoningExpand() {
                            document.querySelectorAll('.reasoning-wrapper').forEach(wrapper => {
                                const textEl = wrapper.querySelector('.reasoning-text');
                                const expandHint = wrapper.querySelector('.reasoning-expand-hint');

                                if (!textEl || !expandHint) return;

                                // Reset state first
                                textEl.classList.remove('truncated', 'expanded');
                                expandHint.classList.remove('expanded');
                                expandHint.style.display = 'none';

                                // Measure the full height without truncation
                                const fullHeight = textEl.scrollHeight;

                                // Now add truncation and measure again
                                textEl.classList.add('truncated');

                                // Force a reflow to ensure CSS is applied
                                void textEl.offsetHeight;

                                const truncatedHeight = textEl.clientHeight;

                                // Check if text is being clamped
                                // scrollHeight > clientHeight means content is cut off
                                const isOverflowing = textEl.scrollHeight > textEl.clientHeight ||
                                                      fullHeight > truncatedHeight + 5;

                                if (isOverflowing) {
                                    expandHint.style.display = 'inline-flex';
                                    expandHint.querySelector('.expand-text').textContent = 'Show more';
                                } else {
                                    // Not overflowing, remove truncation class as it's not needed
                                    textEl.classList.remove('truncated');
                                    expandHint.style.display = 'none';
                                }
                            });
                        }

                        // Toggle expand/collapse - handle both text click and hint click
                        function handleExpandToggle(e) {
                            const expandHint = e.target.closest('.reasoning-expand-hint');
                            const textEl = e.target.closest('.reasoning-text.truncated, .reasoning-text.expanded');

                            // Must click on either the hint or a truncatable text element
                            if (!expandHint && !textEl) return;

                            // Get the wrapper
                            const wrapper = (expandHint || textEl).closest('.reasoning-wrapper');
                            if (!wrapper) return;

                            const text = wrapper.querySelector('.reasoning-text');
                            const hint = wrapper.querySelector('.reasoning-expand-hint');

                            // Only toggle if the hint is visible (meaning text is truncatable)
                            if (!text || !hint || hint.style.display === 'none') return;

                            // Prevent the click from bubbling to parent elements
                            e.preventDefault();
                            e.stopPropagation();

                            const isExpanded = text.classList.contains('expanded');

                            if (isExpanded) {
                                text.classList.remove('expanded');
                                text.classList.add('truncated');
                                hint.classList.remove('expanded');
                                hint.querySelector('.expand-text').textContent = 'Show more';
                            } else {
                                text.classList.add('expanded');
                                hint.classList.add('expanded');
                                hint.querySelector('.expand-text').textContent = 'Show less';
                            }
                        }

                        // Use capturing phase to ensure we get the event before other handlers
                        document.addEventListener('click', handleExpandToggle, true);

                        // Initialize on load and resize
                        document.addEventListener('DOMContentLoaded', initReasoningExpand);

                        // Debounce resize to avoid performance issues
                        let resizeTimeout;
                        window.addEventListener('resize', function() {
                            clearTimeout(resizeTimeout);
                            resizeTimeout = setTimeout(initReasoningExpand, 150);
                        });

                        // Run immediately if DOM is already loaded
                        if (document.readyState !== 'loading') {
                            initReasoningExpand();
                        }
                    })();

                    // ========== DESKTOP HOT POSTS CAROUSEL ==========
                    (function() {
                        // Only initialize on desktop
                        if (window.innerWidth < 768) return;

                        const carousel = document.getElementById('desktopHotCarousel');
                        const track = document.getElementById('hotCarouselTrack');
                        const pauseBtn = document.getElementById('hotCarouselPauseBtn');
                        const indicators = document.getElementById('hotCarouselIndicators');

                        if (!carousel || !track) return;

                        const cards = track.querySelectorAll('.hot-carousel-card');
                        const totalCards = cards.length;

                        // Need at least 3 cards to show carousel
                        if (totalCards < 3) return;

                        // Configuration - adapt visible cards based on total available
                        const MAX_VISIBLE_CARDS = 5;
                        const VISIBLE_CARDS = Math.min(totalCards, MAX_VISIBLE_CARDS);
                        const AUTO_ROTATE_INTERVAL = 5000; // 5 seconds
                        const CENTER_INDEX = Math.floor(VISIBLE_CARDS / 2); // Middle position

                        // State
                        let currentCenterCard = Math.min(CENTER_INDEX, totalCards - 1); // Start centered
                        let autoRotateTimer = null;
                        let isPaused = false;
                        let isHovering = false;

                        // Initialize carousel
                        function init() {
                            // Show all cards initially for proper layout
                            cards.forEach(card => {
                                card.style.display = 'flex';
                            });

                            updateCarousel();
                            startAutoRotate();

                            // Event listeners
                            carousel.addEventListener('mouseenter', handleMouseEnter);
                            carousel.addEventListener('mouseleave', handleMouseLeave);
                            carousel.addEventListener('wheel', handleWheel, { passive: false });

                            if (pauseBtn) {
                                pauseBtn.addEventListener('click', togglePause);
                            }

                            if (indicators) {
                                indicators.querySelectorAll('.hot-carousel-dot').forEach(dot => {
                                    dot.addEventListener('click', () => {
                                        const index = parseInt(dot.dataset.index);
                                        goToCard(index);
                                    });
                                });
                            }

                            // Handle window resize
                            window.addEventListener('resize', () => {
                                if (window.innerWidth < 768) {
                                    stopAutoRotate();
                                } else {
                                    if (!isPaused) startAutoRotate();
                                }
                            });
                        }

                        // Update carousel display
                        function updateCarousel() {
                            cards.forEach((card, index) => {
                                // Calculate visible position relative to center
                                let relativePos = index - currentCenterCard;

                                // Handle wrapping for circular carousel effect
                                if (totalCards > VISIBLE_CARDS) {
                                    const halfVisible = Math.floor(VISIBLE_CARDS / 2);
                                    if (relativePos < -halfVisible) {
                                        relativePos += totalCards;
                                    } else if (relativePos > halfVisible) {
                                        relativePos -= totalCards;
                                    }
                                }

                                // Map relative position to visible positions
                                const visiblePos = relativePos + CENTER_INDEX;

                                // Only show cards within visible range
                                if (visiblePos >= 0 && visiblePos < VISIBLE_CARDS) {
                                    card.style.display = 'flex';
                                    card.setAttribute('data-visible-position', visiblePos);
                                    card.style.order = visiblePos;
                                } else {
                                    card.style.display = 'none';
                                    card.removeAttribute('data-visible-position');
                                }
                            });

                            // Update indicators
                            if (indicators) {
                                indicators.querySelectorAll('.hot-carousel-dot').forEach((dot, index) => {
                                    dot.classList.toggle('active', index === currentCenterCard);
                                });
                            }
                        }

                        // Navigate to a specific card (center it)
                        function goToCard(index) {
                            if (index < 0) {
                                index = totalCards - 1;
                            } else if (index >= totalCards) {
                                index = 0;
                            }

                            currentCenterCard = index;
                            updateCarousel();
                            restartAutoRotate();
                        }

                        // Rotate to next card
                        function rotateNext() {
                            goToCard(currentCenterCard + 1);
                        }

                        // Rotate to previous card
                        function rotatePrev() {
                            goToCard(currentCenterCard - 1);
                        }

                        // Auto-rotation controls
                        function startAutoRotate() {
                            if (isPaused || isHovering) return;
                            stopAutoRotate();
                            autoRotateTimer = setInterval(rotateNext, AUTO_ROTATE_INTERVAL);
                        }

                        function stopAutoRotate() {
                            if (autoRotateTimer) {
                                clearInterval(autoRotateTimer);
                                autoRotateTimer = null;
                            }
                        }

                        function restartAutoRotate() {
                            stopAutoRotate();
                            if (!isPaused && !isHovering) {
                                startAutoRotate();
                            }
                        }

                        // Toggle pause/play
                        function togglePause() {
                            isPaused = !isPaused;

                            if (pauseBtn) {
                                const icon = pauseBtn.querySelector('i');
                                if (isPaused) {
                                    pauseBtn.classList.add('paused');
                                    icon.classList.remove('bi-pause-fill');
                                    icon.classList.add('bi-play-fill');
                                    pauseBtn.setAttribute('aria-label', 'Resume carousel');
                                    pauseBtn.setAttribute('title', 'Resume auto-rotation');
                                    stopAutoRotate();
                                } else {
                                    pauseBtn.classList.remove('paused');
                                    icon.classList.remove('bi-play-fill');
                                    icon.classList.add('bi-pause-fill');
                                    pauseBtn.setAttribute('aria-label', 'Pause carousel');
                                    pauseBtn.setAttribute('title', 'Pause auto-rotation');
                                    startAutoRotate();
                                }
                            }
                        }

                        // Mouse enter - pause auto-rotation
                        function handleMouseEnter() {
                            isHovering = true;
                            stopAutoRotate();
                        }

                        // Mouse leave - resume auto-rotation
                        function handleMouseLeave() {
                            isHovering = false;
                            if (!isPaused) {
                                startAutoRotate();
                            }
                        }

                        // Mouse wheel scrolling
                        function handleWheel(e) {
                            // Prevent page scroll when hovering over carousel
                            e.preventDefault();

                            // Debounce to prevent too rapid scrolling
                            if (carousel.dataset.scrolling === 'true') return;
                            carousel.dataset.scrolling = 'true';

                            setTimeout(() => {
                                carousel.dataset.scrolling = 'false';
                            }, 300);

                            // Scroll down = next, scroll up = previous
                            if (e.deltaY > 0) {
                                rotateNext();
                            } else if (e.deltaY < 0) {
                                rotatePrev();
                            }
                        }

                        // Initialize when DOM is ready
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', init);
                        } else {
                            init();
                        }
                    })();
            </script>
                @endpush
@endsection