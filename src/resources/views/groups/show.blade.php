@extends('layouts.app')

@section('title', $group->name . ' - SoVest')

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

    /* Group Header Styles */
    .group-header-banner {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e5e7eb;
    }

    body.dark-mode .group-header-banner {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%);
        border-color: #404040;
    }

    .group-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }

    body.dark-mode .group-title {
        color: #f3f4f6;
    }

    .group-description {
        color: #6b7280;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
    }

    body.dark-mode .group-description {
        color: #9ca3af;
    }

    .group-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .group-stat {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        color: #6b7280;
        font-size: 0.875rem;
    }

    body.dark-mode .group-stat {
        color: #9ca3af;
    }

    /* Gradient text */
    .gradient-text {
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Feed header */
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
        right: 0;
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

    /* Reasoning text expand/collapse */
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

    /* Comment items */
    .comment-item {
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        background: rgba(0, 0, 0, 0.02);
        border-left: 3px solid #e5e7eb;
    }

    body.dark-mode .comment-item {
        background: rgba(255, 255, 255, 0.03);
        border-left-color: #404040;
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

    /* Prediction badge styles */
    .prediction-badge-vibrant {
        padding: 0.5rem 1.2rem;
        font-size: 0.875rem;
        font-weight: 700;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    .badge-bullish {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .badge-bearish {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    /* Leaderboard sidebar card */
    .leaderboard-sidebar {
        background: #ffffff;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    body.dark-mode .leaderboard-sidebar {
        background: #2a2a2a;
        border-color: #404040;
    }

    .leaderboard-item {
        transition: background-color 0.2s ease;
        border-radius: 0.5rem;
    }

    .leaderboard-item:hover {
        background-color: rgba(16, 185, 129, 0.05);
    }

    body.dark-mode .leaderboard-item:hover {
        background-color: rgba(16, 185, 129, 0.1);
    }

    .leaderboard-name {
        color: #111827;
    }

    body.dark-mode .leaderboard-name {
        color: #f3f4f6;
    }

    .rank-number {
        color: #6b7280;
    }

    body.dark-mode .rank-number {
        color: #9ca3af;
    }

    /* Mobile adjustments */
    @media (max-width: 767.98px) {
        .mobile-hide-sidebar {
            display: none !important;
        }

        .feed-header-container {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 0.5rem;
            padding: 0 0 0.75rem 0;
        }

        .feed-header-container .feed-header {
            font-size: 0.95rem !important;
            white-space: nowrap;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
            border-bottom: none !important;
            flex-shrink: 1;
            min-width: 0;
        }

        .feed-header-container .sort-dropdown-wrapper {
            flex-shrink: 0;
            position: relative;
        }

        .feed-header-container .sort-dropdown-btn {
            padding: 0.375rem 0.5rem;
        }

        .feed-header-container .sort-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            right: auto;
            min-width: 200px;
        }

        .prediction-card {
            margin: 0 0 1.25rem 0 !important;
            width: 100% !important;
            padding: 1.25rem !important;
            border-radius: 1rem !important;
        }

        .vote-btn {
            padding: 0.5rem 0.875rem !important;
            font-size: 0.85rem;
            min-height: 44px;
            border-radius: 22px !important;
        }

        .comments-toggle {
            min-height: 44px;
            padding: 0.5rem 0.875rem !important;
        }

        .group-header-banner {
            padding: 1rem;
        }

        .group-title {
            font-size: 1.25rem;
        }
    }

    @media (min-width: 768px) {
        .mobile-hide-sidebar {
            display: block !important;
        }
    }
</style>
@endsection

@section('content')
<div class="container mt-4">
    {{-- Group Header Banner --}}
    <div class="group-header-banner">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="group-title">
                    <i class="bi bi-people-fill" style="color: #10b981;"></i>
                    {{ $group->name }}
                </h1>
                @if($group->description)
                    <p class="group-description mb-2">{{ $group->description }}</p>
                @endif
                <div class="group-meta">
                    <span class="group-stat">
                        <i class="bi bi-people"></i> {{ $group->member_count }} members
                    </span>
                    @if($userRank > 0)
                        <span class="group-stat">
                            <i class="bi bi-trophy-fill" style="color: #f59e0b;"></i> Your rank: #{{ $userRank }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> All Groups
                </a>
                @if($isAdmin)
                    <a href="{{ route('groups.settings', $group->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                @else
                    <form action="{{ route('groups.leave', $group->id) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Are you sure you want to leave this group?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-left"></i> Leave
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mobile-bottom-padding">
        {{-- Left Sidebar: Group Leaderboard --}}
        <div class="col-lg-3 col-md-4 order-2 order-lg-1 mobile-hide-sidebar">
            <div class="sticky-top" style="top: 1rem;">
                {{-- Leaderboard Card --}}
                <div class="card mb-4 leaderboard-sidebar">
                    <div class="card-body" style="padding: 1.25rem;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0" style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-trophy-fill" style="color: #f59e0b;"></i>
                                Group Leaderboard
                            </h5>
                        </div>

                        @if(!empty($leaderboard) && count($leaderboard) > 0)
                            <div class="leaderboard-list">
                                @foreach($leaderboard as $index => $leader)
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
                                                <span class="rank-number" style="width: 1.25rem; text-align: center; font-weight: 600; font-size: 0.85rem;">{{ $index + 1 }}</span>
                                            @endif
                                            {{-- User Name --}}
                                            <span class="leaderboard-name" style="font-weight: 600; font-size: 0.9rem;">
                                                {{ $leader['first_name'] }} {{ substr($leader['last_name'], 0, 1) }}.
                                                @if($leader['id'] == $userID)
                                                    <span class="badge bg-success" style="font-size: 0.65rem; padding: 0.2rem 0.4rem;">You</span>
                                                @endif
                                                @if($group->admin_id == $leader['id'])
                                                    <span class="badge" style="background: #f59e0b; font-size: 0.65rem; padding: 0.2rem 0.4rem;">Admin</span>
                                                @endif
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

                {{-- User's Current Rank Card (if not in top 10) --}}
                @if($userRank == 0 && $userInfo)
                    <div class="card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%); border: 1px solid #10b981;">
                        <div class="card-body" style="padding: 1rem;">
                            <h6 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 0.5rem;">Your Current Standing</h6>
                            <div class="d-flex align-items-center justify-content-between">
                                <span style="font-weight: 600; color: #111827;">{{ $userInfo['first_name'] }} {{ $userInfo['last_name'] }}</span>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill" style="color: #f59e0b;"></i>
                                    <span style="font-weight: 700; color: #10b981;">{{ number_format($userInfo['reputation_score']) }}</span>
                                </div>
                            </div>
                            <p class="mb-0 mt-2" style="font-size: 0.8rem; color: #6b7280;">Keep making accurate predictions to climb the leaderboard!</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Center Column: Group Predictions Feed --}}
        <div class="col-lg-6 col-md-8 order-1 order-lg-2">
            {{-- Feed Header with Sort Dropdown --}}
            <div class="feed-header-container d-flex justify-content-between align-items-center mb-4">
                <h2 class="feed-header mb-0" style="border-bottom: none; padding-bottom: 0;">
                    @php
                        $sortConfig = [
                            'trending' => ['icon' => 'bi-fire', 'color' => '#ef4444', 'label' => 'Trending'],
                            'recent' => ['icon' => 'bi-clock-fill', 'color' => '#3b82f6', 'label' => 'Recent'],
                            'controversial' => ['icon' => 'bi-arrow-left-right', 'color' => '#f59e0b', 'label' => 'Controversial'],
                        ];
                        $currentSort = $sortConfig[$sort ?? 'trending'];
                    @endphp
                    <i class="{{ $currentSort['icon'] }} me-2" style="color: {{ $currentSort['color'] }};"></i>
                    {{ $currentSort['label'] }} Predictions
                </h2>

                {{-- Sort Dropdown --}}
                <div class="sort-dropdown-wrapper">
                    <button class="sort-dropdown-btn" id="sortDropdownBtn" aria-expanded="false" aria-haspopup="true">
                        <svg class="sort-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="6" x2="20" y2="6"></line>
                            <line x1="4" y1="12" x2="16" y2="12"></line>
                            <line x1="4" y1="18" x2="12" y2="18"></line>
                        </svg>
                        <span class="sort-label d-none d-sm-inline">Sort</span>
                        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sort-dropdown-menu" id="sortDropdownMenu" role="menu">
                        <a href="{{ route('groups.show', $group->id) }}?sort=trending" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'trending' ? 'active' : '' }}" role="menuitem">
                            <i class="bi bi-fire" style="font-size: 1.125rem; color: #ef4444;"></i>
                            <span>Trending</span>
                            <span class="sort-description">Popular right now</span>
                        </a>
                        <a href="{{ route('groups.show', $group->id) }}?sort=recent" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'recent' ? 'active' : '' }}" role="menuitem">
                            <i class="bi bi-clock-fill" style="font-size: 1.125rem; color: #3b82f6;"></i>
                            <span>Recent</span>
                            <span class="sort-description">Newest first</span>
                        </a>
                        <a href="{{ route('groups.show', $group->id) }}?sort=controversial" class="sort-dropdown-item {{ ($sort ?? 'trending') === 'controversial' ? 'active' : '' }}" role="menuitem">
                            <i class="bi bi-arrow-left-right" style="font-size: 1.125rem; color: #f59e0b;"></i>
                            <span>Controversial</span>
                            <span class="sort-description">Most debated</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Predictions Feed --}}
            @if($predictions->isEmpty())
                <div class="prediction-card text-center" style="padding: 3rem; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <h4 style="margin-bottom: 0.5rem;">No predictions yet</h4>
                    <p class="text-muted">Be the first in this group to make a prediction!</p>
                    <a href="{{ route('predictions.create') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-plus-circle-fill me-2"></i>Create Prediction
                    </a>
                </div>
            @else
                @foreach($predictions as $index => $prediction)
                    <div class="prediction-card" data-prediction-id="{{ $prediction->prediction_id }}" style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem;">
                        @php
                            $profilePicture = $prediction->user->profile_picture
                                ? asset('images/profile_pictures/' . $prediction->user->profile_picture)
                                : asset('images/default.png');
                        @endphp

                        {{-- Top section: Profile on left, dates on right --}}
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

                            {{-- End Date Badge --}}
                            <div>
                                <span class="badge" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                    <i class="bi bi-clock"></i> Ends {{ date('M j', strtotime($prediction->end_date)) }}
                                </span>
                            </div>
                        </div>

                        {{-- Company + Reasoning --}}
                        @if(!empty($prediction->reasoning))
                            @if(!empty($prediction->stock->company_name))
                                <div class="mb-3">
                                    <h5 class="mb-2" style="font-size: 1.25rem; font-weight: 700;">
                                        <span class="text-primary">{{ $prediction->stock->symbol }}</span>
                                        <span style="color: #6b7280; font-weight: 500; font-size: 1rem;">{{ $prediction->stock->company_name }}</span>
                                    </h5>
                                </div>
                            @endif

                            {{-- Prediction Type & Target Price --}}
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

                            {{-- Reasoning text with expand/collapse --}}
                            <div class="reasoning-wrapper">
                                <p class="reasoning-text mb-3 truncated" style="line-height: 1.6;" data-full-text="{{ $prediction->reasoning }}">{{ $prediction->reasoning }}</p>
                                <div class="reasoning-expand-hint" style="display: none;">
                                    <span class="expand-text">Show more</span>
                                    <i class="bi bi-chevron-down"></i>
                                </div>
                            </div>
                        @endif

                        {{-- Engagement Bar: Votes & Stats --}}
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
                    {{ $predictions->appends(['sort' => $sort])->links() }}
                </div>
            @endif
        </div>

        {{-- Right Sidebar: Create Prediction & My Predictions --}}
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
                    @if($userPredictions->count() > 0)
                        @foreach($userPredictions as $index => $prediction)
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
@endsection

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

    // Vote buttons functionality
    const voteButtons = document.querySelectorAll(".vote-btn");

    voteButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

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
                    prediction_id: predictionId
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

    function toggleVoteStyle(button, voteType) {
        const card = button.closest('.prediction-card');
        const upvoteBtn = card.querySelector('.upvote-btn');
        const downvoteBtn = card.querySelector('.downvote-btn');

        // Remove voted classes
        upvoteBtn.classList.remove('voted-up');
        downvoteBtn.classList.remove('voted-down');

        // Add voted class to the clicked button
        if (voteType === 'upvote') {
            button.classList.add('voted-up');
        } else {
            button.classList.add('voted-down');
        }
    }

    // Reasoning text expand/collapse
    document.querySelectorAll('.reasoning-wrapper').forEach(wrapper => {
        const text = wrapper.querySelector('.reasoning-text');
        const hint = wrapper.querySelector('.reasoning-expand-hint');

        if (!text || !hint) return;

        // Check if text is truncated
        function checkTruncation() {
            if (text.scrollHeight > text.clientHeight + 5) {
                hint.style.display = 'inline-flex';
            } else {
                hint.style.display = 'none';
            }
        }

        checkTruncation();
        window.addEventListener('resize', checkTruncation);

        // Toggle expand/collapse
        const toggleExpand = () => {
            const isExpanded = text.classList.contains('expanded');
            text.classList.toggle('expanded');
            hint.classList.toggle('expanded');
            hint.querySelector('.expand-text').textContent = isExpanded ? 'Show more' : 'Show less';
        };

        text.addEventListener('click', toggleExpand);
        hint.addEventListener('click', toggleExpand);
    });

    // Comments toggle functionality
    document.querySelectorAll('.comments-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const predictionId = this.getAttribute('data-prediction-id');
            const commentsSection = document.getElementById(`comments-${predictionId}`);

            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                loadComments(predictionId);
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });

    function loadComments(predictionId) {
        const commentsList = document.getElementById(`comments-list-${predictionId}`);

        fetch(`/predictions/${predictionId}/comments`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.comments) {
                    if (data.comments.length === 0) {
                        commentsList.innerHTML = '<p class="no-comments-msg">No comments yet. Be the first to comment!</p>';
                    } else {
                        let html = '';
                        data.comments.forEach(comment => {
                            html += `
                                <div class="comment-item">
                                    <div class="d-flex justify-content-between">
                                        <span class="comment-author">${comment.user.first_name}</span>
                                        <span class="comment-meta">${comment.created_at_human}</span>
                                    </div>
                                    <p class="comment-content mb-0">${comment.content}</p>
                                </div>
                            `;
                        });
                        commentsList.innerHTML = html;
                    }
                } else {
                    commentsList.innerHTML = '<p class="text-muted text-center">Failed to load comments.</p>';
                }
            })
            .catch(err => {
                console.error(err);
                commentsList.innerHTML = '<p class="text-muted text-center">Error loading comments.</p>';
            });
    }

    // Submit comment functionality
    document.querySelectorAll('.submit-comment').forEach(button => {
        button.addEventListener('click', function() {
            const predictionId = this.getAttribute('data-prediction-id');
            const input = document.querySelector(`.comment-input[data-prediction-id="${predictionId}"]`);
            const content = input.value.trim();

            if (!content) return;

            fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    prediction_id: predictionId,
                    content: content
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(predictionId);

                    // Update comment count
                    const countEl = document.querySelector(`.comments-toggle[data-prediction-id="${predictionId}"] .comment-count`);
                    if (countEl) {
                        countEl.textContent = parseInt(countEl.textContent) + 1;
                    }
                } else {
                    alert(data.message || 'Failed to post comment.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error posting comment.');
            });
        });
    });

    // Submit comment on Enter key
    document.querySelectorAll('.comment-input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const predictionId = this.getAttribute('data-prediction-id');
                document.querySelector(`.submit-comment[data-prediction-id="${predictionId}"]`).click();
            }
        });
    });
});
</script>
@endpush
