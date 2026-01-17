{{--
    Reusable Prediction Card Component
    Matches the home page prediction card styling exactly.

    Usage:
    <x-prediction-card :prediction="$prediction" />

    Optional parameters:
    - :show-comments="true/false" (default: true) - Show comments section
    - :show-votes="true/false" (default: true) - Show voting buttons
    - :compact="true/false" (default: false) - Compact mode for sidebars
    - :clickable="true/false" (default: true) - Make entire card clickable
--}}

@props([
    'prediction',
    'showComments' => true,
    'showVotes' => true,
    'compact' => false,
    'clickable' => true
])

@php
    // Handle both Eloquent models and arrays
    $predictionId = is_array($prediction) ? ($prediction['prediction_id'] ?? $prediction['id'] ?? null) : ($prediction->prediction_id ?? $prediction->id ?? null);
    $predictionType = is_array($prediction) ? ($prediction['prediction_type'] ?? $prediction['prediction'] ?? '') : ($prediction->prediction_type ?? '');
    $targetPrice = is_array($prediction) ? ($prediction['target_price'] ?? null) : ($prediction->target_price ?? null);
    $endDate = is_array($prediction) ? ($prediction['end_date'] ?? null) : ($prediction->end_date ?? null);
    $predictionDate = is_array($prediction) ? ($prediction['prediction_date'] ?? null) : ($prediction->prediction_date ?? null);
    $reasoning = is_array($prediction) ? ($prediction['reasoning'] ?? '') : ($prediction->reasoning ?? '');
    $accuracy = is_array($prediction) ? ($prediction['accuracy'] ?? null) : ($prediction->accuracy ?? null);
    $isActive = is_array($prediction) ? ($prediction['is_active'] ?? false) : ($prediction->is_active ?? false);
    $upvotes = is_array($prediction) ? ($prediction['upvotes'] ?? $prediction['votes'] ?? 0) : ($prediction->upvotes ?? 0);
    $downvotes = is_array($prediction) ? ($prediction['downvotes'] ?? 0) : ($prediction->downvotes ?? 0);
    $commentsCount = is_array($prediction) ? ($prediction['comments_count'] ?? 0) : ($prediction->comments_count ?? 0);
    $currentPrice = is_array($prediction) ? ($prediction['current_price'] ?? null) : ($prediction->current_price ?? null);

    // User info
    if (is_array($prediction)) {
        $userName = $prediction['username'] ?? $prediction['first_name'] ?? 'Unknown';
        $userReputation = $prediction['reputation_score'] ?? 0;
        $userProfilePicture = isset($prediction['profile_picture']) && $prediction['profile_picture']
            ? asset('images/profile_pictures/' . $prediction['profile_picture'])
            : asset('images/default.png');
    } else {
        $userName = $prediction->user->first_name ?? 'Unknown';
        $userReputation = $prediction->user->reputation_score ?? 0;
        $userProfilePicture = $prediction->user->profile_picture
            ? asset('images/profile_pictures/' . $prediction->user->profile_picture)
            : asset('images/default.png');
    }

    // Stock info
    if (is_array($prediction)) {
        $stockSymbol = $prediction['symbol'] ?? '';
        $companyName = $prediction['company_name'] ?? '';
    } else {
        $stockSymbol = $prediction->stock->symbol ?? '';
        $companyName = $prediction->stock->company_name ?? '';
    }

    // Status calculation
    $statusClass = 'bg-secondary';
    $statusText = 'Inactive';
    $statusIcon = 'pause-circle';
    if ($isActive) {
        if ($endDate && strtotime($endDate) > time()) {
            $statusClass = 'bg-success';
            $statusText = 'Active';
            $statusIcon = 'play-circle-fill';
        } else {
            $statusClass = 'bg-warning text-dark';
            $statusText = 'Expired';
            $statusIcon = 'clock-history';
        }
    }

    // Bullish/Bearish
    $isBullish = strtolower($predictionType) === 'bullish';
@endphp

@if($clickable)
<a href="{{ route('predictions.view', ['id' => $predictionId]) }}" class="text-decoration-none d-block">
@endif

<div class="prediction-card" data-prediction-id="{{ $predictionId }}">

    {{-- Top section: Profile on left, dates on right --}}
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="d-flex align-items-center">
            <img src="{{ $userProfilePicture }}" alt="{{ $userName }}"
                 class="rounded-circle" width="48" height="48"
                 style="object-fit: cover; border: 2px solid #10b981;">
            <div class="ms-3">
                <div class="fw-bold" style="font-size: 1rem; margin-bottom: 0.25rem;">
                    {{ $userName }}
                </div>
                <small class="text-muted d-flex align-items-center gap-1">
                    <i class="bi bi-star-fill text-warning"></i>
                    <span>{{ $userReputation }} pts</span>
                    @if($predictionDate)
                        <span class="mx-1">•</span>
                        <span>{{ date('M j, Y', strtotime($predictionDate)) }}</span>
                    @endif
                </small>
            </div>
        </div>

        {{-- End Date Badge --}}
        @if($endDate)
        <div>
            <span class="badge" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid #10b981; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                <i class="bi bi-clock"></i>
                @if($isActive && strtotime($endDate) > time())
                    Ends {{ date('M j', strtotime($endDate)) }}
                @else
                    Ended {{ date('M j', strtotime($endDate)) }}
                @endif
            </span>
        </div>
        @endif
    </div>

    {{-- Company + Stock Info --}}
    @if($stockSymbol)
    <div class="mb-3">
        <h5 class="mb-2" style="font-size: 1.25rem; font-weight: 700;">
            <span class="text-primary">{{ $stockSymbol }}</span>
            @if($companyName)
                <span style="color: #6b7280; font-weight: 500; font-size: 1rem;">{{ $companyName }}</span>
            @endif
        </h5>
    </div>
    @endif

    {{-- Prediction Type & Target Price --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <span class="badge prediction-badge-vibrant {{ $isBullish ? 'badge-bullish' : 'badge-bearish' }}">
            <i class="bi bi-{{ $isBullish ? 'arrow-up' : 'arrow-down' }}-circle-fill me-1"></i>
            {{ $predictionType }}
        </span>
        @if($targetPrice)
            <div class="d-flex align-items-center gap-2">
                <span style="color: #6b7280; font-size: 0.95rem;">Target Price:</span>
                <span style="color: #10b981; font-weight: 700; font-size: 1.15rem;">${{ number_format($targetPrice, 2) }}</span>
            </div>
        @endif
        @if($currentPrice)
            <div class="d-flex align-items-center gap-2">
                <span style="color: #6b7280; font-size: 0.95rem;">Current:</span>
                <span style="color: #3b82f6; font-weight: 600; font-size: 1rem;">${{ number_format($currentPrice, 2) }}</span>
            </div>
        @endif
    </div>

    {{-- Reasoning text --}}
    @if(!$compact && $reasoning)
        <p class="reasoning-text mb-3" style="line-height: 1.6;">{{ $reasoning }}</p>
    @endif

    {{-- Engagement Bar: Votes & Stats --}}
    <div class="border-top pt-3 mt-3" style="border-color: #e5e7eb !important;">
        <div class="d-flex justify-content-between align-items-center">
            {{-- Left: Voting --}}
            <div class="d-flex align-items-center gap-2">
                @if($showVotes && !$clickable)
                    {{-- Upvotes --}}
                    <button class="btn btn-sm vote-btn upvote-btn d-flex align-items-center gap-2"
                            data-id="{{ $predictionId }}"
                            data-action="upvote"
                            style="background: rgba(16, 185, 129, 0.1); border: 1px solid transparent; border-radius: 20px; color: #10b981; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                        <i class="bi bi-arrow-up-circle-fill" style="font-size: 1.1rem;"></i>
                        <span id="upvotes-{{ $predictionId }}" class="fw-bold" style="font-size: 0.85rem;">
                            {{ $upvotes }}
                        </span>
                    </button>

                    {{-- Downvotes --}}
                    <button class="btn btn-sm vote-btn downvote-btn d-flex align-items-center gap-2"
                            data-id="{{ $predictionId }}"
                            data-action="downvote"
                            style="background: rgba(239, 68, 68, 0.1); border: 1px solid transparent; border-radius: 20px; color: #ef4444; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                        <i class="bi bi-arrow-down-circle-fill" style="font-size: 1.1rem;"></i>
                        <span id="downvotes-{{ $predictionId }}" class="fw-bold" style="font-size: 0.85rem;">
                            {{ $downvotes }}
                        </span>
                    </button>
                @else
                    {{-- Read-only vote display --}}
                    <span class="d-flex align-items-center gap-1" style="color: #10b981;">
                        <i class="bi bi-hand-thumbs-up-fill"></i>
                        <span class="fw-bold" style="font-size: 0.9rem;">{{ $upvotes }}</span>
                    </span>
                @endif

                {{-- Status Badge --}}
                <span class="badge {{ $statusClass }}" style="padding: 0.4rem 0.8rem;">
                    <i class="bi bi-{{ $statusIcon }} me-1"></i>{{ $statusText }}
                </span>
            </div>

            {{-- Right: Accuracy & Comments Toggle --}}
            <div class="d-flex align-items-center gap-3">
                @if($accuracy !== null)
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bullseye" style="color: #10b981; font-size: 1.1rem;"></i>
                        <span style="font-weight: 700; font-size: 1rem;">{{ number_format($accuracy, 1) }}%</span>
                        <span class="text-muted" style="font-size: 0.85rem;">accuracy</span>
                    </div>
                @endif

                @if($showComments && !$compact && !$clickable)
                    {{-- Comments Toggle Button --}}
                    <button class="btn btn-sm comments-toggle d-flex align-items-center gap-2"
                            data-prediction-id="{{ $predictionId }}"
                            style="background: rgba(59, 130, 246, 0.1); border: 1px solid transparent; border-radius: 20px; color: #3b82f6; padding: 0.4rem 0.75rem; transition: all 0.2s;">
                        <i class="bi bi-chat-dots" style="font-size: 1.1rem;"></i>
                        <span class="comment-count fw-bold" style="font-size: 0.85rem;">
                            {{ $commentsCount }}
                        </span>
                    </button>
                @elseif($commentsCount > 0)
                    <span class="d-flex align-items-center gap-1" style="color: #3b82f6; font-size: 0.9rem;">
                        <i class="bi bi-chat-dots"></i>
                        <span class="fw-bold">{{ $commentsCount }}</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if($showComments && !$compact && !$clickable)
    {{-- Expandable Comments Section --}}
    <div class="comments-section" id="comments-{{ $predictionId }}" style="display: none;">
        <div class="border-top pt-3 mt-3" style="border-color: #e5e7eb !important;">
            {{-- Comment Form --}}
            @auth
            <div class="comment-form mb-3">
                <div class="d-flex gap-2">
                    <input type="text"
                           class="form-control comment-input"
                           placeholder="Add a comment..."
                           data-prediction-id="{{ $predictionId }}"
                           style="border-radius: 20px; padding: 0.5rem 1rem; font-size: 0.9rem;">
                    <button class="btn btn-primary btn-sm submit-comment"
                            data-prediction-id="{{ $predictionId }}"
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
            <div class="comments-list" id="comments-list-{{ $predictionId }}">
                <div class="text-center py-3 loading-comments">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2 text-muted">Loading comments...</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($clickable)
</a>
@endif
