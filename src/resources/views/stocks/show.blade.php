@extends('layouts.app')

@section('styles')
<style>
    .stock-detail-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .stock-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        color: white;
    }

    .stock-symbol {
        font-size: 3rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stock-company {
        font-size: 1.5rem;
        opacity: 0.9;
    }

    .price-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }

    .current-price {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .price-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .info-card {
        background: #2d3748;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #4a5568;
    }

    .info-label {
        color: #a0aec0;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .info-value {
        color: #e2e8f0;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .prediction-card {
        background: #2d3748;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid #4a5568;
        transition: all 0.3s ease;
    }

    .prediction-card:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')
<div class="container stock-detail-container mt-4">
    <!-- Stock Header -->
    <div class="stock-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="stock-symbol">{{ $stock->symbol }}</div>
                <div class="stock-company">{{ $stock->company_name }}</div>
                <div class="mt-2">
                    <span class="badge bg-light text-dark">{{ $stock->sector }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="price-card">
                    <div class="price-label">Current Price</div>
                    @if(isset($currentPrice) && $currentPrice !== null)
                        <div class="current-price">
                            ${{ number_format($currentPrice, 2) }}
                        </div>
                        <div class="price-label">USD</div>
                    @else
                        <div class="current-price">
                            <span class="loading-spinner"></span>
                        </div>
                        <div class="price-label">Fetching price...</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            @auth
                <a href="{{ url('predictions/create') }}?stock_id={{ $stock->stock_id }}&symbol={{ urlencode($stock->symbol) }}&company_name={{ urlencode($stock->company_name) }}"
                   class="btn btn-success btn-lg me-2">
                    <i class="bi bi-lightning-charge"></i> Create Prediction
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-success btn-lg me-2">
                    <i class="bi bi-box-arrow-in-right"></i> Login to Create Prediction
                </a>
            @endauth
            <a href="{{ url('search') }}?query={{ urlencode($stock->symbol) }}&type=stocks"
               class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-arrow-left"></i> Back to Search
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Stock Information -->
        <div class="col-md-6">
            <h3 class="mb-3">Stock Information</h3>

            <div class="info-card">
                <div class="info-label">Symbol</div>
                <div class="info-value">{{ $stock->symbol }}</div>
            </div>

            <div class="info-card">
                <div class="info-label">Company Name</div>
                <div class="info-value">{{ $stock->company_name }}</div>
            </div>

            <div class="info-card">
                <div class="info-label">Sector</div>
                <div class="info-value">{{ $stock->sector }}</div>
            </div>

            @if(isset($currentPrice) && $currentPrice !== null)
            <div class="info-card">
                <div class="info-label">Latest Price</div>
                <div class="info-value">${{ number_format($currentPrice, 2) }} USD</div>
            </div>
            @endif

            @if(isset($latestPriceDate))
            <div class="info-card">
                <div class="info-label">Price Date</div>
                <div class="info-value">{{ date('F j, Y', strtotime($latestPriceDate)) }}</div>
            </div>
            @endif
        </div>

        <!-- Related Predictions -->
        <div class="col-md-6">
            <h3 class="mb-3">Related Predictions</h3>

            @if(!empty($predictions) && count($predictions) > 0)
                @foreach($predictions as $pred)
                    <a href="{{ route('predictions.view', ['id' => $pred->prediction_id]) }}" class="text-decoration-none">
                        <div class="prediction-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        @if($pred->prediction_type == 'Bullish')
                                            <i class="bi bi-arrow-up-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                            <span class="badge bg-success">Bullish</span>
                                        @else
                                            <i class="bi bi-arrow-down-circle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
                                            <span class="badge bg-danger">Bearish</span>
                                        @endif
                                    </div>

                                    <div class="text-light mb-2">
                                        By {{ $pred->user->first_name }} {{ $pred->user->last_name }}
                                        @if(isset($pred->user->reputation_score))
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="bi bi-star-fill"></i> {{ $pred->user->reputation_score }} pts
                                            </span>
                                        @endif
                                    </div>

                                    @if(isset($pred->target_price))
                                        <div class="text-muted">
                                            Target: ${{ number_format($pred->target_price, 2) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="text-end">
                                    @if(isset($pred->accuracy))
                                        <div class="badge {{ $pred->accuracy >= 70 ? 'bg-success' : 'bg-warning' }} mb-2">
                                            {{ $pred->accuracy }}% Accurate
                                        </div>
                                    @endif

                                    <div class="text-muted small">
                                        {{ date('M j, Y', strtotime($pred->prediction_date)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="info-card text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                    <p class="mt-3 mb-0 text-muted">No predictions yet for this stock</p>
                    @auth
                        <a href="{{ url('predictions/create') }}?stock_id={{ $stock->stock_id }}&symbol={{ urlencode($stock->symbol) }}&company_name={{ urlencode($stock->company_name) }}"
                           class="btn btn-success mt-3">
                            <i class="bi bi-lightning-charge"></i> Be the first to predict
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-success mt-3">
                            <i class="bi bi-box-arrow-in-right"></i> Login to predict
                        </a>
                    @endauth
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-fetch price if not available
document.addEventListener('DOMContentLoaded', function() {
    const priceElement = document.querySelector('.current-price');
    const spinner = document.querySelector('.loading-spinner');

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
                priceElement.innerHTML = '$' + data.data.price.toFixed(2);
                document.querySelector('.price-label').textContent = 'USD';
            } else {
                priceElement.innerHTML = '<span class="text-warning">N/A</span>';
                document.querySelector('.price-label').textContent = 'Price unavailable';
            }
        })
        .catch(error => {
            console.error('Error fetching price:', error);
            priceElement.innerHTML = '<span class="text-warning">N/A</span>';
            document.querySelector('.price-label').textContent = 'Error fetching price';
        });
    }
});
</script>
@endsection
