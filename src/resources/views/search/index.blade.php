@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/search.css') }}">
<style>
.gradient-search-hero {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d1f3f 50%, #1a2a3a 100%);
    border-radius: 24px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(139, 92, 246, 0.2);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.search-hero-title {
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}

.search-hero-subtitle {
    color: #a0a0a0;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.enhanced-search-input {
    background: #2a2a2a;
    border: 2px solid #3a3a3a;
    border-radius: 16px;
    padding: 1rem 1.5rem;
    font-size: 1.1rem;
    color: #fff;
    transition: all 0.3s ease;
}

.enhanced-search-input:focus {
    background: #2d2d2d;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    color: #fff;
}

.enhanced-search-btn {
    background: linear-gradient(135deg, #10b981, #3b82f6);
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.enhanced-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.filter-chip {
    background: #2a2a2a;
    border: 2px solid #3a3a3a;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: #fff;
    transition: all 0.3s ease;
}

.filter-chip:focus {
    background: #2d2d2d;
    border-color: #10b981;
    color: #fff;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.result-card-enhanced {
    background: #1a1a1a;
    border: 1px solid #2c2c2c;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.result-card-enhanced:hover {
    transform: translateY(-4px);
    border-color: #10b981;
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.2);
}

.result-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-right: 1.5rem;
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
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.3rem;
}

.result-subtitle {
    color: #a0a0a0;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}

.badge-enhanced {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
}

.quick-action-btn {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid #10b981;
    color: #10b981;
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    background: #10b981;
    color: white;
    transform: scale(1.05);
}

.sidebar-card {
    background: #1a1a1a;
    border: 1px solid #2c2c2c;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.sidebar-card-header {
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.history-item {
    padding: 0.8rem;
    border-radius: 10px;
    margin-bottom: 0.5rem;
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    transition: all 0.2s ease;
}

.history-item:hover {
    background: #2d2d2d;
    border-color: #10b981;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state-icon {
    font-size: 4rem;
    background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>
@endsection

@section('content')
<div class="container search-container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Enhanced Search Hero -->
            <div class="gradient-search-hero">
                <h1 class="search-hero-title text-center">Discover Insights</h1>
                <p class="search-hero-subtitle text-center">Search for stocks, predictions, and top investors</p>

                <!-- Main Search Form -->
                <form action="{{ url('search') }}" method="GET">
                    <div class="input-group mb-3">
                        <input type="text"
                               class="form-control enhanced-search-input"
                               name="query"
                               placeholder="Try 'AAPL', 'Tesla predictions', or 'top investors'..."
                               value="{{ $query }}"
                               id="searchInput"
                               autocomplete="off">
                        <button class="btn enhanced-search-btn" type="submit">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                    </div>

                    <!-- Search suggestions container -->
                    <div id="searchSuggestions" class="search-suggestions"></div>

                    <!-- Filters -->
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="type" class="form-select filter-chip">
                                <option value="all" {{ $type == 'all' ? 'selected' : '' }}>
                                    <i class="bi bi-grid"></i> All Types
                                </option>
                                <option value="stocks" {{ $type == 'stocks' ? 'selected' : '' }}>
                                    <i class="bi bi-graph-up"></i> Stocks
                                </option>
                                <option value="predictions" {{ $type == 'predictions' ? 'selected' : '' }}>
                                    <i class="bi bi-lightning"></i> Predictions
                                </option>
                                <option value="users" {{ $type == 'users' ? 'selected' : '' }}>
                                    <i class="bi bi-people"></i> Users
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="prediction" class="form-select filter-chip">
                                <option value="">Any Prediction</option>
                                <option value="Bullish" {{ $prediction == 'Bullish' ? 'selected' : '' }}>
                                    <i class="bi bi-arrow-up-circle"></i> Bullish
                                </option>
                                <option value="Bearish" {{ $prediction == 'Bearish' ? 'selected' : '' }}>
                                    <i class="bi bi-arrow-down-circle"></i> Bearish
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="sort" class="form-select filter-chip">
                                <option value="relevance" {{ $sort == 'relevance' ? 'selected' : '' }}>
                                    <i class="bi bi-sort-alpha-down"></i> Relevance
                                </option>
                                <option value="date_desc" {{ $sort == 'date_desc' ? 'selected' : '' }}>
                                    <i class="bi bi-clock"></i> Latest
                                </option>
                                <option value="accuracy" {{ $sort == 'accuracy' ? 'selected' : '' }}>
                                    <i class="bi bi-bullseye"></i> Highest Accuracy
                                </option>
                                <option value="votes" {{ $sort == 'votes' ? 'selected' : '' }}>
                                    <i class="bi bi-hand-thumbs-up"></i> Most Votes
                                </option>
                            </select>
                        </div>
                    </div>
                </form>
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

    <div class="row mt-4">
        <!-- Search Results -->
        <div class="col-lg-8">
            @if (!empty($results))
                <div class="mb-4">
                    <h3 class="text-white fw-bold">Search Results</h3>
                    <p class="text-muted">{{ $totalResults }} result(s) for "<span class="text-white">{{ $query }}</span>"</p>
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
