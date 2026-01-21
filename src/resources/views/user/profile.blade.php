@extends('layouts.app')

@section('title', $user->first_name . "'s Profile - SoVest")

@section('content')
@php
    $profilePicture = $user->profile_picture
        ? asset('images/profile_pictures/' . $user->profile_picture)
        : asset('images/default.png');
    $isOwnProfile = Auth::check() && Auth::id() === $user->id;
@endphp

<!-- Profile Header -->
<div class="profile-header-wrapper">
    <div class="profile-header-banner"></div>

    <div class="profile-header-content">
        <!-- Profile Picture -->
        <div class="profile-avatar-wrapper">
            <img src="{{ $profilePicture }}" class="profile-avatar" alt="{{ $user->first_name }}'s Profile Picture">
            @if($isOwnProfile)
                <form action="{{ route('user.profile.uploadPhoto') }}" method="POST" enctype="multipart/form-data" id="photoUploadForm">
                    @csrf
                    <label class="profile-avatar-upload">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        <input type="file" name="profile_picture" onchange="this.form.submit()" hidden accept="image/*">
                    </label>
                </form>
            @endif
        </div>

        <!-- User Info -->
        <div class="profile-user-info">
            <h1 class="profile-name">{{ $user->first_name }} {{ $user->last_name }}</h1>
            <p class="profile-username">{{ $user->email }}</p>

            @if($user->bio)
                <p class="profile-bio">{{ $user->bio }}</p>
            @endif

            @if($isOwnProfile)
                <button type="button" class="btn-edit-bio-inline" id="editBioBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    {{ $user->bio ? 'Edit bio' : 'Add bio' }}
                </button>
            @endif
        </div>

        <!-- Stats -->
        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $userStats['total_predictions'] ?? 0 }}</span>
                <span class="stat-label">Predictions</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ number_format($userStats['avg_accuracy'] ?? 0, 1) }}%</span>
                <span class="stat-label">Accuracy</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ number_format($user->reputation_score ?? 0) }}</span>
                <span class="stat-label">Reputation</span>
            </div>
        </div>
    </div>
</div>

@if($isOwnProfile)
<!-- Bio Edit Modal -->
<div class="bio-edit-modal" id="bioEditModal" style="display: none;">
    <div class="bio-edit-modal-content">
        <form action="{{ route('user.updateBio') }}" method="POST" id="bioForm">
            @csrf
            @method('PATCH')
            <h3 class="bio-modal-title">Edit Bio</h3>
            <textarea name="bio" id="bioInput" rows="3" class="bio-textarea" placeholder="Tell us about yourself..." maxlength="300">{{ $user->bio ?? '' }}</textarea>
            <div class="bio-modal-footer">
                <span class="bio-counter"><span id="bioCounter">{{ strlen($user->bio ?? '') }}</span>/300</span>
                <div class="bio-actions">
                    <button type="button" class="btn-cancel-bio" id="cancelBioBtn">Cancel</button>
                    <button type="submit" class="btn-save-bio">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Predictions Section -->
<div class="predictions-section">
    <div class="predictions-container">
        <div class="feed-header">
            <h2 class="feed-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feed-icon">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Prediction History
            </h2>
            <span class="feed-count">{{ $recentPredictions->count() }} shown</span>
        </div>

        @if($recentPredictions->count() > 0)
            <div class="predictions-feed">
                @foreach ($recentPredictions as $prediction)
                    @php
                        $isPending = $prediction->accuracy === null;
                        $isActive = $prediction->is_active;
                        $isResolved = !$isActive && !$isPending;

                        $statusClass = $isPending ? 'status-pending' : ($isActive ? 'status-active' : 'status-resolved');
                        $statusLabel = $isPending ? 'Pending' : ($isActive ? 'Active' : 'Resolved');

                        $accuracyDisplay = $isPending ? 'Pending' : number_format($prediction->accuracy, 0) . '%';
                        $rawAccuracy = $prediction->accuracy;
                    @endphp
                    <div class="prediction-card {{ $statusClass }}"
                         data-prediction-id="{{ $prediction->prediction_id }}"
                         role="button"
                         tabindex="0"
                         aria-label="View prediction details for {{ $prediction->stock->symbol }}">
                        <!-- Status Indicator -->
                        <div class="prediction-status-bar"></div>

                        <!-- Card Header -->
                        <div class="prediction-card-header">
                            <div class="prediction-stock">
                                <span class="stock-symbol">${{ $prediction->stock->symbol }}</span>
                                <span class="prediction-type {{ $prediction->prediction_type === 'Bullish' ? 'type-bullish' : 'type-bearish' }}">
                                    {{ $prediction->prediction_type }}
                                </span>
                            </div>
                            <span class="prediction-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <!-- Card Body -->
                        <div class="prediction-card-body">
                            @if($prediction->target_price)
                                <div class="prediction-target">
                                    <span class="target-label">Target Price</span>
                                    <span class="target-value">${{ number_format($prediction->target_price, 2) }}</span>
                                </div>
                            @endif

                            <div class="prediction-meta">
                                @if($prediction->end_date)
                                    <span class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        {{ date('M d, Y', strtotime($prediction->end_date)) }}
                                    </span>
                                @endif

                                <span class="meta-item accuracy-display {{ $rawAccuracy !== null && $rawAccuracy >= 70 ? 'accuracy-high' : ($rawAccuracy !== null && $rawAccuracy >= 50 ? 'accuracy-medium' : 'accuracy-low') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    {{ $accuracyDisplay }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer with Votes -->
                        <div class="prediction-card-footer">
                            <span class="vote-display">
                                <span class="vote-up"><i class="bi bi-arrow-up"></i> {{ $prediction->upvotes ?? 0 }}</span>
                                <span class="vote-down"><i class="bi bi-arrow-down"></i> {{ $prediction->downvotes ?? 0 }}</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <h3 class="empty-title">No Predictions Yet</h3>
                <p class="empty-text">{{ $user->first_name }} hasn't made any predictions yet.</p>
            </div>
        @endif
    </div>
</div>

<!-- Back Button -->
<div class="profile-back">
    <a href="{{ url()->previous() }}" class="back-link">
        <i class="bi bi-arrow-left"></i> Go Back
    </a>
</div>

<!-- Prediction Detail Modal (Reddit-style overlay) -->
<div class="prediction-modal-overlay" id="predictionModal">
    <div class="prediction-modal-backdrop" id="modalBackdrop"></div>
    <div class="prediction-modal-container">
        <button class="prediction-modal-close" id="closeModal" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="prediction-modal-content" id="predictionModalContent">
            <!-- Content loaded dynamically via JavaScript -->
            <div class="modal-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading prediction...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
/* ==========================================================================
   Profile Page Styles - Theme-Aware Design
   Matches account page styling for consistency
   ========================================================================== */

/* Theme Variables */
:root {
    --profile-bg-primary: #ffffff;
    --profile-bg-secondary: #f9fafb;
    --profile-bg-tertiary: #f3f4f6;
    --profile-text-primary: #111827;
    --profile-text-secondary: #6b7280;
    --profile-text-muted: #9ca3af;
    --profile-border-color: #e5e7eb;
    --profile-accent: #10b981;
    --profile-accent-dark: #059669;
}

body.dark-mode {
    --profile-bg-primary: #1f1f1f;
    --profile-bg-secondary: #2a2a2a;
    --profile-bg-tertiary: #333333;
    --profile-text-primary: #f3f4f6;
    --profile-text-secondary: #9ca3af;
    --profile-text-muted: #6b7280;
    --profile-border-color: #404040;
}

/* ==========================================================================
   Profile Header
   ========================================================================== */
.profile-header-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.profile-header-banner {
    height: 160px;
    background: linear-gradient(135deg, var(--profile-accent) 0%, #3b82f6 100%);
    opacity: 0.85;
}

body.dark-mode .profile-header-banner {
    opacity: 0.7;
}

.profile-header-content {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    max-width: 800px;
    margin: -50px auto 0;
    padding: 0 1rem;
    position: relative;
    z-index: 10;
}

/* Profile Avatar */
.profile-avatar-wrapper {
    position: relative;
    flex-shrink: 0;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--profile-bg-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.profile-avatar-upload {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 28px;
    height: 28px;
    background: var(--profile-accent);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease;
}

.profile-avatar-upload:hover {
    transform: scale(1.1);
}

/* User Info */
.profile-user-info {
    flex: 1;
    padding-top: 55px;
    min-width: 0;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--profile-text-primary);
    margin: 0 0 0.25rem;
    line-height: 1.2;
}

.profile-username {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
    margin: 0 0 0.5rem;
}

.profile-bio {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
    margin: 0 0 0.5rem;
    line-height: 1.5;
    max-width: 400px;
}

.btn-edit-bio-inline {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--profile-text-secondary);
    background: transparent;
    border: 1px solid var(--profile-border-color);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-edit-bio-inline:hover {
    color: var(--profile-accent);
    border-color: var(--profile-accent);
}

/* Stats */
.profile-stats {
    display: flex;
    gap: 1.5rem;
    padding-top: 55px;
    flex-shrink: 0;
}

.profile-stats .stat-item {
    text-align: center;
}

.profile-stats .stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--profile-text-primary);
    line-height: 1.2;
}

.profile-stats .stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--profile-text-secondary);
    font-weight: 500;
    margin-top: 0.125rem;
}

/* ==========================================================================
   Bio Edit Modal (only shown for own profile)
   ========================================================================== */
.bio-edit-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.bio-edit-modal-content {
    background: var(--profile-bg-primary);
    border-radius: 0.75rem;
    padding: 1.5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.bio-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--profile-text-primary);
    margin: 0 0 1rem;
}

.bio-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--profile-border-color);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    background: var(--profile-bg-secondary);
    color: var(--profile-text-primary);
    transition: border-color 0.2s ease;
}

.bio-textarea:focus {
    outline: none;
    border-color: var(--profile-accent);
}

.bio-textarea::placeholder {
    color: var(--profile-text-muted);
}

.bio-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}

.bio-counter {
    font-size: 0.75rem;
    color: var(--profile-text-muted);
}

.bio-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-cancel-bio,
.btn-save-bio {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn-cancel-bio {
    background: var(--profile-bg-tertiary);
    color: var(--profile-text-primary);
}

.btn-cancel-bio:hover {
    background: var(--profile-border-color);
}

.btn-save-bio {
    background: var(--profile-accent);
    color: white;
}

.btn-save-bio:hover {
    background: var(--profile-accent-dark);
}

/* ==========================================================================
   Predictions Section
   ========================================================================== */
.predictions-section {
    padding: 0 1rem 2rem;
}

.predictions-container {
    max-width: 800px;
    margin: 0 auto;
}

.feed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--profile-border-color);
}

.feed-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--profile-text-primary);
    margin: 0;
}

.feed-icon {
    color: var(--profile-accent);
}

.feed-count {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
    font-weight: 500;
}

/* Predictions Feed */
.predictions-feed {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ==========================================================================
   Prediction Card
   ========================================================================== */
.prediction-card {
    display: block;
    background: var(--profile-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--profile-border-color);
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    text-decoration: none;
    color: inherit;
}

.prediction-card:hover {
    border-color: var(--profile-accent);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
}

body.dark-mode .prediction-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Status Bar */
.prediction-status-bar {
    height: 3px;
    width: 100%;
}

.status-pending .prediction-status-bar {
    background: #f59e0b;
}

.status-active .prediction-status-bar {
    background: #3b82f6;
}

.status-resolved .prediction-status-bar {
    background: var(--profile-accent);
}

/* Card Header */
.prediction-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1rem 0.75rem;
}

.prediction-stock {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stock-symbol {
    font-family: ui-monospace, SFMono-Regular, monospace;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--profile-text-primary);
}

.prediction-type {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.type-bullish {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

body.dark-mode .type-bullish {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

.type-bearish {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

body.dark-mode .type-bearish {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* Status Badge */
.prediction-status-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
}

.prediction-status-badge.status-pending {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
}

body.dark-mode .prediction-status-badge.status-pending {
    background: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

.prediction-status-badge.status-active {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
}

body.dark-mode .prediction-status-badge.status-active {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
}

.prediction-status-badge.status-resolved {
    background: rgba(16, 185, 129, 0.15);
    color: #059669;
}

body.dark-mode .prediction-status-badge.status-resolved {
    background: rgba(16, 185, 129, 0.2);
    color: #6ee7b7;
}

/* Card Body */
.prediction-card-body {
    padding: 0 1rem 0.75rem;
}

.prediction-target {
    display: flex;
    flex-direction: column;
    margin-bottom: 0.75rem;
}

.target-label {
    font-size: 0.6875rem;
    font-weight: 500;
    color: var(--profile-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.target-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--profile-text-primary);
}

.prediction-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--profile-text-secondary);
}

.meta-item svg {
    color: var(--profile-text-muted);
}

/* Accuracy Display */
.accuracy-display.accuracy-high {
    color: #059669;
}

body.dark-mode .accuracy-display.accuracy-high {
    color: #6ee7b7;
}

.accuracy-display.accuracy-medium {
    color: #d97706;
}

body.dark-mode .accuracy-display.accuracy-medium {
    color: #fbbf24;
}

.accuracy-display.accuracy-low {
    color: var(--profile-text-muted);
}

/* Card Footer */
.prediction-card-footer {
    display: flex;
    justify-content: flex-end;
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--profile-border-color);
}

.vote-display {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
}

.vote-display .vote-up {
    color: var(--profile-accent);
}

.vote-display .vote-down {
    color: #ef4444;
}

/* ==========================================================================
   Empty State
   ========================================================================== */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--profile-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--profile-border-color);
}

.empty-icon {
    color: var(--profile-text-muted);
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--profile-text-primary);
    margin: 0 0 0.5rem;
}

.empty-text {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
    margin: 0;
}

/* ==========================================================================
   Back Button
   ========================================================================== */
.profile-back {
    padding: 1rem;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
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
    color: var(--profile-accent);
}

/* ==========================================================================
   Expanded/Highlighted Prediction Card (from query param)
   ========================================================================== */
.prediction-card.expanded {
    border: 2px solid var(--profile-accent);
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(59, 130, 246, 0.05) 100%);
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.25);
    transform: scale(1.02);
    animation: pulse-highlight 2s ease-in-out;
}

body.dark-mode .prediction-card.expanded {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.1) 100%);
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
}

@keyframes pulse-highlight {
    0% {
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.25);
    }
    50% {
        box-shadow: 0 4px 30px rgba(16, 185, 129, 0.5);
    }
    100% {
        box-shadow: 0 4px 20px rgba(16, 185, 129, 0.25);
    }
}

.prediction-card.expanded .stock-symbol {
    color: var(--profile-accent);
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */
@media (max-width: 640px) {
    .profile-header-content {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 1rem;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
    }

    .profile-user-info {
        padding-top: 0;
    }

    .profile-bio {
        max-width: none;
    }

    .profile-stats {
        padding-top: 0;
        gap: 2rem;
    }

    .profile-header-banner {
        height: 120px;
    }

    .profile-header-content {
        margin-top: -40px;
    }

    .prediction-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .prediction-status-badge {
        align-self: flex-start;
    }
}

/* ==========================================================================
   Prediction Modal Overlay (Reddit-style)
   ========================================================================== */
.prediction-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.prediction-modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.prediction-modal-overlay.visible {
    opacity: 1;
}

.prediction-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

body.dark-mode .prediction-modal-backdrop {
    background: rgba(0, 0, 0, 0.85);
}

.prediction-modal-container {
    position: relative;
    width: 100%;
    max-width: 650px;
    max-height: 90vh;
    margin: 1rem;
    background: var(--profile-bg-primary);
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transform: translateY(20px) scale(0.95);
    transition: transform 0.3s ease;
}

.prediction-modal-overlay.visible .prediction-modal-container {
    transform: translateY(0) scale(1);
}

.prediction-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 10;
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 50%;
    background: var(--profile-bg-tertiary);
    color: var(--profile-text-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.prediction-modal-close:hover {
    background: var(--profile-accent);
    color: white;
    transform: scale(1.1);
}

.prediction-modal-content {
    padding: 1.5rem;
    overflow-y: auto;
    max-height: calc(90vh - 2rem);
}

/* Modal Loading State */
.modal-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    gap: 1rem;
    color: var(--profile-text-secondary);
}

/* Modal Prediction Card Styles */
.modal-prediction-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--profile-border-color);
}

.modal-user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.modal-user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--profile-accent);
}

.modal-user-details {
    display: flex;
    flex-direction: column;
}

.modal-user-name {
    font-weight: 600;
    color: var(--profile-text-primary);
    font-size: 1rem;
}

.modal-user-meta {
    font-size: 0.8125rem;
    color: var(--profile-text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close-hint {
    font-size: 0.75rem;
    color: var(--profile-text-muted);
}

.modal-stock-info {
    margin-bottom: 1rem;
}

.modal-stock-symbol {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--profile-text-primary);
    font-family: ui-monospace, SFMono-Regular, monospace;
}

.modal-company-name {
    color: var(--profile-text-secondary);
    font-size: 1rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.modal-prediction-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.modal-badge {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modal-badge-bullish {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.modal-badge-bearish {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.modal-price-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-price-label {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
}

.modal-price-value {
    font-weight: 700;
    font-size: 1rem;
}

.modal-price-target {
    color: var(--profile-accent);
}

.modal-price-current {
    color: #3b82f6;
}

.modal-reasoning {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--profile-bg-secondary);
    border-radius: 0.5rem;
    border-left: 3px solid var(--profile-accent);
}

.modal-reasoning-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--profile-text-muted);
    margin-bottom: 0.5rem;
}

.modal-reasoning-text {
    color: var(--profile-text-primary);
    line-height: 1.6;
    font-size: 0.9375rem;
}

.modal-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    background: var(--profile-bg-secondary);
    border-radius: 0.5rem;
}

.modal-meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
}

.modal-meta-item i {
    color: var(--profile-text-muted);
}

.modal-status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
}

.modal-accuracy {
    font-weight: 700;
}

.modal-accuracy-high {
    color: #059669;
}

.modal-accuracy-medium {
    color: #d97706;
}

.modal-accuracy-low {
    color: var(--profile-text-muted);
}

/* Modal Voting Section */
.modal-voting {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 0;
    border-top: 1px solid var(--profile-border-color);
    border-bottom: 1px solid var(--profile-border-color);
    margin-bottom: 1.5rem;
}

.modal-vote-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid transparent;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-vote-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-upvote-btn {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.modal-upvote-btn:hover:not(:disabled) {
    background: rgba(16, 185, 129, 0.2);
    border-color: #10b981;
}

.modal-upvote-btn.voted {
    background: rgba(16, 185, 129, 0.25);
    border-color: #10b981;
}

.modal-downvote-btn {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.modal-downvote-btn:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.2);
    border-color: #ef4444;
}

.modal-downvote-btn.voted {
    background: rgba(239, 68, 68, 0.25);
    border-color: #ef4444;
}

body.dark-mode .modal-upvote-btn {
    background: rgba(16, 185, 129, 0.15);
}

body.dark-mode .modal-downvote-btn {
    background: rgba(239, 68, 68, 0.15);
}

/* Modal Comments Section */
.modal-comments-section {
    margin-top: 1rem;
}

.modal-comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.modal-comments-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--profile-text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-comments-count {
    font-size: 0.875rem;
    color: var(--profile-text-secondary);
    font-weight: normal;
}

.modal-comment-form {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.modal-comment-input {
    flex: 1;
    padding: 0.625rem 1rem;
    border: 1px solid var(--profile-border-color);
    border-radius: 20px;
    font-size: 0.9375rem;
    background: var(--profile-bg-secondary);
    color: var(--profile-text-primary);
    transition: border-color 0.2s ease;
}

.modal-comment-input:focus {
    outline: none;
    border-color: var(--profile-accent);
}

.modal-comment-input::placeholder {
    color: var(--profile-text-muted);
}

.modal-comment-submit {
    padding: 0.625rem 1rem;
    border: none;
    border-radius: 20px;
    background: var(--profile-accent);
    color: white;
    cursor: pointer;
    transition: background 0.2s ease;
}

.modal-comment-submit:hover {
    background: var(--profile-accent-dark);
}

.modal-comment-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-comments-list {
    max-height: 300px;
    overflow-y: auto;
}

.modal-comment-item {
    padding: 0.75rem;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    background: var(--profile-bg-secondary);
    border-left: 3px solid var(--profile-border-color);
}

.modal-comment-item:hover {
    background: var(--profile-bg-tertiary);
}

.modal-comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.375rem;
}

.modal-comment-author {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--profile-text-primary);
}

.modal-comment-date {
    font-size: 0.75rem;
    color: var(--profile-text-muted);
}

.modal-comment-content {
    font-size: 0.9375rem;
    color: var(--profile-text-primary);
    line-height: 1.5;
}

.modal-comment-actions {
    margin-top: 0.5rem;
}

.modal-reply-btn {
    background: none;
    border: none;
    font-size: 0.8125rem;
    color: var(--profile-text-muted);
    cursor: pointer;
    padding: 0;
}

.modal-reply-btn:hover {
    color: #3b82f6;
}

.modal-reply-form {
    display: none;
    margin-top: 0.5rem;
}

.modal-reply-form.show {
    display: flex;
    gap: 0.5rem;
}

.modal-reply-item {
    margin-left: 1.5rem;
    padding-left: 0.75rem;
    border-left: 2px solid rgba(59, 130, 246, 0.3);
}

.modal-no-comments {
    text-align: center;
    padding: 2rem;
    color: var(--profile-text-muted);
    font-size: 0.9375rem;
}

.modal-login-prompt {
    text-align: center;
    padding: 1rem;
    color: var(--profile-text-secondary);
    font-size: 0.9375rem;
}

.modal-login-prompt a {
    color: var(--profile-accent);
    font-weight: 500;
}

/* Modal responsive */
@media (max-width: 640px) {
    .prediction-modal-container {
        margin: 0;
        max-height: 100vh;
        border-radius: 0;
    }

    .prediction-modal-content {
        max-height: 100vh;
        padding: 1rem;
        padding-top: 3.5rem;
    }

    .modal-prediction-header {
        flex-direction: column;
        gap: 1rem;
    }

    .modal-prediction-details {
        flex-direction: column;
    }

    .modal-voting {
        flex-wrap: wrap;
    }
}

/* Prevent body scroll when modal is open */
body.modal-open {
    overflow: hidden;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========================================================================
    // Modal State & Elements
    // ========================================================================
    const modal = document.getElementById('predictionModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalContent = document.getElementById('predictionModalContent');
    const closeModalBtn = document.getElementById('closeModal');
    const userId = {{ $user->id }};
    const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let currentPredictionId = null;
    let originalUrl = window.location.href;
    let isModalNavigation = false;

    // ========================================================================
    // Modal Open/Close Functions
    // ========================================================================
    function openModal(predictionId) {
        currentPredictionId = predictionId;
        modal.classList.add('active');
        document.body.classList.add('modal-open');

        // Animate in
        requestAnimationFrame(() => {
            modal.classList.add('visible');
        });

        // Update URL with pushState
        const newUrl = `/profile/${userId}?prediction=${predictionId}`;
        if (window.location.href !== window.location.origin + newUrl) {
            history.pushState(
                { modal: true, predictionId: predictionId },
                '',
                newUrl
            );
        }

        // Load prediction data
        loadPredictionDetails(predictionId);
    }

    function closeModal(updateHistory = true) {
        modal.classList.remove('visible');

        setTimeout(() => {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
            currentPredictionId = null;

            // Reset modal content to loading state
            modalContent.innerHTML = `
                <div class="modal-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading prediction...</p>
                </div>
            `;
        }, 300);

        // Update URL back to profile (only if not navigating via browser buttons)
        if (updateHistory && !isModalNavigation) {
            history.pushState(
                { modal: false },
                '',
                `/profile/${userId}`
            );
        }
        isModalNavigation = false;
    }

    // ========================================================================
    // Load Prediction Details
    // ========================================================================
    async function loadPredictionDetails(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/details`);
            const result = await response.json();

            if (result.success) {
                renderModalContent(result.data);
                loadComments(predictionId);
            } else {
                modalContent.innerHTML = `
                    <div class="modal-loading">
                        <i class="bi bi-exclamation-circle" style="font-size: 2rem; color: #ef4444;"></i>
                        <p>Error loading prediction: ${result.message || 'Unknown error'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading prediction:', error);
            modalContent.innerHTML = `
                <div class="modal-loading">
                    <i class="bi bi-exclamation-circle" style="font-size: 2rem; color: #ef4444;"></i>
                    <p>Failed to load prediction details</p>
                </div>
            `;
        }
    }

    // ========================================================================
    // Render Modal Content
    // ========================================================================
    function renderModalContent(data) {
        const isBullish = data.prediction_type.toLowerCase() === 'bullish';
        const profilePicture = data.user.profile_picture
            ? `/images/profile_pictures/${data.user.profile_picture}`
            : '/images/default.png';

        // Status calculation
        let statusClass = 'bg-secondary';
        let statusText = 'Inactive';
        if (data.is_active) {
            if (data.end_date && new Date(data.end_date) > new Date()) {
                statusClass = 'bg-success';
                statusText = 'Active';
            } else {
                statusClass = 'bg-warning text-dark';
                statusText = 'Expired';
            }
        }

        // Accuracy class
        let accuracyClass = 'modal-accuracy-low';
        if (data.accuracy !== null) {
            if (data.accuracy >= 70) accuracyClass = 'modal-accuracy-high';
            else if (data.accuracy >= 50) accuracyClass = 'modal-accuracy-medium';
        }

        // Format dates
        const predictionDate = data.prediction_date ? new Date(data.prediction_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
        const endDate = data.end_date ? new Date(data.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';

        modalContent.innerHTML = `
            <!-- Header with user info -->
            <div class="modal-prediction-header">
                <div class="modal-user-info">
                    <img src="${profilePicture}" alt="${escapeHtml(data.user.first_name)}" class="modal-user-avatar">
                    <div class="modal-user-details">
                        <span class="modal-user-name">${escapeHtml(data.user.first_name)} ${escapeHtml(data.user.last_name || '')}</span>
                        <span class="modal-user-meta">
                            <i class="bi bi-star-fill text-warning"></i>
                            ${data.user.reputation_score} pts
                            ${predictionDate ? `<span>• ${predictionDate}</span>` : ''}
                        </span>
                    </div>
                </div>
                <span class="modal-close-hint">Press ESC to close</span>
            </div>

            <!-- Stock Info -->
            <div class="modal-stock-info">
                <span class="modal-stock-symbol">$${escapeHtml(data.stock.symbol)}</span>
                ${data.stock.company_name ? `<span class="modal-company-name">${escapeHtml(data.stock.company_name)}</span>` : ''}
            </div>

            <!-- Prediction Details -->
            <div class="modal-prediction-details">
                <span class="modal-badge ${isBullish ? 'modal-badge-bullish' : 'modal-badge-bearish'}">
                    <i class="bi bi-${isBullish ? 'arrow-up' : 'arrow-down'}-circle-fill me-1"></i>
                    ${data.prediction_type}
                </span>
                ${data.target_price ? `
                    <div class="modal-price-info">
                        <span class="modal-price-label">Target:</span>
                        <span class="modal-price-value modal-price-target">$${parseFloat(data.target_price).toFixed(2)}</span>
                    </div>
                ` : ''}
                ${data.stock.current_price ? `
                    <div class="modal-price-info">
                        <span class="modal-price-label">Current:</span>
                        <span class="modal-price-value modal-price-current">$${parseFloat(data.stock.current_price).toFixed(2)}</span>
                    </div>
                ` : ''}
            </div>

            <!-- Reasoning -->
            ${data.reasoning ? `
                <div class="modal-reasoning">
                    <div class="modal-reasoning-title">Reasoning</div>
                    <div class="modal-reasoning-text">${escapeHtml(data.reasoning)}</div>
                </div>
            ` : ''}

            <!-- Meta info -->
            <div class="modal-meta">
                ${endDate ? `
                    <span class="modal-meta-item">
                        <i class="bi bi-calendar3"></i>
                        ${data.is_active && new Date(data.end_date) > new Date() ? 'Ends' : 'Ended'} ${endDate}
                    </span>
                ` : ''}
                <span class="modal-meta-item">
                    <span class="badge modal-status-badge ${statusClass}">${statusText}</span>
                </span>
                ${data.accuracy !== null ? `
                    <span class="modal-meta-item">
                        <i class="bi bi-bullseye"></i>
                        <span class="modal-accuracy ${accuracyClass}">${parseFloat(data.accuracy).toFixed(1)}%</span> accuracy
                    </span>
                ` : ''}
            </div>

            <!-- Voting Section -->
            <div class="modal-voting" id="modal-voting-${data.prediction_id}">
                <button class="modal-vote-btn modal-upvote-btn ${data.user_vote === 'upvote' ? 'voted' : ''}"
                        data-action="upvote"
                        data-prediction-id="${data.prediction_id}"
                        ${!isLoggedIn ? 'disabled' : ''}>
                    <i class="bi bi-arrow-up-circle-fill"></i>
                    <span class="vote-count" id="modal-upvotes-${data.prediction_id}">${data.upvotes}</span>
                </button>
                <button class="modal-vote-btn modal-downvote-btn ${data.user_vote === 'downvote' ? 'voted' : ''}"
                        data-action="downvote"
                        data-prediction-id="${data.prediction_id}"
                        ${!isLoggedIn ? 'disabled' : ''}>
                    <i class="bi bi-arrow-down-circle-fill"></i>
                    <span class="vote-count" id="modal-downvotes-${data.prediction_id}">${data.downvotes}</span>
                </button>
                ${!isLoggedIn ? '<span style="color: var(--profile-text-muted); font-size: 0.875rem;">Log in to vote</span>' : ''}
            </div>

            <!-- Comments Section -->
            <div class="modal-comments-section" id="modal-comments-section-${data.prediction_id}">
                <div class="modal-comments-header">
                    <h4 class="modal-comments-title">
                        <i class="bi bi-chat-dots"></i>
                        Comments
                        <span class="modal-comments-count" id="modal-comments-count-${data.prediction_id}">(${data.comments_count})</span>
                    </h4>
                </div>

                ${isLoggedIn ? `
                    <div class="modal-comment-form">
                        <input type="text"
                               class="modal-comment-input"
                               id="modal-comment-input-${data.prediction_id}"
                               placeholder="Add a comment..."
                               maxlength="500">
                        <button class="modal-comment-submit"
                                id="modal-comment-submit-${data.prediction_id}"
                                data-prediction-id="${data.prediction_id}">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                ` : `
                    <div class="modal-login-prompt">
                        <a href="/login">Log in</a> to join the discussion.
                    </div>
                `}

                <div class="modal-comments-list" id="modal-comments-list-${data.prediction_id}">
                    <div class="modal-loading" style="padding: 1rem;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span style="margin-left: 0.5rem;">Loading comments...</span>
                    </div>
                </div>
            </div>
        `;

        // Attach vote button handlers
        attachVoteHandlers(data.prediction_id);

        // Attach comment handlers
        if (isLoggedIn) {
            attachCommentHandlers(data.prediction_id);
        }
    }

    // ========================================================================
    // Voting Functionality
    // ========================================================================
    function attachVoteHandlers(predictionId) {
        const votingSection = document.getElementById(`modal-voting-${predictionId}`);
        if (!votingSection) return;

        const voteButtons = votingSection.querySelectorAll('.modal-vote-btn');
        voteButtons.forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!isLoggedIn) return;

                const action = this.dataset.action;
                const pid = this.dataset.predictionId;

                try {
                    const response = await fetch(`/predictions/vote/${pid}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            vote_type: action,
                            prediction_id: pid
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Update vote counts
                        updateVoteCounts(pid);
                        // Toggle visual state
                        toggleVoteState(this, action);
                    } else {
                        console.error('Vote failed:', result.message);
                    }
                } catch (error) {
                    console.error('Error voting:', error);
                }
            });
        });
    }

    async function updateVoteCounts(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/vote-counts`);
            const data = await response.json();

            if (data.success) {
                const upvotesEl = document.getElementById(`modal-upvotes-${predictionId}`);
                const downvotesEl = document.getElementById(`modal-downvotes-${predictionId}`);

                if (upvotesEl) upvotesEl.textContent = data.upvotes;
                if (downvotesEl) downvotesEl.textContent = data.downvotes;

                // Also update the card in the background
                const card = document.querySelector(`.prediction-card[data-prediction-id="${predictionId}"]`);
                if (card) {
                    const cardUpvotes = card.querySelector('.vote-up');
                    const cardDownvotes = card.querySelector('.vote-down');
                    if (cardUpvotes) cardUpvotes.innerHTML = `<i class="bi bi-arrow-up"></i> ${data.upvotes}`;
                    if (cardDownvotes) cardDownvotes.innerHTML = `<i class="bi bi-arrow-down"></i> ${data.downvotes}`;
                }
            }
        } catch (error) {
            console.error('Error updating vote counts:', error);
        }
    }

    function toggleVoteState(button, action) {
        const votingSection = button.closest('.modal-voting');
        const upvoteBtn = votingSection.querySelector('.modal-upvote-btn');
        const downvoteBtn = votingSection.querySelector('.modal-downvote-btn');

        if (action === 'upvote') {
            upvoteBtn.classList.toggle('voted');
            downvoteBtn.classList.remove('voted');
        } else {
            downvoteBtn.classList.toggle('voted');
            upvoteBtn.classList.remove('voted');
        }
    }

    // ========================================================================
    // Comments Functionality
    // ========================================================================
    async function loadComments(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/comments`);
            const result = await response.json();

            const commentsList = document.getElementById(`modal-comments-list-${predictionId}`);
            if (!commentsList) return;

            if (result.success && result.data.length > 0) {
                commentsList.innerHTML = result.data.map(comment => renderComment(comment, predictionId)).join('');
            } else {
                commentsList.innerHTML = `
                    <div class="modal-no-comments">
                        <i class="bi bi-chat-square-text"></i>
                        <p>No comments yet. Be the first to share your thoughts!</p>
                    </div>
                `;
            }

            // Update count
            const countEl = document.getElementById(`modal-comments-count-${predictionId}`);
            if (countEl) {
                countEl.textContent = `(${result.data ? result.data.length : 0})`;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            const commentsList = document.getElementById(`modal-comments-list-${predictionId}`);
            if (commentsList) {
                commentsList.innerHTML = `<div class="modal-no-comments text-danger">Error loading comments</div>`;
            }
        }
    }

    function renderComment(comment, predictionId, isReply = false) {
        const replyClass = isReply ? 'modal-reply-item' : '';
        let html = `
            <div class="modal-comment-item ${replyClass}" data-comment-id="${comment.comment_id}">
                <div class="modal-comment-header">
                    <span class="modal-comment-author">${escapeHtml(comment.user.name)}</span>
                    <span class="modal-comment-date">${comment.created_at}</span>
                </div>
                <div class="modal-comment-content">${escapeHtml(comment.content)}</div>
                ${isLoggedIn && !isReply ? `
                    <div class="modal-comment-actions">
                        <button class="modal-reply-btn" data-comment-id="${comment.comment_id}" data-prediction-id="${predictionId}">
                            <i class="bi bi-reply"></i> Reply
                        </button>
                    </div>
                    <div class="modal-reply-form" id="modal-reply-form-${comment.comment_id}">
                        <input type="text"
                               class="modal-comment-input"
                               id="modal-reply-input-${comment.comment_id}"
                               placeholder="Write a reply..."
                               maxlength="500">
                        <button class="modal-comment-submit modal-reply-submit"
                                data-comment-id="${comment.comment_id}"
                                data-prediction-id="${predictionId}">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                ` : ''}
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

    function attachCommentHandlers(predictionId) {
        // Main comment submit
        const submitBtn = document.getElementById(`modal-comment-submit-${predictionId}`);
        const input = document.getElementById(`modal-comment-input-${predictionId}`);

        if (submitBtn && input) {
            submitBtn.addEventListener('click', () => submitComment(predictionId, input));
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitComment(predictionId, input);
                }
            });
        }

        // Use event delegation for reply buttons (they're rendered dynamically)
        const commentsSection = document.getElementById(`modal-comments-section-${predictionId}`);
        if (commentsSection) {
            commentsSection.addEventListener('click', function(e) {
                // Reply toggle
                if (e.target.closest('.modal-reply-btn')) {
                    const btn = e.target.closest('.modal-reply-btn');
                    const commentId = btn.dataset.commentId;
                    const replyForm = document.getElementById(`modal-reply-form-${commentId}`);
                    if (replyForm) {
                        replyForm.classList.toggle('show');
                        if (replyForm.classList.contains('show')) {
                            const replyInput = document.getElementById(`modal-reply-input-${commentId}`);
                            if (replyInput) replyInput.focus();
                        }
                    }
                }

                // Reply submit
                if (e.target.closest('.modal-reply-submit')) {
                    const btn = e.target.closest('.modal-reply-submit');
                    const commentId = btn.dataset.commentId;
                    const pid = btn.dataset.predictionId;
                    const replyInput = document.getElementById(`modal-reply-input-${commentId}`);
                    if (replyInput) {
                        submitReply(pid, commentId, replyInput);
                    }
                }
            });

            // Reply input enter key
            commentsSection.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && e.target.id && e.target.id.startsWith('modal-reply-input-')) {
                    e.preventDefault();
                    const commentId = e.target.id.replace('modal-reply-input-', '');
                    const submitBtn = commentsSection.querySelector(`.modal-reply-submit[data-comment-id="${commentId}"]`);
                    if (submitBtn) {
                        const pid = submitBtn.dataset.predictionId;
                        submitReply(pid, commentId, e.target);
                    }
                }
            });
        }
    }

    async function submitComment(predictionId, input) {
        const content = input.value.trim();
        if (!content) return;

        try {
            const formData = new FormData();
            formData.append('prediction_id', predictionId);
            formData.append('content', content);

            const response = await fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                input.value = '';
                loadComments(predictionId);
            } else {
                alert(result.message || 'Error posting comment');
            }
        } catch (error) {
            console.error('Error posting comment:', error);
            alert('Error posting comment');
        }
    }

    async function submitReply(predictionId, parentCommentId, input) {
        const content = input.value.trim();
        if (!content) return;

        try {
            const formData = new FormData();
            formData.append('prediction_id', predictionId);
            formData.append('content', content);
            formData.append('parent_comment_id', parentCommentId);

            const response = await fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                input.value = '';
                const replyForm = document.getElementById(`modal-reply-form-${parentCommentId}`);
                if (replyForm) replyForm.classList.remove('show');
                loadComments(predictionId);
            } else {
                alert(result.message || 'Error posting reply');
            }
        } catch (error) {
            console.error('Error posting reply:', error);
            alert('Error posting reply');
        }
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================

    // Click on prediction cards to open modal
    document.querySelectorAll('.prediction-card[data-prediction-id]').forEach(card => {
        card.addEventListener('click', function(e) {
            const predictionId = this.dataset.predictionId;
            if (predictionId) {
                openModal(predictionId);
            }
        });

        // Also handle keyboard navigation (Enter/Space)
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const predictionId = this.dataset.predictionId;
                if (predictionId) {
                    openModal(predictionId);
                }
            }
        });
    });

    // Close modal on backdrop click
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', () => closeModal());
    }

    // Close modal on close button click
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => closeModal());
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(e) {
        isModalNavigation = true;

        if (e.state && e.state.modal && e.state.predictionId) {
            // Opening modal via back/forward
            if (!modal.classList.contains('active')) {
                openModal(e.state.predictionId);
            }
        } else {
            // Closing modal via back button
            if (modal.classList.contains('active')) {
                closeModal(false);
            }
        }
    });

    // ========================================================================
    // Check for prediction query parameter on page load (for direct URL access)
    // ========================================================================
    const urlParams = new URLSearchParams(window.location.search);
    const predictionIdParam = urlParams.get('prediction');

    if (predictionIdParam) {
        // Open modal for the prediction
        openModal(predictionIdParam);

        // Also highlight the card
        const targetCard = document.querySelector(`.prediction-card[data-prediction-id="${predictionIdParam}"]`);
        if (targetCard) {
            targetCard.classList.add('expanded');
        }
    }

    // ========================================================================
    // Bio edit modal functionality (only for own profile)
    // ========================================================================
    const editBioBtn = document.getElementById('editBioBtn');
    const cancelBioBtn = document.getElementById('cancelBioBtn');
    const bioEditModal = document.getElementById('bioEditModal');
    const bioInput = document.getElementById('bioInput');
    const bioCounter = document.getElementById('bioCounter');

    if (editBioBtn && bioEditModal) {
        editBioBtn.addEventListener('click', function() {
            bioEditModal.style.display = 'flex';
            if (bioInput) bioInput.focus();
        });
    }

    if (cancelBioBtn && bioEditModal) {
        cancelBioBtn.addEventListener('click', function() {
            bioEditModal.style.display = 'none';
        });
    }

    if (bioEditModal) {
        bioEditModal.addEventListener('click', function(e) {
            if (e.target === bioEditModal) {
                bioEditModal.style.display = 'none';
            }
        });
    }

    if (bioInput && bioCounter) {
        bioInput.addEventListener('input', function() {
            bioCounter.textContent = this.value.length;
        });
    }

    // ========================================================================
    // Utility Functions
    // ========================================================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
