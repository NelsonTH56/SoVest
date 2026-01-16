@extends('layouts.app')

@section('styles')
<style>
    .stock-detail-hero {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d1f3f 50%, #1f2937 100%);
        border-radius: 24px;
        padding: 3rem;
        margin-bottom: 2rem;
        border: 1px solid rgba(139, 92, 246, 0.3);
        box-shadow: 0 10px 50px rgba(0, 0, 0, 0.4);
        position: relative;
        overflow: hidden;
    }

    .stock-detail-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }

    .stock-content {
        position: relative;
        z-index: 1;
    }

    .stock-symbol-display {
        font-size: 3.5rem;
        font-weight: 900;
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }

    .stock-company-name {
        font-size: 1.8rem;
        color: #e0e0e0;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .sector-badge {
        background: rgba(16, 185, 129, 0.2);
        border: 2px solid #10b981;
        color: #10b981;
        padding: 0.6rem 1.2rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-block;
    }

    .price-display-card {
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(20px);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
    }

    .price-label-text {
        color: #a0a0a0;
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        margin-bottom: 0.8rem;
    }

    .price-value {
        font-size: 3rem;
        font-weight: 900;
        color: #fff;
        margin-bottom: 0.5rem;
        text-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
    }

    .price-currency {
        color: #a0a0a0;
        font-size: 1rem;
        font-weight: 600;
    }

    .action-button-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        border-radius: 14px;
        padding: 1rem 2rem;
        font-weight: 700;
        font-size: 1.1rem;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .action-button-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.5);
        background: linear-gradient(135deg, #059669, #047857);
    }

    .action-button-secondary {
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 14px;
        padding: 1rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        color: #fff;
        transition: all 0.3s ease;
    }

    .action-button-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-3px);
        color: #fff;
    }

    .info-section-card {
        background: #1a1a1a;
        border: 1px solid #2c2c2c;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .info-section-card:hover {
        border-color: #10b981;
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.15);
    }

    .section-title {
        font-size: 1.8rem;
        font-weight: 800;
        color: #fff;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 0.8rem;
        background: linear-gradient(135deg, #10b981, #3b82f6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .info-row {
        background: #2a2a2a;
        border: 1px solid #3a3a3a;
        border-radius: 12px;
        padding: 1.2rem;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
    }

    .info-row:hover {
        background: #2d2d2d;
        border-color: #10b981;
    }

    .info-row-label {
        color: #a0a0a0;
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .info-row-value {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 700;
    }

    .prediction-card-enhanced {
        background: #1a1a1a;
        border: 1px solid #2c2c2c;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .prediction-card-enhanced:hover {
        transform: translateY(-4px);
        border-color: #10b981;
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.2);
    }

    .prediction-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem;
    }

    .prediction-type-badge.bullish {
        background: rgba(16, 185, 129, 0.2);
        border: 2px solid #10b981;
        color: #10b981;
    }

    .prediction-type-badge.bearish {
        background: rgba(239, 68, 68, 0.2);
        border: 2px solid #ef4444;
        color: #ef4444;
    }

    .prediction-meta {
        color: #a0a0a0;
        font-size: 0.95rem;
        margin-top: 0.8rem;
    }

    .prediction-stats {
        display: flex;
        gap: 0.8rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .stat-badge {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .empty-predictions {
        text-align: center;
        padding: 4rem 2rem;
        background: #2a2a2a;
        border: 2px dashed #3a3a3a;
        border-radius: 16px;
    }

    .empty-icon {
        font-size: 4rem;
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1rem;
    }

    .loading-shimmer {
        display: inline-block;
        width: 24px;
        height: 24px;
        border: 3px solid rgba(16, 185, 129, 0.2);
        border-radius: 50%;
        border-top-color: #10b981;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .quick-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .quick-stat-item {
        background: #2a2a2a;
        padding: 1rem;
        border-radius: 12px;
        border: 1px solid #3a3a3a;
        text-align: center;
    }

    .quick-stat-label {
        color: #a0a0a0;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }

    .quick-stat-value {
        color: #fff;
        font-size: 1.3rem;
        font-weight: 700;
    }

    .back-link {
        color: #a0a0a0;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .back-link:hover {
        color: #10b981;
        transform: translateX(-3px);
    }

    .back-link i {
        transition: transform 0.2s ease;
    }

    .back-link:hover i {
        transform: translateX(-3px);
    }
</style>
@endsection

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
});
</script>
@endsection
