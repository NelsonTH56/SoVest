@extends('layouts.app')

@section('title', 'Leaderboard - SoVest')

@section('content')
<div class="leaderboard-page">
    {{-- Page Header --}}
    <div class="leaderboard-header text-center mb-4">
        <h1 class="leaderboard-title">
            <i class="bi bi-trophy" style="color: #10b981;"></i>
            Leaderboard
        </h1>
        <p class="leaderboard-subtitle">Top predictors ranked by reputation score</p>
    </div>

    {{-- User's Current Rank Card (if not in top 20) --}}
    @if($userRank == 0 && $userInfo)
        <div class="your-rank-card mb-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="your-rank-label">Your Current Standing</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rank-badge unranked">--</span>
                            <div>
                                <span class="your-name">{{ $userInfo['first_name'] }} {{ $userInfo['last_name'] }}</span>
                                <span class="your-stats">{{ $userInfo['prediction_count'] ?? 0 }} predictions</span>
                            </div>
                        </div>
                        <div class="your-score">
                            <i class="bi bi-star-fill" style="color: #10b981;"></i>
                            <span>{{ number_format($userInfo['reputation_score']) }} pts</span>
                        </div>
                    </div>
                    <p class="rank-message">Keep making accurate predictions to climb the leaderboard!</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Leaderboard Table --}}
    <div class="leaderboard-container">
        @if(!empty($topUsers) && count($topUsers) > 0)
            <div class="leaderboard-list">
                @foreach($topUsers as $index => $user)
                    @php
                        $isCurrentUser = ($user['id'] == $userID);
                        $rankClass = '';
                        if ($index === 0) $rankClass = 'rank-gold';
                        elseif ($index === 1) $rankClass = 'rank-silver';
                        elseif ($index === 2) $rankClass = 'rank-bronze';
                    @endphp
                    <div class="leaderboard-row {{ $isCurrentUser ? 'current-user' : '' }} {{ $rankClass }}">
                        {{-- Rank --}}
                        <div class="rank-cell">
                            @if($index === 0)
                                <i class="bi bi-1-circle rank-icon" style="color: #10b981"></i>
                            @elseif($index === 1)
                                <i class="bi bi-2-circle rank-icon" style="color: #10b981"></i>
                            @elseif($index === 2)
                                <i class="bi bi-3-circle rank-icon" style="color: #10b981s"></i>
                            @else
                                <span class="rank-number">{{ $index + 1 }}</span>
                            @endif
                        </div>

                        {{-- User Info --}}
                        <div class="user-cell">
                            <span class="user-name">{{ $user['first_name'] }} {{ $user['last_name'] }}</span>
                            @if($isCurrentUser)
                                <span class="you-badge">You</span>
                            @endif
                        </div>

                        {{-- Stats --}}
                        <div class="stats-cell">
                            <div class="stat">
                                <span class="stat-value">{{ $user['predictions_count'] ?? 0 }}</span>
                                <span class="stat-label">Predictions</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">{{ number_format($user['avg_accuracy'] ?? 0, 1) }}%</span>
                                <span class="stat-label">Accuracy</span>
                            </div>
                        </div>

                        {{-- Score --}}
                        <div class="score-cell">
                            <i class="bi bi-star-fill" style="color: #f59e0b;"></i>
                            <span class="score-value">{{ number_format($user['reputation_score']) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-leaderboard text-center py-5">
                <i class="bi bi-trophy" style="font-size: 3rem; color: #9ca3af;"></i>
                <h4 class="mt-3">No Rankings Yet</h4>
                <p class="text-muted">Be the first to make predictions and climb the leaderboard!</p>
                <a href="{{ route('predictions.create') }}" class="btn btn-primary mt-2">Make a Prediction</a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
/* Leaderboard Page Styles */
.leaderboard-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1rem;
}

.leaderboard-header {
    margin-bottom: 2rem;
}

.leaderboard-title {
    font-size: 2rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

body.dark-mode .leaderboard-title {
    color: #f3f4f6;
}

.leaderboard-subtitle {
    color: #6b7280;
    font-size: 1rem;
    margin-top: 0.5rem;
}

body.dark-mode .leaderboard-subtitle {
    color: #9ca3af;
}

/* Your Rank Card */
.your-rank-card .card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
    border: 1px solid #10b981;
    border-radius: 0.75rem;
}

body.dark-mode .your-rank-card .card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%);
}

.your-rank-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

body.dark-mode .your-rank-label {
    color: #9ca3af;
}

.rank-badge.unranked {
    width: 40px;
    height: 40px;
    background: #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #6b7280;
}

body.dark-mode .rank-badge.unranked {
    background: #404040;
    color: #9ca3af;
}

.your-name {
    display: block;
    font-weight: 600;
    color: #111827;
}

body.dark-mode .your-name {
    color: #f3f4f6;
}

.your-stats {
    font-size: 0.85rem;
    color: #6b7280;
}

body.dark-mode .your-stats {
    color: #9ca3af;
}

.your-score {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 700;
    font-size: 1.1rem;
    color: #10b981;
}

.rank-message {
    margin: 0.75rem 0 0;
    font-size: 0.85rem;
    color: #6b7280;
}

body.dark-mode .rank-message {
    color: #9ca3af;
}

/* Leaderboard Container */
.leaderboard-container {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

body.dark-mode .leaderboard-container {
    background: #2a2a2a;
    border-color: #404040;
}

/* Leaderboard Row */
.leaderboard-row {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

body.dark-mode .leaderboard-row {
    border-bottom-color: #404040;
}

.leaderboard-row:last-child {
    border-bottom: none;
}

.leaderboard-row:hover {
    background-color: rgba(16, 185, 129, 0.05);
}

body.dark-mode .leaderboard-row:hover {
    background-color: rgba(16, 185, 129, 0.1);
}

.leaderboard-row.current-user {
    background-color: rgba(16, 185, 129, 0.1);
}

body.dark-mode .leaderboard-row.current-user {
    background-color: rgba(16, 185, 129, 0.15);
}

/* Top 3 styling */
.leaderboard-row.rank-gold {
    background: linear-gradient(90deg, rgba(251, 191, 36, 0.1) 0%, transparent 50%);
}

.leaderboard-row.rank-silver {
    background: linear-gradient(90deg, rgba(156, 163, 175, 0.1) 0%, transparent 50%);
}

.leaderboard-row.rank-bronze {
    background: linear-gradient(90deg, rgba(205, 127, 50, 0.1) 0%, transparent 50%);
}

/* Rank Cell */
.rank-cell {
    width: 50px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rank-icon {
    font-size: 1.75rem;
}

.rank-number {
    font-weight: 700;
    font-size: 1rem;
    color: #6b7280;
}

body.dark-mode .rank-number {
    color: #9ca3af;
}

/* User Cell */
.user-cell {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

body.dark-mode .user-name {
    color: #f3f4f6;
}

.you-badge {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #10b981;
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
}

/* Stats Cell */
.stats-cell {
    display: flex;
    gap: 1.5rem;
    margin-right: 1.5rem;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-weight: 700;
    font-size: 0.95rem;
    color: #111827;
}

body.dark-mode .stat-value {
    color: #f3f4f6;
}

.stat-label {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

body.dark-mode .stat-label {
    color: #9ca3af;
}

/* Score Cell */
.score-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 80px;
    justify-content: flex-end;
}

.score-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: #10b981;
}

/* Empty State */
.empty-leaderboard {
    padding: 3rem 2rem;
}

.empty-leaderboard h4 {
    color: #111827;
}

body.dark-mode .empty-leaderboard h4 {
    color: #f3f4f6;
}

/* Responsive */
@media (max-width: 640px) {
    .leaderboard-row {
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .rank-cell {
        width: 40px;
    }

    .rank-icon {
        font-size: 1.5rem;
    }

    .user-cell {
        flex: none;
        width: calc(100% - 50px);
    }

    .stats-cell {
        width: 100%;
        justify-content: flex-start;
        margin-right: 0;
        margin-left: 50px;
        gap: 1rem;
    }

    .score-cell {
        position: absolute;
        right: 1.25rem;
        top: 1rem;
    }

    .leaderboard-row {
        position: relative;
    }
}
</style>
@endsection
