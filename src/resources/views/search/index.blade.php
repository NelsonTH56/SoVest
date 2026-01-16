@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/search.css') }}">
<style>
/* Modern, simplified search styling */
.search-hero-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #111827;
}

body.dark-mode .search-hero-title {
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.search-hero-subtitle {
    color: #6b7280;
    font-size: 1.15rem;
}

body.dark-mode .search-hero-subtitle {
    color: #9ca3af;
}

/* Modern search wrapper */
.modern-search-wrapper {
    position: relative;
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.modern-search-input {
    flex: 1;
    border-radius: 1rem;
    padding: 1rem 1.5rem;
    font-size: 1.05rem;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
    background: white;
    color: #111827;
}

.modern-search-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    outline: none;
}

body.dark-mode .modern-search-input {
    background: #2a2a2a;
    border-color: #404040;
    color: #e5e7eb;
}

body.dark-mode .modern-search-input:focus {
    background: #2d2d2d;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);
}

body.dark-mode .modern-search-input::placeholder {
    color: #6b7280;
}

.modern-search-button {
    border-radius: 1rem;
    padding: 1rem 1.75rem;
    font-size: 1.2rem;
    border: none;
    transition: all 0.3s ease;
}

.modern-search-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

/* Simple filter selects */
.simple-filter-select {
    border-radius: 0.75rem;
    padding: 0.65rem 1rem;
    border: 2px solid #e5e7eb;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    background: white;
    color: #111827;
}

.simple-filter-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    outline: none;
}

body.dark-mode .simple-filter-select {
    background: #2a2a2a;
    border-color: #404040;
    color: #e5e7eb;
}

body.dark-mode .simple-filter-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

body.dark-mode .simple-filter-select option {
    background: #2a2a2a;
    color: #e5e7eb;
}

/* Result cards with light/dark mode */
.result-card-enhanced {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.result-card-enhanced:hover {
    transform: translateY(-3px);
    border-color: #10b981;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

body.dark-mode .result-card-enhanced {
    background: #2d2d2d;
    border-color: #404040;
}

body.dark-mode .result-card-enhanced:hover {
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.2);
}

.result-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-right: 1.25rem;
    flex-shrink: 0;
}

.stock-icon-wrapper {
    background: linear-gradient(135deg, #10b981, #059669);
}

.user-icon-wrapper {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.prediction-icon-wrapper {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.result-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.25rem;
}

body.dark-mode .result-title {
    color: #f3f4f6;
}

.result-subtitle {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

body.dark-mode .result-subtitle {
    color: #9ca3af;
}

.badge-enhanced {
    padding: 0.4rem 0.75rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.8rem;
}

.quick-action-btn {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid #10b981;
    color: #10b981;
    border-radius: 0.75rem;
    padding: 0.6rem 1.1rem;
    font-weight: 600;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.quick-action-btn:hover {
    background: #10b981;
    color: white;
    transform: scale(1.05);
}

/* Sidebar styling */
.sidebar-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

body.dark-mode .sidebar-card {
    background: #2d2d2d;
    border-color: #404040;
}

.sidebar-card-header {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

body.dark-mode .sidebar-card-header {
    color: #f3f4f6;
}

.history-item {
    padding: 0.75rem;
    border-radius: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.history-item:hover {
    background: #f3f4f6;
    border-color: #10b981;
}

body.dark-mode .history-item {
    background: #2a2a2a;
    border-color: #404040;
}

body.dark-mode .history-item:hover {
    background: #333333;
    border-color: #10b981;
}

body.dark-mode .history-item .text-white {
    color: #f3f4f6 !important;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    font-size: 4rem;
    color: #d1d5db;
}

body.dark-mode .empty-state-icon {
    color: #6b7280;
}

.empty-state h3 {
    color: #111827;
}

body.dark-mode .empty-state h3 {
    color: #f3f4f6;
}

.empty-state p {
    color: #6b7280;
}

body.dark-mode .empty-state p {
    color: #9ca3af;
}

/* Alert styling */
.alert-info {
    background: rgba(59, 130, 246, 0.1);
    border-left: 4px solid #3b82f6;
    border-radius: 0.75rem;
    color: #111827;
}

body.dark-mode .alert-info {
    background: rgba(59, 130, 246, 0.15);
    color: #e5e7eb;
}

/* Results section header */
.results-header {
    color: #111827;
}

body.dark-mode .results-header {
    color: #f3f4f6;
}

/* Search suggestions */
.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 4rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    margin-top: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

body.dark-mode .search-suggestions {
    background: #2a2a2a;
    border-color: #404040;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
</style>
@endsection

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <!-- Simplified Search Hero -->
            <div class="text-center mb-5">
                <h1 class="search-hero-title mb-3">Search SoVest</h1>
                <p class="search-hero-subtitle">Find stocks, predictions, and investors all in one place</p>
            </div>

            <!-- Main Search Form - Simplified -->
            <div class="row mb-4">
                <div class="col-lg-8 mx-auto">
                    <form action="{{ url('search') }}" method="GET">
                        <div class="modern-search-wrapper">
                            <input type="text"
                                   class="form-control modern-search-input"
                                   name="query"
                                   placeholder="Search for stocks, predictions, or users..."
                                   value="{{ $query }}"
                                   id="searchInput"
                                   autocomplete="off">
                            <button class="btn btn-primary modern-search-button" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>

                        <!-- Search suggestions container -->
                        <div id="searchSuggestions" class="search-suggestions"></div>

                        <!-- Simple Filters Row -->
                        <div class="d-flex gap-2 mt-3 flex-wrap justify-content-center">
                            <select name="type" class="form-select simple-filter-select" style="width: auto;">
                                <option value="all" {{ $type == 'all' ? 'selected' : '' }}>All Types</option>
                                <option value="stocks" {{ $type == 'stocks' ? 'selected' : '' }}>📈 Stocks</option>
                                <option value="predictions" {{ $type == 'predictions' ? 'selected' : '' }}>⚡ Predictions</option>
                                <option value="users" {{ $type == 'users' ? 'selected' : '' }}>👥 Users</option>
                            </select>

                            <select name="prediction" class="form-select simple-filter-select" style="width: auto;" id="predictionFilter">
                                <option value="">Any Type</option>
                                <option value="Bullish" {{ $prediction == 'Bullish' ? 'selected' : '' }}>🔼 Bullish</option>
                                <option value="Bearish" {{ $prediction == 'Bearish' ? 'selected' : '' }}>🔽 Bearish</option>
                            </select>

                            <select name="sort" class="form-select simple-filter-select" style="width: auto;">
                                <option value="relevance" {{ $sort == 'relevance' ? 'selected' : '' }}>Most Relevant</option>
                                <option value="date_desc" {{ $sort == 'date_desc' ? 'selected' : '' }}>Latest</option>
                                <option value="accuracy" {{ $sort == 'accuracy' ? 'selected' : '' }}>Highest Accuracy</option>
                                <option value="votes" {{ $sort == 'votes' ? 'selected' : '' }}>Most Votes</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            @if (isset($predictionIntentDetected) && $predictionIntentDetected)
                <div class="alert alert-info border-0" style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6 !important;">
                    <i class="bi bi-lightbulb-fill me-2"></i>
                    <strong>Prediction Mode:</strong> We've prioritized stock results.
                    <a href="{{ url('predictions/create') }}" class="alert-link fw-bold">Create a new prediction →</a>
                </div>
            @endif
        </div>
    </div>

    <div class="row mt-5">
        <!-- Search Results -->
        <div class="col-lg-8">
            @if (!empty($results))
                <div class="mb-4">
                    <h3 class="fw-bold results-header">Search Results</h3>
                    <p class="text-muted">{{ $totalResults }} result(s) for "<span style="color: #10b981; font-weight: 600;">{{ $query }}</span>"</p>
                </div>

                <div class="search-results">
                    @foreach($results as $result)
                        @if($result['result_type'] == 'stock')
                            <a href="{{ route('stocks.show', $result['symbol']) }}" class="text-decoration-none">
                                <div class="result-card-enhanced">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div class="result-icon-wrapper stock-icon-wrapper">
                                                <i class="bi bi-graph-up-arrow text-white"></i>
                                            </div>
                                            <div>
                                                <div class="result-title">{{ $result['symbol'] }}</div>
                                                <div class="result-subtitle">{{ $result['company_name'] }}</div>
                                                <span class="badge bg-secondary badge-enhanced">{{ $result['sector'] }}</span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <button class="btn quick-action-btn me-2" onclick="event.preventDefault(); window.location.href='{{ route('predictions.create') }}?stock_id={{ $result['stock_id'] ?? '' }}&symbol={{ $result['symbol'] }}&company_name={{ urlencode($result['company_name']) }}'">
                                                <i class="bi bi-plus-circle me-1"></i> Predict
                                            </button>
                                            <i class="bi bi-chevron-right text-muted fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @elseif($result['result_type'] == 'user')
                            <div class="result-card-enhanced">
                                <div class="d-flex align-items-center">
                                    <div class="result-icon-wrapper user-icon-wrapper">
                                        <i class="bi bi-person-circle text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="result-title">{{ $result['first_name'] . ' ' . $result['last_name'] }}</div>
                                        <div class="result-subtitle">{{ $result['email'] }}</div>
                                        @if(isset($result['reputation_score']))
                                            <span class="badge bg-warning badge-enhanced text-dark">
                                                <i class="bi bi-star-fill"></i> {{ $result['reputation_score'] }} reputation
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif($result['result_type'] == 'prediction')
                            <div class="result-card-enhanced">
                                <div class="d-flex align-items-start">
                                    <div class="result-icon-wrapper prediction-icon-wrapper">
                                        <i class="bi bi-lightning-charge-fill text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="result-title">
                                            {{ $result['symbol'] }}
                                            <span class="badge {{ $result['prediction_type'] == 'Bullish' ? 'bg-success' : 'bg-danger' }} badge-enhanced ms-2">
                                                <i class="bi bi-{{ $result['prediction_type'] == 'Bullish' ? 'arrow-up' : 'arrow-down' }}-circle-fill"></i>
                                                {{ $result['prediction_type'] }}
                                            </span>
                                        </div>
                                        <div class="result-subtitle">By {{ $result['first_name'] . ' ' . $result['last_name'] }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            @if(isset($result['accuracy']))
                                                <span class="badge {{ $result['accuracy'] >= 70 ? 'bg-success' : 'bg-warning text-dark' }} badge-enhanced">
                                                    <i class="bi bi-bullseye"></i> {{ $result['accuracy'] }}% accuracy
                                                </span>
                                            @endif
                                            <span class="badge bg-info badge-enhanced">
                                                <i class="bi bi-hand-thumbs-up-fill"></i> {{ $result['votes'] ?? 0 }} votes
                                            </span>
                                            @if(isset($result['target_price']))
                                                <span class="badge bg-secondary badge-enhanced">
                                                    <i class="bi bi-bullseye"></i> ${{ number_format($result['target_price'], 2) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if($totalResults > 10)
                    <!-- Pagination -->
                    <nav aria-label="Search results pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            @php
                                $totalPages = ceil($totalResults / 10);
                                for($i = 1; $i <= $totalPages; $i++):
                            @endphp
                                <li class="page-item {{ $i == $page ? 'active' : '' }}">
                                    <a class="page-link bg-dark border-secondary text-white" href="{{ url('search') }}?query={{ urlencode($query) }}&type={{ $type }}&prediction={{ $prediction }}&sort={{ $sort }}&page={{ $i }}">
                                        {{ $i }}
                                    </a>
                                </li>
                            @php endfor; @endphp
                        </ul>
                    </nav>
                @endif
            @elseif(!empty($query))
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="text-white mt-3">No results found</h3>
                    <p class="text-muted">Try adjusting your search or filters</p>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-compass"></i>
                    </div>
                    <h3 class="text-white mt-3">Start Exploring</h3>
                    <p class="text-muted">Search for stocks, predictions, or investors above</p>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            @if (!empty($searchHistory))
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <span><i class="bi bi-clock-history me-2"></i>Recent Searches</span>
                        <button class="btn btn-sm btn-outline-danger" id="clearHistory">
                            <i class="bi bi-trash"></i> Clear
                        </button>
                    </div>
                    <div>
                        @foreach($searchHistory as $history)
                            <a href="{{ url('search') }}?query={{ urlencode($history['search_query']) }}&type={{ $history['search_type'] }}" class="text-decoration-none">
                                <div class="history-item">
                                    <div class="text-white fw-semibold">{{ $history['search_query'] }}</div>
                                    <small class="text-muted">
                                        <i class="bi bi-tag"></i> {{ ucfirst($history['search_type']) }} •
                                        {{ date("M j, g:i a", strtotime($history['created_at'])) }}
                                    </small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($savedSearches))
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <span><i class="bi bi-bookmark-star-fill me-2"></i>Saved Searches</span>
                    </div>
                    <div>
                        @foreach($savedSearches as $saved)
                            <div class="history-item d-flex justify-content-between align-items-center">
                                <a href="{{ url('search') }}?query={{ urlencode($saved['search_query']) }}&type={{ $saved['search_type'] }}" class="text-decoration-none flex-grow-1">
                                    <div class="text-white fw-semibold">{{ $saved['search_query'] }}</div>
                                    <small class="text-muted">
                                        <i class="bi bi-tag"></i> {{ ucfirst($saved['search_type']) }}
                                    </small>
                                </a>
                                <button class="btn btn-sm btn-outline-danger remove-saved" data-id="{{ $saved['id'] }}">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($query))
                <div class="text-center">
                    <button id="saveSearch" class="btn btn-outline-success w-100" data-query="{{ $query }}" data-type="{{ $type }}" style="border-radius: 12px; padding: 0.8rem;">
                        <i class="bi bi-bookmark-plus-fill me-2"></i> Save This Search
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/search.js') }}"></script>
@endsection
