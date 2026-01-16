@extends('layouts.app')

@section('title', 'Sovest - {{ $Curruser["full_name"] }}')

@section('content')
@php
    $profilePicture = $Curruser['profile_picture']
        ? asset('images/profile_pictures/' . $Curruser['profile_picture'])
        : asset('images/default.png');
@endphp

<!-- Restrained Profile Header -->
<div class="profile-header-wrapper">
    <div class="profile-header-banner"></div>

    <div class="profile-header-content">
        <!-- Profile Picture -->
        <div class="profile-avatar-wrapper">
            <img src="{{ $profilePicture }}" class="profile-avatar" alt="Profile Picture">
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
        </div>

        <!-- User Info -->
        <div class="profile-user-info">
            <h1 class="profile-name">{{ $Curruser['full_name'] }}</h1>
            <p class="profile-username">{{ $Curruser['username'] }}</p>

            <!-- Bio - Supporting role, inline with username -->
            @if($Curruser['bio'])
                <p class="profile-bio">{{ $Curruser['bio'] }}</p>
            @endif

            <button type="button" class="btn-edit-bio-inline" id="editBioBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                {{ $Curruser['bio'] ? 'Edit bio' : 'Add bio' }}
            </button>
        </div>

        <!-- Stats - Primary Focus -->
        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-value">{{ $userStats['total_predictions'] ?? 0 }}</span>
                <span class="stat-label">Predictions</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ number_format($Curruser['avg_accuracy'] ?? 0, 1) }}%</span>
                <span class="stat-label">Accuracy</span>
            </div>
            <div class="stat-item">
                <span class="stat-value">{{ $Curruser['reputation_score'] ?? 0 }}</span>
                <span class="stat-label">Reputation</span>
            </div>
        </div>
    </div>
</div>

<!-- Bio Edit Modal -->
<div class="bio-edit-modal" id="bioEditModal" style="display: none;">
    <div class="bio-edit-modal-content">
        <form action="{{ route('user.updateBio') }}" method="POST" id="bioForm">
            @csrf
            @method('PATCH')
            <h3 class="bio-modal-title">Edit Bio</h3>
            <textarea name="bio" id="bioInput" rows="3" class="bio-textarea" placeholder="Tell us about yourself..." maxlength="300">{{ $Curruser['bio'] ?? '' }}</textarea>
            <div class="bio-modal-footer">
                <span class="bio-counter"><span id="bioCounter">{{ strlen($Curruser['bio'] ?? '') }}</span>/300</span>
                <div class="bio-actions">
                    <button type="button" class="btn-cancel-bio" id="cancelBioBtn">Cancel</button>
                    <button type="submit" class="btn-save-bio">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Predictions Feed Section - Visual Focal Point -->
<div class="predictions-section">
    <div class="predictions-container">
        <div class="feed-header">
            <h2 class="feed-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feed-icon">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Prediction History
            </h2>
            <span class="feed-count">{{ count($Curruser['predictions']) }} total</span>
        </div>

        @if(count($Curruser['predictions']) > 0)
            <div class="predictions-feed">
                @foreach ($Curruser['predictions'] as $index => $prediction)
                    @php
                        // Determine prediction status for styling
                        $isPending = $prediction['accuracy'] === 'Pending';
                        $isActive = $prediction['is_active'];
                        $isResolved = !$isActive && !$isPending;

                        $statusClass = $isPending ? 'status-pending' : ($isActive ? 'status-active' : 'status-resolved');
                        $statusLabel = $isPending ? 'Pending' : ($isActive ? 'Active' : 'Resolved');
                    @endphp
                    <div class="prediction-card {{ $statusClass }}">
                        <!-- Status Indicator -->
                        <div class="prediction-status-bar"></div>

                        <!-- Card Header -->
                        <div class="prediction-card-header">
                            <div class="prediction-stock">
                                <span class="stock-symbol">${{ $prediction['symbol'] }}</span>
                                <span class="prediction-type {{ $prediction['prediction'] === 'bullish' ? 'type-bullish' : 'type-bearish' }}">
                                    {{ ucfirst($prediction['prediction']) }}
                                </span>
                            </div>
                            <span class="prediction-status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>

                        <!-- Card Body -->
                        <div class="prediction-card-body">
                            @if(isset($prediction['target_price']) && $prediction['target_price'])
                                <div class="prediction-target">
                                    <span class="target-label">Target Price</span>
                                    <span class="target-value">${{ number_format($prediction['target_price'], 2) }}</span>
                                </div>
                            @endif

                            <div class="prediction-meta">
                                @if(isset($prediction['end_date']))
                                    <span class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        {{ date('M d, Y', strtotime($prediction['end_date'])) }}
                                    </span>
                                @endif

                                <span class="meta-item accuracy-display {{ isset($prediction['raw_accuracy']) && $prediction['raw_accuracy'] >= 70 ? 'accuracy-high' : (isset($prediction['raw_accuracy']) && $prediction['raw_accuracy'] >= 50 ? 'accuracy-medium' : 'accuracy-low') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    {{ $prediction['accuracy'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="prediction-card-actions">
                            <button class="action-btn" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                Comment
                            </button>
                            <button class="action-btn" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                                    <polyline points="16 6 12 2 8 6"></polyline>
                                    <line x1="12" y1="2" x2="12" y2="15"></line>
                                </svg>
                                Share
                            </button>
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
                <p class="empty-text">Start making predictions to build your track record.</p>
                <a href="{{ route('predictions.create') }}" class="btn btn-primary">Make a Prediction</a>
            </div>
        @endif
    </div>
</div>

@endsection

@section('styles')
<style>
/* ==========================================================================
   Account Page Styles - Theme-Aware Design
   Uses CSS custom properties for consistent light/dark mode support
   ========================================================================== */

/* Theme Variables */
:root {
    --account-bg-primary: #ffffff;
    --account-bg-secondary: #f9fafb;
    --account-bg-tertiary: #f3f4f6;
    --account-text-primary: #111827;
    --account-text-secondary: #6b7280;
    --account-text-muted: #9ca3af;
    --account-border-color: #e5e7eb;
    --account-accent: #10b981;
    --account-accent-dark: #059669;
}

body.dark-mode {
    --account-bg-primary: #1f1f1f;
    --account-bg-secondary: #2a2a2a;
    --account-bg-tertiary: #333333;
    --account-text-primary: #f3f4f6;
    --account-text-secondary: #9ca3af;
    --account-text-muted: #6b7280;
    --account-border-color: #404040;
}

/* ==========================================================================
   Profile Header - Restrained, Professional
   ========================================================================== */
.profile-header-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.profile-header-banner {
    height: 100px;
    background: linear-gradient(135deg, var(--account-accent) 0%, #3b82f6 100%);
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
    border: 4px solid var(--account-bg-primary);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.profile-avatar-upload {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 28px;
    height: 28px;
    background: var(--account-accent);
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
    color: var(--account-text-primary);
    margin: 0 0 0.25rem;
    line-height: 1.2;
}

.profile-username {
    font-size: 0.875rem;
    color: var(--account-text-secondary);
    margin: 0 0 0.5rem;
}

.profile-bio {
    font-size: 0.875rem;
    color: var(--account-text-secondary);
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
    color: var(--account-text-secondary);
    background: transparent;
    border: 1px solid var(--account-border-color);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-edit-bio-inline:hover {
    color: var(--account-accent);
    border-color: var(--account-accent);
}

/* Stats - Primary Focus */
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
    color: var(--account-text-primary);
    line-height: 1.2;
}

.profile-stats .stat-label {
    display: block;
    font-size: 0.75rem;
    color: var(--account-text-secondary);
    font-weight: 500;
    margin-top: 0.125rem;
}

/* ==========================================================================
   Bio Edit Modal
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
    background: var(--account-bg-primary);
    border-radius: 0.75rem;
    padding: 1.5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.bio-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--account-text-primary);
    margin: 0 0 1rem;
}

.bio-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--account-border-color);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    background: var(--account-bg-secondary);
    color: var(--account-text-primary);
    transition: border-color 0.2s ease;
}

.bio-textarea:focus {
    outline: none;
    border-color: var(--account-accent);
}

.bio-textarea::placeholder {
    color: var(--account-text-muted);
}

.bio-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}

.bio-counter {
    font-size: 0.75rem;
    color: var(--account-text-muted);
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
    background: var(--account-bg-tertiary);
    color: var(--account-text-primary);
}

.btn-cancel-bio:hover {
    background: var(--account-border-color);
}

.btn-save-bio {
    background: var(--account-accent);
    color: white;
}

.btn-save-bio:hover {
    background: var(--account-accent-dark);
}

/* ==========================================================================
   Predictions Section - Visual Focal Point
   ========================================================================== */
.predictions-section {
    padding: 0 1rem 3rem;
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
    border-bottom: 1px solid var(--account-border-color);
}

.feed-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--account-text-primary);
    margin: 0;
}

.feed-icon {
    color: var(--account-accent);
}

.feed-count {
    font-size: 0.875rem;
    color: var(--account-text-secondary);
    font-weight: 500;
}

/* Predictions Feed */
.predictions-feed {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ==========================================================================
   Prediction Card - Status Distinction
   ========================================================================== */
.prediction-card {
    background: var(--account-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--account-border-color);
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.prediction-card:hover {
    border-color: var(--account-accent);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

body.dark-mode .prediction-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

/* Status Bar - Visual Distinction */
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
    background: var(--account-accent);
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
    color: var(--account-text-primary);
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
    color: var(--account-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.target-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--account-text-primary);
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
    color: var(--account-text-secondary);
}

.meta-item svg {
    color: var(--account-text-muted);
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
    color: var(--account-text-muted);
}

/* Card Actions */
.prediction-card-actions {
    display: flex;
    border-top: 1px solid var(--account-border-color);
}

.prediction-card-actions .action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.75rem;
    background: transparent;
    border: none;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--account-text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}

.prediction-card-actions .action-btn:first-child {
    border-right: 1px solid var(--account-border-color);
}

.prediction-card-actions .action-btn:hover {
    background: var(--account-bg-secondary);
    color: var(--account-accent);
}

/* ==========================================================================
   Empty State
   ========================================================================== */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--account-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--account-border-color);
}

.empty-icon {
    color: var(--account-text-muted);
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--account-text-primary);
    margin: 0 0 0.5rem;
}

.empty-text {
    font-size: 0.875rem;
    color: var(--account-text-secondary);
    margin: 0 0 1.5rem;
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
        height: 80px;
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
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBioBtn = document.getElementById('editBioBtn');
    const cancelBioBtn = document.getElementById('cancelBioBtn');
    const bioEditModal = document.getElementById('bioEditModal');
    const bioInput = document.getElementById('bioInput');
    const bioCounter = document.getElementById('bioCounter');

    // Open bio modal
    if (editBioBtn && bioEditModal) {
        editBioBtn.addEventListener('click', function() {
            bioEditModal.style.display = 'flex';
            if (bioInput) bioInput.focus();
        });
    }

    // Close bio modal
    if (cancelBioBtn && bioEditModal) {
        cancelBioBtn.addEventListener('click', function() {
            bioEditModal.style.display = 'none';
        });
    }

    // Close modal on backdrop click
    if (bioEditModal) {
        bioEditModal.addEventListener('click', function(e) {
            if (e.target === bioEditModal) {
                bioEditModal.style.display = 'none';
            }
        });
    }

    // Character counter
    if (bioInput && bioCounter) {
        bioInput.addEventListener('input', function() {
            bioCounter.textContent = this.value.length;
        });
    }
});
</script>
@endsection