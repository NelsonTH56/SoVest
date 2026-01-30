@extends('layouts.app')

@section('title', $pageTitle ?? "{$prediction['symbol']} {$prediction['prediction_type']} Prediction")

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reddit-card.css') }}">
@endsection

@section('content')
    @php
    // Calculate prediction status
    $isPending = $prediction['accuracy'] === null;
    $isActive = $prediction['is_active'] == 1;
    $endDate = new DateTime($prediction['end_date']);
    $today = new DateTime();
    $daysRemaining = $today > $endDate ? 0 : $today->diff($endDate)->days;
    $isBullish = $prediction['prediction_type'] === 'Bullish';

    // Calculate price change percentage
    $priceChange = null;
    if (isset($prediction['target_price']) && isset($prediction['stock']['current_price']) && $prediction['stock']['current_price'] > 0) {
        $priceChange = (($prediction['target_price'] - $prediction['stock']['current_price']) / $prediction['stock']['current_price']) * 100;
    }

    // Format dates
    $predictionDateFormatted = date('M j, Y', strtotime($prediction['prediction_date']));
    $endDateFormatted = date('M j, Y', strtotime($prediction['end_date']));
    @endphp

    <div class="prediction-detail-page">
        <div class="prediction-detail-grid">
            <!-- Main Content -->
            <div class="space-y-6">
                <!-- Prediction Card -->
                <div class="prediction-detail-card">
                    <!-- Header -->
                    <div class="prediction-detail-header">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-3">
                                <span class="modal-badge modal-badge--{{ $isBullish ? 'bullish' : 'bearish' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        @if($isBullish)
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        @else
                                            <path d="M12 5v14M5 12l7 7 7-7"/>
                                        @endif
                                    </svg>
                                    {{ $prediction['prediction_type'] }}
                                </span>
                                <span class="text-2xl font-bold" style="color: var(--reddit-accent); font-family: ui-monospace, monospace;">
                                    ${{ $prediction['symbol'] }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($prediction['accuracy'] !== null)
                                    <span class="modal-meta-item" style="background: {{ $prediction['accuracy'] >= 70 ? 'rgba(16, 185, 129, 0.15)' : ($prediction['accuracy'] >= 40 ? 'rgba(245, 158, 11, 0.15)' : 'rgba(239, 68, 68, 0.15)') }}; color: {{ $prediction['accuracy'] >= 70 ? 'var(--reddit-accent-dark)' : ($prediction['accuracy'] >= 40 ? 'var(--reddit-warning)' : 'var(--reddit-danger)') }};">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <circle cx="12" cy="12" r="6"/>
                                            <circle cx="12" cy="12" r="2"/>
                                        </svg>
                                        {{ number_format($prediction['accuracy'], 0) }}% Accuracy
                                    </span>
                                @else
                                    <span class="modal-meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        Pending
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="prediction-detail-body">
                        <!-- User Info -->
                        <div class="flex items-center gap-3 mb-6 pb-6" style="border-bottom: 1px solid var(--reddit-card-border);">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg" style="background: var(--reddit-accent);">
                                {{ strtoupper(substr($prediction['username'], 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold" style="color: var(--reddit-text-primary);">{{ $prediction['username'] }}</div>
                                <div class="text-sm flex items-center gap-2" style="color: var(--reddit-text-secondary);">
                                    @if(isset($prediction['user']['reputation_score']))
                                        <span class="flex items-center gap-1" style="color: var(--reddit-warning);">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                            {{ number_format($prediction['user']['reputation_score']) }} reputation
                                        </span>
                                        <span>&middot;</span>
                                    @endif
                                    <span>{{ $predictionDateFormatted }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Info Widget -->
                        <div class="modal-stock-widget mb-6">
                            <div class="modal-stock-row">
                                <div class="modal-stock-info">
                                    <span class="modal-ticker">${{ $prediction['stock']['symbol'] }}</span>
                                    @if(isset($prediction['stock']['company_name']))
                                        <span class="modal-company">{{ $prediction['stock']['company_name'] }}</span>
                                    @endif
                                </div>
                                @if(isset($prediction['stock']['current_price']))
                                    <div class="modal-stock-prices">
                                        <span class="modal-current-price">Current Price</span>
                                        <span class="modal-current-price-value">${{ number_format($prediction['stock']['current_price'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-prediction-row">
                                <span class="modal-badge modal-badge--{{ $isBullish ? 'bullish' : 'bearish' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        @if($isBullish)
                                            <path d="M12 19V5M5 12l7-7 7 7"/>
                                        @else
                                            <path d="M12 5v14M5 12l7 7 7-7"/>
                                        @endif
                                    </svg>
                                    {{ $prediction['prediction_type'] }}
                                </span>
                                @if($prediction['target_price'])
                                    <div class="modal-target-price">
                                        <span class="modal-target-label">Target:</span>
                                        <span class="modal-target-value">${{ number_format($prediction['target_price'], 2) }}</span>
                                        @if($priceChange !== null)
                                            <span class="modal-price-change modal-price-change--{{ $priceChange >= 0 ? 'up' : 'down' }}">
                                                {{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChange, 1) }}%
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Reasoning -->
                        @if($prediction['reasoning'])
                            <div class="modal-reasoning mb-6" style="margin-left: 0; margin-right: 0;">
                                <div class="modal-reasoning-text">{{ $prediction['reasoning'] }}</div>
                            </div>
                        @endif

                        <!-- Meta Info -->
                        <div class="modal-meta-bar mb-6" style="margin-left: 0; margin-right: 0;">
                            <span class="modal-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                @if($isPending)
                                    Ends {{ $endDateFormatted }} ({{ $daysRemaining }} days remaining)
                                @else
                                    Ended {{ $endDateFormatted }}
                                @endif
                            </span>
                            <span class="modal-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    @if($isActive)
                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                    @else
                                        <rect x="6" y="4" width="4" height="16"/>
                                        <rect x="14" y="4" width="4" height="16"/>
                                    @endif
                                </svg>
                                {{ $isActive ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Voting Section -->
                        <div class="modal-voting" style="margin-left: 0; margin-right: 0;" id="votingSection">
                            @auth
                                <button class="modal-vote-btn modal-vote-up" data-prediction-id="{{ $prediction['prediction_id'] }}" data-vote-type="upvote">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 19V5M5 12l7-7 7 7"/>
                                    </svg>
                                    <span id="upvoteCount">{{ $prediction['upvotes'] }}</span>
                                </button>
                                <button class="modal-vote-btn modal-vote-down" data-prediction-id="{{ $prediction['prediction_id'] }}" data-vote-type="downvote">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 5v14M5 12l7 7 7-7"/>
                                    </svg>
                                    <span id="downvoteCount">{{ $prediction['downvotes'] }}</span>
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="modal-vote-btn modal-vote-up">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 19V5M5 12l7-7 7 7"/>
                                    </svg>
                                    <span>{{ $prediction['upvotes'] }}</span>
                                </a>
                                <a href="{{ route('login') }}" class="modal-vote-btn modal-vote-down">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M12 5v14M5 12l7 7 7-7"/>
                                    </svg>
                                    <span>{{ $prediction['downvotes'] }}</span>
                                </a>
                            @endauth
                            <button class="modal-share-btn" onclick="sharePrediction({{ $prediction['prediction_id'] }}, '{{ $prediction['symbol'] }}')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                                    <polyline points="16 6 12 2 8 6"/>
                                    <line x1="12" y1="2" x2="12" y2="15"/>
                                </svg>
                                Share
                            </button>
                        </div>

                        <!-- Owner Actions -->
                        @if(Auth::check() && Auth::id() == $prediction['user_id'] && $isActive)
                            <div class="flex justify-end gap-3 pt-4" style="border-top: 1px solid var(--reddit-card-border);">
                                <a href="{{ route('predictions.edit', ['id' => $prediction['prediction_id']]) }}"
                                   class="modal-vote-btn" style="text-decoration: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                    Edit
                                </a>
                                <button type="button" class="modal-vote-btn" style="border-color: var(--reddit-danger); color: var(--reddit-danger);"
                                        onclick="confirmDelete({{ $prediction['prediction_id'] }})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="prediction-detail-card">
                    <div class="prediction-detail-header">
                        <h3 class="flex items-center gap-2 m-0 font-semibold" style="color: var(--reddit-text-primary);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--reddit-info);">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            Comments ({{ isset($comments) ? $comments->count() : 0 }})
                        </h3>
                    </div>
                    <div class="prediction-detail-body">
                        <!-- Comment Form -->
                        @auth
                            <div class="modal-comment-form mb-6" id="commentForm" data-prediction-id="{{ $prediction['prediction_id'] }}">
                                <input type="text"
                                       class="modal-comment-input"
                                       id="commentInput"
                                       placeholder="Share your thoughts on this prediction..."
                                       maxlength="600">
                                <button class="modal-comment-submit" id="commentSubmit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"/>
                                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div class="modal-login-prompt mb-6">
                                <a href="{{ route('login') }}">Log in</a> to join the discussion.
                            </div>
                        @endauth

                        <!-- Comments List -->
                        <div class="modal-comments-list" id="commentsList">
                            @if(isset($comments) && $comments->count() > 0)
                                @foreach($comments as $comment)
                                    @include('predictions.partials.comment-unified', ['comment' => $comment, 'depth' => 0])
                                @endforeach
                            @else
                                <div class="modal-no-comments">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 0.5rem;">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <p>No comments yet. Be the first to share your thoughts!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div>
                    <a href="{{ route('predictions.trending') }}" class="modal-vote-btn" style="text-decoration: none; display: inline-flex;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        Back to Trending
                    </a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Stock Information Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Stock Information</div>
                    <div class="sidebar-card-body">
                        <div class="text-2xl font-bold mb-1" style="color: var(--reddit-accent); font-family: ui-monospace, monospace;">
                            ${{ $prediction['stock']['symbol'] }}
                        </div>
                        <div class="text-sm mb-4" style="color: var(--reddit-text-secondary);">
                            {{ $prediction['stock']['company_name'] ?? 'Unknown Company' }}
                        </div>

                        @if(isset($prediction['stock']['sector']))
                            <div class="flex justify-between py-2" style="border-top: 1px solid var(--reddit-card-border);">
                                <span style="color: var(--reddit-text-muted);">Sector</span>
                                <span style="color: var(--reddit-text-primary);">{{ $prediction['stock']['sector'] }}</span>
                            </div>
                        @endif

                        @if(isset($prediction['stock']['current_price']))
                            <div class="flex justify-between py-2" style="border-top: 1px solid var(--reddit-card-border);">
                                <span style="color: var(--reddit-text-muted);">Current Price</span>
                                <span class="font-semibold" style="color: var(--reddit-info);">${{ number_format($prediction['stock']['current_price'], 2) }}</span>
                            </div>
                        @endif

                        <div class="mt-4">
                            <a href="{{ route('search', ['query' => $prediction['stock']['symbol']]) }}"
                               class="block w-full text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors"
                               style="background: var(--reddit-card-bg-hover); color: var(--reddit-accent); border: 1px solid var(--reddit-card-border);">
                                View Stock Details
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Predictor Profile Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">Predictor Profile</div>
                    <div class="sidebar-card-body">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-lg" style="background: var(--reddit-accent);">
                                {{ strtoupper(substr($prediction['username'], 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold" style="color: var(--reddit-text-primary);">{{ $prediction['username'] }}</div>
                                @if(isset($prediction['user']['reputation_score']))
                                    <div class="flex items-center gap-1 text-sm" style="color: var(--reddit-warning);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        {{ number_format($prediction['user']['reputation_score']) }} reputation
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="prediction-detail-modal" id="deleteModal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="prediction-modal-backdrop" onclick="closeDeleteModal()"></div>
        <div class="prediction-modal-container" style="max-width: 400px;">
            <div class="prediction-modal-content" style="padding: 1.5rem;">
                <h3 class="text-lg font-semibold mb-2" style="color: var(--reddit-text-primary);">Confirm Deletion</h3>
                <p class="mb-4" style="color: var(--reddit-text-secondary);">
                    Are you sure you want to delete this prediction? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button class="modal-vote-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="modal-vote-btn" style="background: var(--reddit-danger); color: white; border-color: var(--reddit-danger);"
                            id="confirmDeleteBtn">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Toast -->
    <div class="share-toast" id="shareToast">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        Link copied to clipboard!
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Voting
    document.querySelectorAll('#votingSection .modal-vote-btn[data-vote-type]').forEach(btn => {
        btn.addEventListener('click', async function() {
            const predictionId = this.dataset.predictionId;
            const voteType = this.dataset.voteType;

            try {
                const response = await fetch(`/predictions/vote/${predictionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json'
                    },
                    body: new URLSearchParams({
                        vote_type: voteType,
                        prediction_id: predictionId
                    })
                });

                const result = await response.json();
                if (result.success || response.ok) {
                    // Refresh vote counts
                    const countsResponse = await fetch(`/predictions/${predictionId}/vote-counts`);
                    const counts = await countsResponse.json();

                    if (counts.success) {
                        document.getElementById('upvoteCount').textContent = counts.upvotes;
                        document.getElementById('downvoteCount').textContent = counts.downvotes;
                    }

                    // Toggle button states
                    const upBtn = document.querySelector('[data-vote-type="upvote"]');
                    const downBtn = document.querySelector('[data-vote-type="downvote"]');

                    if (voteType === 'upvote') {
                        upBtn?.classList.toggle('active');
                        downBtn?.classList.remove('active');
                    } else {
                        downBtn?.classList.toggle('active');
                        upBtn?.classList.remove('active');
                    }
                }
            } catch (error) {
                console.error('Vote error:', error);
            }
        });
    });

    // Comment submission
    const commentInput = document.getElementById('commentInput');
    const commentSubmit = document.getElementById('commentSubmit');
    const predictionId = document.getElementById('commentForm')?.dataset.predictionId;

    if (commentSubmit && commentInput && predictionId) {
        const submitComment = async () => {
            const content = commentInput.value.trim();
            if (!content) return;

            commentSubmit.disabled = true;

            try {
                const response = await fetch('/comments', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        prediction_id: predictionId,
                        content: content
                    })
                });

                const result = await response.json();
                if (result.success || response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Comment error:', error);
            } finally {
                commentSubmit.disabled = false;
            }
        };

        commentSubmit.addEventListener('click', submitComment);
        commentInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                submitComment();
            }
        });
    }
});

// Delete confirmation
let deleteId = null;

function confirmDelete(id) {
    deleteId = id;
    document.getElementById('deleteModal').classList.add('active', 'visible');
    document.body.classList.add('modal-open');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active', 'visible');
    document.body.classList.remove('modal-open');
    deleteId = null;
}

document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
    if (!deleteId) return;

    try {
        const response = await fetch(`/api/predictions/delete/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();
        if (result.success || response.ok) {
            window.location.href = '{{ route("predictions.trending") }}';
        } else {
            alert('Error: ' + (result.message || 'Failed to delete prediction'));
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('An error occurred while deleting');
    }
});

// Share functionality
function sharePrediction(predictionId, symbol) {
    const url = new URL(window.location);
    url.searchParams.set('prediction', predictionId);

    if (navigator.share) {
        navigator.share({
            title: `SoVest - ${symbol} Prediction`,
            url: url.toString()
        }).catch(() => copyToClipboard(url.toString()));
    } else {
        copyToClipboard(url.toString());
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showShareToast();
    }).catch(() => {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showShareToast();
    });
}

function showShareToast() {
    const toast = document.getElementById('shareToast');
    if (toast) {
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 2500);
    }
}
</script>
@endsection
