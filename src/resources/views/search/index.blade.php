@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/search.css') }}">
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
                            <x-prediction-card
                                :prediction="$result"
                                :show-comments="false"
                                :show-votes="false"
                                :clickable="true"
                            />
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
                <div class="empty-state mx-auto">
                    <div class="empty-state-icon">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="text-white mt-3">No results found</h3>
                    <p class="text-muted">Try adjusting your search or filters</p>
                </div>
            @else
                <div class="empty-state mx-auto">
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
                    <button id="saveSearch" class="btn btn-outline-success btn-sm" data-query="{{ $query }}" data-type="{{ $type }}" style="border-radius: 10px; padding: 0.5rem 1rem;">
                        <i class="bi bi-bookmark-plus-fill me-1"></i> Save This Search
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
