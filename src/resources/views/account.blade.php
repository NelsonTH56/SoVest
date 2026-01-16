@extends('layouts.app')

@section('title', 'Sovest - {{ $Curruser["full_name"] }}')

@section('content')
@php
    $profilePicture = $Curruser['profile_picture']
        ? asset('images/profile_pictures/' . $Curruser['profile_picture'])
        : asset('images/default.png');
@endphp

<!-- Modern Profile Header with Cover -->
<div class="profile-cover-wrapper">
    <div class="profile-cover-gradient"></div>

    <div class="container profile-content-wrapper">
        <!-- Profile Card -->
        <div class="profile-card animate-fade-in">
            <div class="profile-header-content">
                <!-- Profile Picture Section -->
                <div class="profile-picture-section">
                    <div class="profile-picture-wrapper">
                        <img src="{{ $profilePicture }}" class="profile-picture-main" alt="Profile Picture">
                        <form action="{{ route('user.profile.uploadPhoto') }}" method="POST" enctype="multipart/form-data" id="photoUploadForm">
                            @csrf
                            <label class="profile-picture-overlay">
                                <svg xmlns="http://www.w3.org/2000/svg" class="camera-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Change Photo</span>
                                <input type="file" name="profile_picture" onchange="this.form.submit()" hidden accept="image/*">
                            </label>
                        </form>
                    </div>
                </div>

                <!-- Profile Info Section -->
                <div class="profile-info-section">
                    <h1 class="profile-name gradient-text">{{ $Curruser['full_name'] }}</h1>
                    <p class="profile-username">{{ $Curruser['username'] }}</p>

                    <!-- Stats Row -->
                    <div class="profile-stats-row">
                        <div class="stat-item">
                            <div class="stat-value">{{ $userStats['total_predictions'] ?? 0 }}</div>
                            <div class="stat-label">Predictions</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-value">{{ number_format($Curruser['avg_accuracy'] ?? 0, 1) }}%</div>
                            <div class="stat-label">Accuracy</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $Curruser['reputation_score'] ?? 0 }}</div>
                            <div class="stat-label">Reputation</div>
                        </div>
                    </div>

                    <!-- Bio Section -->
                    <div class="bio-section-modern">
                        <form action="{{ route('user.updateBio') }}" method="POST" id="bioForm">
                            @csrf
                            @method('PATCH')

                            <div class="bio-display" id="bioDisplay">
                                <p class="bio-text">{{ $Curruser['bio'] ?? 'Share something about yourself...' }}</p>
                                <button type="button" class="btn-edit-bio" id="editBioBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    Edit Bio
                                </button>
                            </div>

                            <div class="bio-edit-form" id="bioEditForm" style="display: none;">
                                <textarea name="bio" id="bioInput" rows="3" class="bio-textarea" placeholder="Tell us about yourself..." maxlength="300">{{ $Curruser['bio'] ?? '' }}</textarea>
                                <div class="bio-actions">
                                    <button type="button" class="btn-cancel-bio" id="cancelBioBtn">Cancel</button>
                                    <button type="submit" class="btn-save-bio">Save Bio</button>
                                </div>
                                <div class="bio-counter">
                                    <span id="bioCounter">{{ strlen($Curruser['bio'] ?? '') }}</span>/300
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Predictions Feed Section -->
<div class="container predictions-feed-container">
    <div class="feed-header">
        <h2 class="feed-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feed-icon">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            My Predictions
        </h2>
        <div class="feed-count">{{ count($Curruser['predictions']) }} {{ count($Curruser['predictions']) === 1 ? 'Prediction' : 'Predictions' }}</div>
    </div>

    @if(count($Curruser['predictions']) > 0)
        <div class="predictions-feed">
            @foreach ($Curruser['predictions'] as $index => $prediction)
                <div class="prediction-post animate-fade-in" style="animation-delay: {{ $index * 0.1 }}s">
                    <!-- Post Header -->
                    <div class="post-header">
                        <img src="{{ $profilePicture }}" class="post-avatar" alt="Avatar">
                        <div class="post-user-info">
                            <div class="post-username">{{ $Curruser['full_name'] }}</div>
                            <div class="post-timestamp">
                                @if(isset($prediction['end_date']))
                                    Target: {{ date('M d, Y', strtotime($prediction['end_date'])) }}
                                @else
                                    Active Prediction
                                @endif
                            </div>
                        </div>
                        <div class="post-badge {{ $prediction['prediction'] === 'bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                            {{ ucfirst($prediction['prediction']) }}
                        </div>
                    </div>

                    <!-- Post Content -->
                    <div class="post-content">
                        <div class="stock-info-row">
                            <span class="stock-symbol-large">${{ $prediction['symbol'] }}</span>
                            @if(isset($prediction['target_price']) && $prediction['target_price'])
                                <span class="target-price">Target: ${{ number_format($prediction['target_price'], 2) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Post Stats -->
                    <div class="post-stats">
                        <div class="stat-chip {{ isset($prediction['raw_accuracy']) && $prediction['raw_accuracy'] >= 70 ? 'stat-chip-success' : (isset($prediction['raw_accuracy']) && $prediction['raw_accuracy'] >= 50 ? 'stat-chip-warning' : 'stat-chip-neutral') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Accuracy: {{ $prediction['accuracy'] }}</span>
                        </div>

                        <div class="stat-chip stat-chip-info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="6"></circle>
                                <circle cx="12" cy="12" r="2"></circle>
                            </svg>
                            <span>{{ $prediction['is_active'] ? 'Active' : 'Completed' }}</span>
                        </div>
                    </div>

                    <!-- Post Actions -->
                    <div class="post-actions">
                        <button class="action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <span>Comment</span>
                        </button>
                        <button class="action-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                                <polyline points="16 6 12 2 8 6"></polyline>
                                <line x1="12" y1="2" x2="12" y2="15"></line>
                            </svg>
                            <span>Share</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-icon">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
            <h3>No Predictions Yet</h3>
            <p>Start making predictions to see them appear here!</p>
            <a href="{{ route('predictions.create') }}" class="btn btn-primary">Make Your First Prediction</a>
        </div>
    @endif
</div>

@endsection

@section('styles')
<style>
/* Profile Cover Section */
.profile-cover-wrapper {
    position: relative;
    margin-bottom: 2rem;
}

.profile-cover-gradient {
    height: 200px;
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
    position: relative;
}

.profile-content-wrapper {
    margin-top: -100px;
    position: relative;
    z-index: 10;
}

/* Profile Card */
.profile-card {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-bottom: 2rem;
}

.profile-header-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
}

/* Profile Picture */
.profile-picture-section {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.profile-picture-wrapper {
    position: relative;
    width: 150px;
    height: 150px;
}

.profile-picture-main {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid white;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
}

.profile-picture-wrapper:hover .profile-picture-main {
    transform: scale(1.05);
}

.profile-picture-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    transition: all 0.3s ease;
    overflow: hidden;
}

.profile-picture-overlay:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
}

.camera-icon {
    width: 20px;
    height: 20px;
}

.profile-picture-overlay span {
    display: none;
}

/* Profile Info */
.profile-info-section {
    text-align: center;
    width: 100%;
}

.profile-name {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.profile-username {
    font-size: 1rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

/* Stats Row */
.profile-stats-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(145deg, #f9fafb 0%, #ffffff 100%);
    border-radius: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
    margin-top: 0.25rem;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: #e5e7eb;
}

/* Bio Section */
.bio-section-modern {
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
}

.bio-display {
    background: #f9fafb;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: left;
    position: relative;
}

.bio-text {
    color: #374151;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.btn-edit-bio {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit-bio:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.bio-edit-form {
    background: #f9fafb;
    border-radius: 1rem;
    padding: 1.5rem;
}

.bio-textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    font-size: 0.95rem;
    resize: vertical;
    transition: border-color 0.3s ease;
    font-family: inherit;
}

.bio-textarea:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.bio-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.btn-cancel-bio,
.btn-save-bio {
    padding: 0.625rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-cancel-bio {
    background: #e5e7eb;
    color: #374151;
}

.btn-cancel-bio:hover {
    background: #d1d5db;
}

.btn-save-bio {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-save-bio:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.bio-counter {
    text-align: right;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

/* Predictions Feed */
.predictions-feed-container {
    max-width: 800px;
    margin: 0 auto;
    padding-bottom: 3rem;
}

.feed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.feed-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}

.feed-icon {
    color: #10b981;
}

.feed-count {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.predictions-feed {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Prediction Post Card */
.prediction-post {
    background: white;
    border-radius: 1.25rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 1.5rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.prediction-post:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(16, 185, 129, 0.15);
    border-color: #10b981;
}

/* Post Header */
.post-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.post-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #10b981;
}

.post-user-info {
    flex: 1;
}

.post-username {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
}

.post-timestamp {
    font-size: 0.8rem;
    color: #6b7280;
}

.post-badge {
    padding: 0.4rem 1rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-bullish {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.badge-bearish {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

/* Post Content */
.post-content {
    margin-bottom: 1rem;
}

.stock-info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: linear-gradient(145deg, #f9fafb 0%, #ffffff 100%);
    border-radius: 0.75rem;
    border-left: 4px solid #10b981;
}

.stock-symbol-large {
    font-family: monospace;
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.target-price {
    font-size: 1rem;
    font-weight: 600;
    color: #10b981;
}

/* Post Stats */
.post-stats {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.stat-chip-success {
    background: #d1fae5;
    color: #065f46;
}

.stat-chip-warning {
    background: #fef3c7;
    color: #92400e;
}

.stat-chip-neutral {
    background: #e5e7eb;
    color: #374151;
}

.stat-chip-info {
    background: #dbeafe;
    color: #1e40af;
}

/* Post Actions */
.post-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    color: #6b7280;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: #f3f4f6;
    color: #10b981;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-icon {
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (min-width: 768px) {
    .profile-header-content {
        flex-direction: row;
        align-items: flex-start;
        text-align: left;
    }

    .profile-info-section {
        text-align: left;
    }

    .profile-stats-row {
        justify-content: flex-start;
    }
}

@media (max-width: 767px) {
    .profile-cover-gradient {
        height: 150px;
    }

    .profile-content-wrapper {
        margin-top: -75px;
    }

    .profile-picture-main {
        width: 120px;
        height: 120px;
    }

    .profile-picture-wrapper {
        width: 120px;
        height: 120px;
    }

    .profile-stats-row {
        gap: 1rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .post-header {
        flex-wrap: wrap;
    }

    .post-badge {
        margin-left: auto;
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBioBtn = document.getElementById('editBioBtn');
    const cancelBioBtn = document.getElementById('cancelBioBtn');
    const bioDisplay = document.getElementById('bioDisplay');
    const bioEditForm = document.getElementById('bioEditForm');
    const bioInput = document.getElementById('bioInput');
    const bioCounter = document.getElementById('bioCounter');

    // Toggle bio editing
    if (editBioBtn) {
        editBioBtn.addEventListener('click', function() {
            bioDisplay.style.display = 'none';
            bioEditForm.style.display = 'block';
            bioInput.focus();
        });
    }

    if (cancelBioBtn) {
        cancelBioBtn.addEventListener('click', function() {
            bioDisplay.style.display = 'block';
            bioEditForm.style.display = 'none';
        });
    }

    // Character counter
    if (bioInput && bioCounter) {
        bioInput.addEventListener('input', function() {
            bioCounter.textContent = this.value.length;
        });
    }

    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.prediction-post').forEach(post => {
        post.style.opacity = '0';
        post.style.transform = 'translateY(20px)';
        post.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(post);
    });
});
</script>
@endsection