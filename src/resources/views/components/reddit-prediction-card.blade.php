{{--
    Reddit-Style Prediction Card Component
    Left-aligned voting column with text-focused content area.
    Opens global modal when clicked.

    Usage:
    <x-reddit-prediction-card :prediction="$prediction" />

    Optional parameters:
    - :show-engagement="true/false" (default: true) - Show engagement bar
    - :compact="false" (default: false) - Compact mode for sidebars
--}}

@props([
    'prediction',
    'showEngagement' => true,
    'compact' => false
])

@php
    // Handle both Eloquent models and arrays
    $predictionId = is_array($prediction) ? ($prediction['prediction_id'] ?? $prediction['id'] ?? null) : ($prediction->prediction_id ?? $prediction->id ?? null);
    $predictionType = is_array($prediction) ? ($prediction['prediction_type'] ?? $prediction['prediction'] ?? '') : ($prediction->prediction_type ?? '');
    $predictionDate = is_array($prediction) ? ($prediction['prediction_date'] ?? null) : ($prediction->prediction_date ?? null);
    $reasoning = is_array($prediction) ? ($prediction['reasoning'] ?? '') : ($prediction->reasoning ?? '');
    $targetPrice = is_array($prediction) ? ($prediction['target_price'] ?? null) : ($prediction->target_price ?? null);
    $upvotes = is_array($prediction) ? ($prediction['upvotes'] ?? $prediction['votes'] ?? 0) : ($prediction->upvotes ?? 0);
    $downvotes = is_array($prediction) ? ($prediction['downvotes'] ?? 0) : ($prediction->downvotes ?? 0);
    $commentsCount = is_array($prediction) ? ($prediction['comments_count'] ?? 0) : ($prediction->comments_count ?? 0);

    // User info
    if (is_array($prediction)) {
        $userName = $prediction['username'] ?? $prediction['first_name'] ?? 'Unknown';
        $userReputation = $prediction['reputation_score'] ?? 0;
    } else {
        $userName = $prediction->user->first_name ?? 'Unknown';
        $userReputation = $prediction->user->reputation_score ?? 0;
    }

    // Stock info
    if (is_array($prediction)) {
        $stockSymbol = $prediction['symbol'] ?? '';
    } else {
        $stockSymbol = $prediction->stock->symbol ?? '';
    }

    // Calculate vote score
    $voteScore = $upvotes - $downvotes;
    $scoreFormatted = $voteScore >= 1000 ? number_format($voteScore / 1000, 1) . 'k' : $voteScore;

    // Bullish/Bearish
    $isBullish = strtolower($predictionType) === 'bullish';

    // Format date as relative time
    if ($predictionDate) {
        $date = new DateTime($predictionDate);
        $now = new DateTime();
        $diff = $now->diff($date);

        if ($diff->days == 0) {
            if ($diff->h == 0) {
                $timeAgo = $diff->i . 'm ago';
            } else {
                $timeAgo = $diff->h . 'h ago';
            }
        } elseif ($diff->days == 1) {
            $timeAgo = '1d ago';
        } elseif ($diff->days < 7) {
            $timeAgo = $diff->days . 'd ago';
        } elseif ($diff->days < 30) {
            $timeAgo = floor($diff->days / 7) . 'w ago';
        } else {
            $timeAgo = date('M j', strtotime($predictionDate));
        }
    } else {
        $timeAgo = '';
    }
@endphp

<article class="reddit-card {{ $compact ? 'reddit-card--compact' : '' }}"
         data-prediction-id="{{ $predictionId }}"
         role="button"
         tabindex="0"
         aria-label="View prediction for {{ $stockSymbol }} by {{ $userName }}">

    {{-- Left Voting Column (Reddit-style) --}}
    <aside class="reddit-card-vote-column">
        <button type="button"
                class="reddit-vote-btn reddit-vote-up"
                onclick="event.stopPropagation(); votePrediction({{ $predictionId }}, 'upvote', this)"
                aria-label="Upvote">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 19V5M5 12l7-7 7 7"/>
            </svg>
        </button>
        <span class="reddit-vote-score" data-prediction-id="{{ $predictionId }}">{{ $scoreFormatted }}</span>
        <button type="button"
                class="reddit-vote-btn reddit-vote-down"
                onclick="event.stopPropagation(); votePrediction({{ $predictionId }}, 'downvote', this)"
                aria-label="Downvote">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12l7 7 7-7"/>
            </svg>
        </button>
    </aside>

    {{-- Main Content Area --}}
    <div class="reddit-card-content">
        {{-- Header: Username, Reputation & Time --}}
        <div class="reddit-card-header">
            <span class="reddit-card-username">{{ $userName }}</span>
            <span class="reddit-card-separator">&middot;</span>
            <span class="reddit-card-reputation">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                {{ number_format($userReputation) }}
            </span>
            @if($timeAgo)
                <span class="reddit-card-separator">&middot;</span>
                <span class="reddit-card-time">{{ $timeAgo }}</span>
            @endif
        </div>

        {{-- Meta Line: Ticker, Type Badge, Target Price --}}
        <div class="reddit-card-meta">
            <span class="reddit-card-ticker">${{ $stockSymbol }}</span>
            <span class="reddit-card-type reddit-card-type--{{ $isBullish ? 'bullish' : 'bearish' }}">
                @if($isBullish)
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 19V5M5 12l7-7 7 7"/>
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12l7 7 7-7"/>
                    </svg>
                @endif
                {{ $predictionType }}
            </span>
            @if($targetPrice)
                <span class="reddit-card-target">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    ${{ number_format($targetPrice, 2) }}
                </span>
            @endif
        </div>

        {{-- Reasoning Text --}}
        @if($reasoning)
            <p class="reddit-card-reasoning">{{ $reasoning }}</p>
        @endif

        {{-- Engagement Bar --}}
        @if($showEngagement)
        <div class="reddit-card-engagement">
            {{-- Comments --}}
            <button type="button" class="reddit-engagement-btn reddit-engagement-comments">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span>{{ $commentsCount }} {{ $commentsCount == 1 ? 'comment' : 'comments' }}</span>
            </button>

            {{-- Share Button --}}
            <button type="button"
                    class="reddit-engagement-btn reddit-engagement-share"
                    onclick="event.stopPropagation(); sharePrediction({{ $predictionId }}, '{{ $stockSymbol }}')"
                    aria-label="Share prediction">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                    <polyline points="16 6 12 2 8 6"/>
                    <line x1="12" y1="2" x2="12" y2="15"/>
                </svg>
                <span>Share</span>
            </button>
        </div>
        @endif
    </div>
</article>
