@extends('layouts.app')

@section('title', $user->first_name . "'s Profile - SoVest")

@section('content')
@php
    $profilePicture = $user->profile_picture
        ? asset('images/profile_pictures/' . $user->profile_picture)
        : asset('images/default.png');
@endphp

<div class="public-profile-page">
    {{-- Profile Header --}}
    <div class="public-profile-header">
        <div class="public-profile-banner"></div>
        <div class="public-profile-content">
            <img src="{{ $profilePicture }}" class="public-profile-avatar" alt="{{ $user->first_name }}">
            <div class="public-profile-info">
                <h1 class="public-profile-name">{{ $user->first_name }} {{ substr($user->last_name ?? '', 0, 1) }}.</h1>
                @if($user->bio)
                    <p class="public-profile-bio">{{ $user->bio }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="public-profile-stats">
        <div class="stat-box">
            <span class="stat-value">{{ $userStats['total_predictions'] ?? 0 }}</span>
            <span class="stat-label">Predictions</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($userStats['avg_accuracy'] ?? 0, 1) }}%</span>
            <span class="stat-label">Accuracy</span>
        </div>
        <div class="stat-box highlight">
            <span class="stat-value">{{ number_format($user->reputation_score ?? 0) }}</span>
            <span class="stat-label">Reputation</span>
        </div>
    </div>

    {{-- Recent Predictions --}}
    <div class="public-profile-predictions">
        <h2 class="section-title">
            <i class="bi bi-graph-up-arrow"></i>
            Recent Predictions
        </h2>

        @if($recentPredictions->count() > 0)
            @foreach($recentPredictions as $prediction)
                <a href="{{ route('predictions.view', ['id' => $prediction->prediction_id]) }}" class="prediction-link-card">
                    <div class="plc-header">
                        <span class="plc-symbol">${{ $prediction->stock->symbol }}</span>
                        <span class="plc-badge {{ $prediction->prediction_type == 'Bullish' ? 'bullish' : 'bearish' }}">
                            {{ $prediction->prediction_type }}
                        </span>
                    </div>
                    <div class="plc-body">
                        <span class="plc-target">${{ number_format($prediction->target_price, 2) }}</span>
                        <span class="plc-votes">
                            <span class="up"><i class="bi bi-arrow-up"></i> {{ $prediction->upvotes ?? 0 }}</span>
                            <span class="down"><i class="bi bi-arrow-down"></i> {{ $prediction->downvotes ?? 0 }}</span>
                        </span>
                    </div>
                </a>
            @endforeach
        @else
            <div class="empty-predictions">
                <i class="bi bi-graph-up-arrow"></i>
                <p>No predictions yet</p>
            </div>
        @endif
    </div>

    {{-- Back Button --}}
    <div class="public-profile-back">
        <a href="{{ url()->previous() }}" class="back-link">
            <i class="bi bi-arrow-left"></i> Go Back
        </a>
    </div>
</div>
@endsection

@section('styles')
<style>
/* ==========================================================================
   Public Profile Page Styles
   ========================================================================== */

/* Theme Variables */
:root {
    --profile-bg-primary: #ffffff;
    --profile-bg-secondary: #f9fafb;
    --profile-text-primary: #111827;
    --profile-text-secondary: #6b7280;
    --profile-text-muted: #9ca3af;
    --profile-border-color: #e5e7eb;
    --profile-accent: #10b981;
}

body.dark-mode {
    --profile-bg-primary: #1f1f1f;
    --profile-bg-secondary: #2a2a2a;
    --profile-text-primary: #f3f4f6;
    --profile-text-secondary: #9ca3af;
    --profile-text-muted: #6b7280;
    --profile-border-color: #404040;
}

/* Page Container */
.public-profile-page {
    max-width: 600px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* Profile Header */
.public-profile-header {
    position: relative;
    margin-bottom: 1rem;
}

.public-profile-banner {
    height: 120px;
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
    border-radius: 0 0 1rem 1rem;
}

body.dark-mode .public-profile-banner {
    opacity: 0.8;
}

.public-profile-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -50px;
    padding: 0 1rem;
}

.public-profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--profile-bg-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.public-profile-info {
    text-align: center;
    margin-top: 0.75rem;
}

.public-profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--profile-text-primary);
    margin: 0;
}

.public-profile-bio {
    font-size: 0.9rem;
    color: var(--profile-text-secondary);
    margin: 0.5rem 0 0;
    max-width: 300px;
    line-height: 1.5;
}

/* Stats Row */
.public-profile-stats {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    padding: 1.5rem 1rem;
    background: var(--profile-bg-secondary);
    border-radius: 1rem;
    margin: 1rem;
}

.stat-box {
    text-align: center;
}

.stat-box .stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--profile-text-primary);
}

.stat-box .stat-label {
    font-size: 0.75rem;
    color: var(--profile-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-box.highlight .stat-value {
    color: #10b981;
}

/* Predictions Section */
.public-profile-predictions {
    padding: 0 1rem;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--profile-text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: #10b981;
}

/* Prediction Link Card */
.prediction-link-card {
    display: block;
    background: var(--profile-bg-primary);
    border: 1px solid var(--profile-border-color);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.prediction-link-card:hover {
    border-color: #10b981;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

body.dark-mode .prediction-link-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.plc-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.plc-symbol {
    font-family: ui-monospace, SFMono-Regular, monospace;
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--profile-text-primary);
}

.plc-badge {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.plc-badge.bullish {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

.plc-badge.bearish {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

body.dark-mode .plc-badge.bullish {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

body.dark-mode .plc-badge.bearish {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

.plc-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.plc-target {
    font-weight: 600;
    color: #10b981;
    font-size: 1rem;
}

.plc-votes {
    display: flex;
    gap: 0.75rem;
    font-size: 0.85rem;
}

.plc-votes .up {
    color: #10b981;
}

.plc-votes .down {
    color: #ef4444;
}

/* Empty State */
.empty-predictions {
    text-align: center;
    padding: 2rem;
    color: var(--profile-text-muted);
}

.empty-predictions i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.5rem;
}

/* Back Button */
.public-profile-back {
    padding: 1.5rem 1rem;
    text-align: center;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--profile-text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: #10b981;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .public-profile-page {
        padding-bottom: 1rem;
    }

    .public-profile-banner {
        height: 100px;
        border-radius: 0;
    }

    .public-profile-avatar {
        width: 80px;
        height: 80px;
    }

    .public-profile-name {
        font-size: 1.25rem;
    }

    .public-profile-stats {
        margin: 1rem 0.5rem;
        gap: 1rem;
        padding: 1rem;
    }

    .stat-box .stat-value {
        font-size: 1.1rem;
    }

    .public-profile-predictions {
        padding: 0 0.5rem;
    }

    .prediction-link-card {
        padding: 0.875rem;
    }
}
</style>
@endsection
