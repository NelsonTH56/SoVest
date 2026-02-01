@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/leaderboard-card.css') }}">
<link rel="stylesheet" href="{{ asset('css/reddit-card.css') }}">
<link rel="stylesheet" href="{{ asset('css/home-feed.css') }}">
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
                    <a href="{{ route('predictions.view', ['id' => $hot->prediction_id]) }}"
                       class="mobile-hot-card {{ $hot->prediction_type == 'Bullish' ? 'bullish' : 'bearish' }}"
                       data-prediction-id="{{ $hot->prediction_id }}">
                        <div class="hot-card-top">
                            <span class="hot-card-symbol">{{ $hot->stock->symbol }}</span>
                            <span class="hot-card-badge-mini {{ $hot->prediction_type == 'Bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                                <i class="bi bi-{{ $hot->prediction_type == 'Bullish' ? 'arrow-up' : 'arrow-down' }}"></i>
                            </span>
                        </div>
                        @php
                            $currentPrice = $hot->stock->latestPrice->close_price ?? null;
                            $pctChange = ($hot->target_price && $currentPrice && $currentPrice > 0)
                                ? (($hot->target_price - $currentPrice) / $currentPrice) * 100
                                : null;
                        @endphp
                        <div class="hot-card-price">
                            ${{ number_format($hot->target_price, 2) }}
                            @if($pctChange !== null)
                                <span class="hot-card-pct {{ $pctChange >= 0 ? 'pct-up' : 'pct-down' }}">
                                    {{ $pctChange >= 0 ? '+' : '' }}{{ number_format($pctChange, 1) }}%
                                </span>
                            @endif
                        </div>
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

    {{-- Mobile Feed Header (outside row for proper alignment with Top Contributors) --}}
    @php
        $sortConfig = [
            'trending' => ['icon' => 'bi-fire', 'color' => '#ef4444', 'label' => 'Trending'],
            'recent' => ['icon' => 'bi-clock-fill', 'color' => '#3b82f6', 'label' => 'Recent'],
            'controversial' => ['icon' => 'bi-arrow-left-right', 'color' => '#f59e0b', 'label' => 'Controversial'],
        ];
        $currentSort = $sortConfig[$sort ?? 'trending'];
    @endphp
    <div class="mobile-feed-header-wrapper">
        <div class="mobile-feed-header">
            <h6 class="mobile-feed-title">
                <i class="{{ $currentSort['icon'] }}" style="color: {{ $currentSort['color'] }};"></i>
                {{ $currentSort['label'] }} Predictions
                <button class="sort-dropdown-toggle" id="mobileSortDropdownBtn" aria-expanded="false" aria-haspopup="true">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </h6>
        </div>
        <div class="mobile-sort-dropdown-menu" id="mobileSortDropdownMenu" role="menu">
            <a href="{{ url('home?sort=trending') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'trending' ? 'active' : '' }}" role="menuitem">
                <i class="bi bi-fire" style="font-size: 1.125rem; color: #ef4444;"></i>
                <span>Trending</span>
            </a>
            <a href="{{ url('home?sort=recent') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'recent' ? 'active' : '' }}" role="menuitem">
                <i class="bi bi-clock-fill" style="font-size: 1.125rem; color: #3b82f6;"></i>
                <span>Recent</span>
            </a>
            <a href="{{ url('home?sort=controversial') }}" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'controversial' ? 'active' : '' }}" role="menuitem">
                <i class="bi bi-arrow-left-right" style="font-size: 1.125rem; color: #f59e0b;"></i>
                <span>Controversial</span>
            </a>
        </div>
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
                            <i class="bi bi-trophy" style="color: #10b981;"></i>
                            Leaderboard
                        </h5>
                        <a href="{{ route('user.leaderboard') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 20px; font-size: 0.8rem;">
                            View All
                        </a>
                    </div>

                    @if(!empty($leaderboardUsers) && count($leaderboardUsers) > 0)
                        <div class="leaderboard-list">
                            @foreach($leaderboardUsers as $index => $leader)
                                @if($index > 0)
                                    <hr style="margin: 0.25rem 0; border-color: #e5e7eb; opacity: 0.5;">
                                @endif
                                <div class="leaderboard-item d-flex align-items-center justify-content-between" style="padding: 0.5rem 0.5rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        {{-- Rank with medal icon for top 3 --}}
                                        @if($index === 0)
                                            <i class="bi bi-1-circle" style="color: #10b981; font-size: 1.25rem;"></i>
                                        @elseif($index === 1)
                                            <i class="bi bi-2-circle" style="color: #10b981; font-size: 1.25rem;"></i>
                                        @elseif($index === 2)
                                            <i class="bi bi-3-circle" style="color: #10b981; font-size: 1.25rem;"></i>
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
                                        <i class="bi bi-star" style="color: #f59e0b; font-size: 0.75rem;"></i>
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
                        <a href="{{ route('predictions.view', ['id' => $hot->prediction_id]) }}"
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
                            @php
                                $desktopCurrentPrice = $hot->stock->latestPrice->close_price ?? null;
                                $desktopPctChange = ($hot->target_price && $desktopCurrentPrice && $desktopCurrentPrice > 0)
                                    ? (($hot->target_price - $desktopCurrentPrice) / $desktopCurrentPrice) * 100
                                    : null;
                            @endphp
                            <div class="hot-card-price">
                                ${{ number_format($hot->target_price, 2) }}
                                @if($desktopPctChange !== null)
                                    <span class="hot-card-pct {{ $desktopPctChange >= 0 ? 'pct-up' : 'pct-down' }}">
                                        {{ $desktopPctChange >= 0 ? '+' : '' }}{{ number_format($desktopPctChange, 1) }}%
                                    </span>
                                @endif
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
                    <button class="hot-carousel-dot {{ $index === 1 ? 'active' : '' }}"
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

        {{-- Desktop Feed Header with Inline Sort Dropdown --}}
        <div class="feed-header-inline desktop-only mb-3">
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
            <div class="reddit-card" style="padding: 3rem; text-align: center; cursor: default;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                <h4 style="margin-bottom: 0.5rem; color: var(--reddit-text-primary);">No predictions yet</h4>
                <p style="color: var(--reddit-text-secondary);">Be the first to make a prediction!</p>
            </div>
        @else
            @foreach($predictions as $prediction)
                <x-reddit-prediction-card :prediction="$prediction" />
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
                <i class="bi bi-plus-lg" style="font-size: 1.2rem;"></i>
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
                    // Mobile Sort Dropdown functionality
                    const mobileSortBtn = document.getElementById('mobileSortDropdownBtn');
                    const mobileSortMenu = document.getElementById('mobileSortDropdownMenu');

                    if (mobileSortBtn && mobileSortMenu) {
                        mobileSortBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const isOpen = mobileSortMenu.classList.contains('show');

                            if (isOpen) {
                                mobileSortMenu.classList.remove('show');
                                mobileSortBtn.classList.remove('open');
                                mobileSortBtn.setAttribute('aria-expanded', 'false');
                            } else {
                                mobileSortMenu.classList.add('show');
                                mobileSortBtn.classList.add('open');
                                mobileSortBtn.setAttribute('aria-expanded', 'true');
                            }
                        });

                        document.addEventListener('click', function(e) {
                            if (!mobileSortBtn.contains(e.target) && !mobileSortMenu.contains(e.target)) {
                                mobileSortMenu.classList.remove('show');
                                mobileSortBtn.classList.remove('open');
                                mobileSortBtn.setAttribute('aria-expanded', 'false');
                            }
                        });
                    }

                    // Desktop Sort Dropdown functionality
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

                        // Configuration - show 3 cards at a time for narrow sidebar
                        const MAX_VISIBLE_CARDS = 3;
                        const VISIBLE_CARDS = Math.min(totalCards, MAX_VISIBLE_CARDS);
                        const AUTO_ROTATE_INTERVAL = 5000; // 5 seconds
                        const CENTER_INDEX = 1; // Middle position (index 1 in 3-card layout)

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