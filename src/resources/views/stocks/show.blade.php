@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/stock-show.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="container mt-4" style="max-width: 1200px;">
    <!-- Back Navigation -->
    <div class="mb-3">
        <a href="{{ url('search') }}?query={{ urlencode($stock->symbol) }}&type=stocks"
           class="text-decoration-none d-inline-flex align-items-center back-link">
            <i class="bi bi-arrow-left me-2"></i> Back to Search
        </a>
    </div>

    <!-- Stock Hero Section -->
    <div class="stock-detail-hero">
        <div class="stock-content">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="stock-symbol-display">{{ $stock->symbol }}</div>
                    <div class="stock-company-name">{{ $stock->company_name }}</div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="sector-badge">
                            <i class="bi bi-building"></i> {{ $stock->sector }}
                        </span>
                        <!-- Primary Action Button - In Hero -->
                        @auth
                            <a href="{{ url('predictions/create') }}?stock_id={{ $stock->stock_id }}&symbol={{ urlencode($stock->symbol) }}&company_name={{ urlencode($stock->company_name) }}"
                               class="btn action-button-primary">
                                <i class="bi bi-lightning-charge-fill me-2"></i> Create Prediction
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn action-button-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login to Predict
                            </a>
                        @endauth
                    </div>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="price-display-card">
                        <div class="price-label-text">Current Price</div>
                        @if(isset($currentPrice) && $currentPrice !== null)
                            <div class="price-value">${{ number_format($currentPrice, 2) }}</div>
                            <div class="price-currency">USD</div>
                        @else
                            <div class="price-value">
                                <span class="loading-shimmer"></span>
                            </div>
                            <div class="price-currency">Loading...</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Price History Chart Section -->
    @if(!empty($priceHistory) && count($priceHistory) > 1)
    <div class="info-section-card mb-4">
        <div class="section-title">
            <i class="bi bi-graph-up-arrow"></i>
            30-Day Price History
        </div>

        <!-- Price Change Summary -->
        @if($priceStats['hasData'])
        <div class="price-change-summary">
            <span class="price-change {{ $priceStats['change'] >= 0 ? 'positive' : 'negative' }}">
                <i class="bi {{ $priceStats['change'] >= 0 ? 'bi-arrow-up-circle-fill' : 'bi-arrow-down-circle-fill' }}"></i>
                {{ $priceStats['change'] >= 0 ? '+' : '' }}${{ number_format(abs($priceStats['change']), 2) }}
                ({{ $priceStats['changePercent'] >= 0 ? '+' : '' }}{{ number_format($priceStats['changePercent'], 2) }}%)
            </span>
            <span class="price-range">
                <i class="bi bi-arrow-down-up"></i>
                Range: ${{ number_format($priceStats['low'], 2) }} - ${{ number_format($priceStats['high'], 2) }}
            </span>
        </div>
        @endif

        <!-- Chart Container -->
        <div class="chart-container">
            <canvas id="priceHistoryChart"></canvas>
        </div>
    </div>
    @else
    <div class="info-section-card mb-4">
        <div class="section-title">
            <i class="bi bi-graph-up-arrow"></i>
            Price History
        </div>
        <div class="empty-chart-message">
            @if(isset($historyLoading) && $historyLoading)
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading price history... Refresh the page in a few seconds.</p>
            @else
                <i class="bi bi-bar-chart-line"></i>
                <p>No price history available for this stock yet.</p>
            @endif
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Stock Information -->
        <div class="col-lg-5 mb-4">
            <div class="info-section-card">
                <div class="section-title">
                    <i class="bi bi-info-circle-fill"></i>
                    Stock Information
                </div>

                <div class="info-row">
                    <span class="info-row-label">
                        <i class="bi bi-tag-fill me-2"></i>Symbol
                    </span>
                    <span class="info-row-value">{{ $stock->symbol }}</span>
                </div>

                <div class="info-row">
                    <span class="info-row-label">
                        <i class="bi bi-building me-2"></i>Company
                    </span>
                    <span class="info-row-value">{{ $stock->company_name }}</span>
                </div>

                <div class="info-row">
                    <span class="info-row-label">
                        <i class="bi bi-briefcase-fill me-2"></i>Sector
                    </span>
                    <span class="info-row-value">{{ $stock->sector }}</span>
                </div>

                @if(isset($currentPrice) && $currentPrice !== null)
                <div class="info-row">
                    <span class="info-row-label">
                        <i class="bi bi-currency-dollar me-2"></i>Latest Price
                    </span>
                    <span class="info-row-value">${{ number_format($currentPrice, 2) }}</span>
                </div>
                @endif

                @if(isset($latestPriceDate))
                <div class="info-row">
                    <span class="info-row-label">
                        <i class="bi bi-calendar-check me-2"></i>Price Date
                    </span>
                    <span class="info-row-value">{{ date('M j, Y', strtotime($latestPriceDate)) }}</span>
                </div>
                @endif

                <!-- Quick Stats -->
                <div class="quick-stats-grid">
                    <div class="quick-stat-item">
                        <div class="quick-stat-label">Predictions</div>
                        <div class="quick-stat-value">{{ count($predictions ?? []) }}</div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-label">Bullish</div>
                        <div class="quick-stat-value text-success">
                            {{ collect($predictions ?? [])->where('prediction_type', 'Bullish')->count() }}
                        </div>
                    </div>
                    <div class="quick-stat-item">
                        <div class="quick-stat-label">Bearish</div>
                        <div class="quick-stat-value text-danger">
                            {{ collect($predictions ?? [])->where('prediction_type', 'Bearish')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Predictions -->
        <div class="col-lg-7">
            <div class="info-section-card">
                <div class="section-title">
                    <i class="bi bi-lightning-charge-fill"></i>
                    Community Predictions
                </div>

                @if(!empty($predictions) && count($predictions) > 0)
                    @foreach($predictions as $pred)
                        <a href="{{ route('predictions.view', ['id' => $pred->prediction_id]) }}" class="text-decoration-none">
                            <div class="prediction-card-enhanced">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="mb-3">
                                            @if($pred->prediction_type == 'Bullish')
                                                <span class="prediction-type-badge bullish">
                                                    <i class="bi bi-arrow-up-circle-fill"></i>
                                                    Bullish
                                                </span>
                                            @else
                                                <span class="prediction-type-badge bearish">
                                                    <i class="bi bi-arrow-down-circle-fill"></i>
                                                    Bearish
                                                </span>
                                            @endif
                                        </div>

                                        <div class="prediction-meta">
                                            <i class="bi bi-person-circle me-1"></i>
                                            <strong class="text-white">{{ $pred->user->first_name }} {{ $pred->user->last_name }}</strong>
                                            @if(isset($pred->user->reputation_score))
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="bi bi-star-fill"></i> {{ $pred->user->reputation_score }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="prediction-stats">
                                            @if(isset($pred->target_price))
                                                <span class="stat-badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6;">
                                                    <i class="bi bi-bullseye"></i> ${{ number_format($pred->target_price, 2) }}
                                                </span>
                                            @endif
                                            @if(isset($pred->accuracy))
                                                <span class="stat-badge {{ $pred->accuracy >= 70 ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    <i class="bi bi-graph-up"></i> {{ $pred->accuracy }}% Accurate
                                                </span>
                                            @endif
                                            <span class="stat-badge" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6; border: 1px solid #8b5cf6;">
                                                <i class="bi bi-calendar3"></i> {{ date('M j, Y', strtotime($pred->prediction_date)) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ms-3">
                                        <i class="bi bi-chevron-right text-muted" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="empty-predictions">
                        <div class="empty-icon">
                            <i class="bi bi-chat-square-dots"></i>
                        </div>
                        <h4 class="text-white fw-bold">No Predictions Yet</h4>
                        <p class="text-muted mb-4">Be the first to share your analysis for {{ $stock->symbol }}</p>
                        @auth
                            <a href="{{ url('predictions/create') }}?stock_id={{ $stock->stock_id }}&symbol={{ urlencode($stock->symbol) }}&company_name={{ urlencode($stock->company_name) }}"
                               class="btn action-button-primary">
                                <i class="bi bi-lightning-charge-fill me-2"></i> Create First Prediction
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn action-button-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login to Predict
                            </a>
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-fetch price if not available
document.addEventListener('DOMContentLoaded', function() {
    const priceElement = document.querySelector('.price-value');
    const spinner = document.querySelector('.loading-shimmer');

    if (spinner) {
        // Price is loading, fetch it
        const symbol = '{{ $stock->symbol }}';

        fetch('/api/fetch_stock_price', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ symbol: symbol })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.price) {
                priceElement.innerHTML = '$' + parseFloat(data.data.price).toFixed(2);
                document.querySelector('.price-currency').textContent = 'USD';
            } else {
                priceElement.innerHTML = '<span class="text-warning">N/A</span>';
                document.querySelector('.price-currency').textContent = 'Price unavailable';
            }
        })
        .catch(error => {
            console.error('Error fetching price:', error);
            priceElement.innerHTML = '<span class="text-warning">N/A</span>';
            document.querySelector('.price-currency').textContent = 'Error loading price';
        });
    }

    // Initialize price history chart
    @if(!empty($priceHistory) && count($priceHistory) > 1)
    initPriceChart();
    @endif
});

@if(!empty($priceHistory) && count($priceHistory) > 1)
function initPriceChart() {
    const priceData = @json($priceHistory);
    const ctx = document.getElementById('priceHistoryChart');

    if (!ctx || priceData.length === 0) return;

    // Extract labels and prices
    const labels = priceData.map(item => item.timestamp);
    const prices = priceData.map(item => item.price);

    // Determine if overall trend is up or down
    const isPositive = prices[prices.length - 1] >= prices[0];

    // Color scheme
    const chartColor = isPositive ? '#10b981' : '#ef4444';
    const gridColor = 'rgba(255, 255, 255, 0.1)';
    const textColor = '#a0a0a0';

    // Create gradient fill
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, isPositive ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)');
    gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '{{ $stock->symbol }} Price',
                data: prices,
                borderColor: chartColor,
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: chartColor,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#2a2a2a',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: chartColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            const dataIndex = context[0].dataIndex;
                            return priceData[dataIndex].date;
                        },
                        label: function(context) {
                            return '$' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        maxTicksLimit: 6
                    }
                },
                y: {
                    display: true,
                    position: 'right',
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    },
                    ticks: {
                        color: textColor,
                        font: { size: 11 },
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}
@endif
</script>
@endsection
