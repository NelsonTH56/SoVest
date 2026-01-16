@extends('layouts.app')

@section('title', $pageTitle ?? 'Create Prediction')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
    <div class="container mt-4 prediction-create-container">
        <!-- Progress Steps -->
        <div class="prediction-steps mb-5">
            <div class="steps-container">
                <div class="step" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Stock</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Direction</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Target</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">Analysis</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-label">Confirm</div>
                </div>
            </div>
        </div>

        <h2 class="text-center mb-4 prediction-title">
            <span class="gradient-text-form">{{ $isEditing ? 'Edit' : 'Make Your' }} Prediction</span>
        </h2>

        <style>
        .gradient-text-form {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        </style>

        <!-- Errors Display -->
        @if ($errors->any())
            <div class="row mb-4">
                <div class="col-md-10 offset-md-1 col-lg-8 offset-lg-2">
                    <div class="alert alert-danger">
                        <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-10 offset-md-1 col-lg-8 offset-lg-2">
                <div class="prediction-form-card">
                    <form id="prediction-form"
                        action="{{ $isEditing ? route('predictions.update', $prediction['prediction_id']) : route('predictions.store') }}"
                        method="post"
                        autocomplete="off">
                        @csrf
                        <input type="hidden" name="action" value="{{ $isEditing ? 'update' : 'create' }}">
                        @if ($isEditing)
                            <input type="hidden" name="prediction_id" value="{{ $prediction['prediction_id'] }}">
                        @endif

                        <!-- Step 1: Stock Selection -->
                        <div class="form-step active" data-step="1">
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                                <h3 class="step-title">Select a Stock</h3>
                                <p class="step-description">Choose the stock you want to make a prediction about</p>

                                <div class="form-group-enhanced">
                                    <label for="stock-search" class="form-label-enhanced">
                                        Stock Symbol or Company Name
                                    </label>
                                    <div class="input-with-icon">
                                        @if ($isEditing)
                                            <input type="text" class="form-control-enhanced" id="stock-search"
                                                value="{{ $prediction['symbol'] . ' - ' . $prediction['company_name'] }}" readonly>
                                            <input type="hidden" id="stock_id" name="stock_id" value="{{ $prediction['stock_id'] }}"
                                                required>
                                        @elseif (isset($hasPreselectedStock) && $hasPreselectedStock)
                                            <input type="text" class="form-control-enhanced" id="stock-search"
                                                value="{{ $prediction['symbol'] . ' - ' . $prediction['company_name'] }}" readonly>
                                            <input type="hidden" id="stock_id" name="stock_id" value="{{ $prediction['stock_id'] }}"
                                                required>
                                        @else
                                            <i class="bi bi-search input-icon"></i>
                                            <input type="text" class="form-control-enhanced @error('stock_id') is-invalid @enderror"
                                                id="stock-search" placeholder="Search for AAPL, Tesla, Microsoft...">
                                            <input type="hidden" id="stock_id" name="stock_id" required>
                                        @endif
                                    </div>
                                    @if ($isEditing == false && (isset($hasPreselectedStock) == false || $hasPreselectedStock == false))
                                        <div id="stock-suggestions" class="mt-2"></div>
                                        <div class="form-hint">
                                            <i class="bi bi-info-circle"></i>
                                            Start typing to search. You can search by ticker symbol or company name.
                                        </div>
                                    @endif

                                    <!-- Stock Info Card (appears after selection) -->
                                    <div id="stock-info-card" class="stock-info-card" style="display: none;">
                                        <div class="stock-info-header">
                                            <div>
                                                <h4 id="stock-name" class="stock-name"></h4>
                                                <p id="stock-symbol" class="stock-symbol"></p>
                                            </div>
                                            <div class="stock-price-section">
                                                <div id="current-price-loader" class="price-loader">
                                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                </div>
                                                <div id="current-price-display" style="display: none;">
                                                    <span class="current-price-label">Current Price</span>
                                                    <span id="current-price" class="current-price">$0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @error('stock_id')
                                        <div class="error-message">
                                            <i class="bi bi-exclamation-circle"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Prediction Type -->
                        <div class="form-step" data-step="2">
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <h3 class="step-title">What's Your Prediction?</h3>
                                <p class="step-description">Will the stock price go up or down?</p>

                                <div class="form-group-enhanced">
                                    <div class="prediction-type-options">
                                        <input type="radio" class="btn-check" name="prediction_type" id="bullish" value="Bullish"
                                            {{ $isEditing && $prediction['prediction_type'] == 'Bullish' ? 'checked' : '' }}>
                                        <label class="prediction-type-card bullish-card" for="bullish">
                                            <div class="prediction-icon">
                                                <i class="bi bi-arrow-up-circle-fill"></i>
                                            </div>
                                            <h4>Bullish</h4>
                                            <p>Stock price will rise</p>
                                            <div class="prediction-badge">
                                                <i class="bi bi-chevron-up"></i> Upward trend
                                            </div>
                                        </label>

                                        <input type="radio" class="btn-check" name="prediction_type" id="bearish" value="Bearish"
                                            {{ $isEditing && $prediction['prediction_type'] == 'Bearish' ? 'checked' : '' }}>
                                        <label class="prediction-type-card bearish-card" for="bearish">
                                            <div class="prediction-icon">
                                                <i class="bi bi-arrow-down-circle-fill"></i>
                                            </div>
                                            <h4>Bearish</h4>
                                            <p>Stock price will fall</p>
                                            <div class="prediction-badge">
                                                <i class="bi bi-chevron-down"></i> Downward trend
                                            </div>
                                        </label>
                                    </div>
                                    @error('prediction_type')
                                        <div class="error-message">
                                            <i class="bi bi-exclamation-circle"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Target Price & Timeframe -->
                        <div class="form-step" data-step="3">
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="bi bi-bullseye"></i>
                                </div>
                                <h3 class="step-title">Set Your Target</h3>
                                <p class="step-description">Define your price target and timeframe</p>

                                <!-- Price Inputs Row -->
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="form-group-enhanced">
                                            <label for="target_price" class="form-label-enhanced">
                                                <i class="bi bi-currency-dollar text-warning"></i>
                                                Target Price
                                            </label>
                                            <div class="input-with-icon target-input-wrapper">
                                                <span class="dollar-sign">$</span>
                                                <input type="number" class="form-control-enhanced form-control-lg ps-4 @error('target_price') is-invalid @enderror"
                                                    id="target_price" name="target_price" step="0.01" min="0.01"
                                                    placeholder="0.00"
                                                    value="{{ $isEditing && $prediction['target_price'] ? $prediction['target_price'] : old('target_price', '') }}">
                                            </div>
                                            @error('target_price')
                                                <div class="error-message">
                                                    <i class="bi bi-exclamation-circle"></i>{{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group-enhanced">
                                            <label for="percent_change" class="form-label-enhanced">
                                                <i class="bi bi-percent text-info"></i>
                                                Percent Change
                                            </label>
                                            <div class="input-with-icon target-input-wrapper">
                                                <input type="number" class="form-control-enhanced form-control-lg"
                                                    id="percent_change" step="0.01"
                                                    placeholder="0.00">
                                                <span class="percent-sign">%</span>
                                            </div>
                                            <div class="form-hint">
                                                <i class="bi bi-arrow-left-right"></i>
                                                <span>Updates automatically with target price</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-hint mb-4">
                                    <i class="bi bi-lightbulb"></i>
                                    <span id="price-suggestion">Enter either target price or percent change - they stay in sync</span>
                                </div>

                                <!-- Timeframe Row -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group-enhanced">
                                            <label for="end_date" class="form-label-enhanced">
                                                <i class="bi bi-calendar-event text-primary"></i>
                                                Timeframe (End Date)
                                            </label>
                                            <div class="input-with-icon target-input-wrapper">
                                                <i class="bi bi-calendar3 input-icon"></i>
                                                <input type="date" class="form-control-enhanced form-control-lg @error('end_date') is-invalid @enderror"
                                                    id="end_date" name="end_date" required
                                                    value="{{ $isEditing ? date('Y-m-d', strtotime($prediction['end_date'])) : old('end_date', '') }}">
                                            </div>
                                            <div class="form-hint">
                                                <i class="bi bi-info-circle"></i>
                                                Minimum 7 days from today
                                            </div>
                                            <div id="end-date-feedback" class="invalid-feedback"></div>
                                            @error('end_date')
                                                <div class="error-message">
                                                    <i class="bi bi-exclamation-circle"></i>{{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Reasoning -->
                        <div class="form-step" data-step="4">
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="bi bi-chat-quote"></i>
                                </div>
                                <h3 class="step-title">Share Your Analysis</h3>
                                <p class="step-description">Explain why you believe in this prediction</p>

                                <div class="form-group-enhanced">
                                    <label for="reasoning" class="form-label-enhanced">
                                        Your Reasoning
                                    </label>
                                    <textarea class="form-control-enhanced textarea-enhanced @error('reasoning') is-invalid @enderror"
                                        id="reasoning"
                                        name="reasoning"
                                        rows="6"
                                        placeholder="What makes you confident about this prediction? Consider mentioning:
• Financial results or upcoming earnings
• Product launches or company developments
• Market trends or industry changes
• Technical indicators or chart patterns"
                                        required>{{ $isEditing ? $prediction['reasoning'] : old('reasoning', '') }}</textarea>

                                    <div class="reasoning-footer">
                                        <div class="form-hint">
                                            <i class="bi bi-stars"></i>
                                            Quality reasoning increases your credibility score
                                        </div>
                                        <div id="reasoning-counter" class="character-counter">0 / 30 min</div>
                                    </div>

                                    @error('reasoning')
                                        <div class="error-message">
                                            <i class="bi bi-exclamation-circle"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Quick Tips -->
                                <div class="tips-card">
                                    <h5 class="tips-title">
                                        <i class="bi bi-lightbulb-fill"></i>
                                        Tips for Great Predictions
                                    </h5>
                                    <ul class="tips-list">
                                        <li><i class="bi bi-check-circle-fill"></i>Be specific with your analysis and data points</li>
                                        <li><i class="bi bi-check-circle-fill"></i>Mention upcoming catalysts or events</li>
                                        <li><i class="bi bi-check-circle-fill"></i>Consider both technical and fundamental factors</li>
                                        <li><i class="bi bi-check-circle-fill"></i>Explain risks or potential challenges</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5: Confirmation -->
                        <div class="form-step" data-step="5">
                            <div class="step-content">
                                <div class="step-icon confirmation-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h3 class="step-title">Confirm Your Prediction</h3>
                                <p class="step-description">Review your prediction before publishing</p>

                                <!-- Warning Banner -->
                                <div class="confirmation-warning">
                                    <div class="warning-icon">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <div class="warning-content">
                                        <strong>Predictions are permanent</strong>
                                        <p>Once submitted, you cannot edit the direction, target price, or timeframe. This ensures accountability and fair scoring.</p>
                                    </div>
                                </div>

                                <!-- Prediction Summary -->
                                <div class="confirmation-summary">
                                    <h5 class="summary-title">
                                        <i class="bi bi-clipboard-check"></i>
                                        Prediction Summary
                                    </h5>

                                    <div class="summary-grid">
                                        <div class="summary-item">
                                            <span class="summary-label">Stock</span>
                                            <span id="confirm-stock" class="confirm-value">-</span>
                                        </div>

                                        <div class="summary-item">
                                            <span class="summary-label">Current Price</span>
                                            <span id="confirm-current-price" class="confirm-value">-</span>
                                        </div>

                                        <div class="summary-item">
                                            <span class="summary-label">Direction</span>
                                            <span id="confirm-direction" class="confirm-value">-</span>
                                        </div>

                                        <div class="summary-item">
                                            <span class="summary-label">Target Price</span>
                                            <span id="confirm-target-price" class="confirm-value">-</span>
                                        </div>

                                        <div class="summary-item">
                                            <span class="summary-label">Percent Change</span>
                                            <span id="confirm-percent-change" class="confirm-value">-</span>
                                        </div>

                                        <div class="summary-item">
                                            <span class="summary-label">Timeframe</span>
                                            <span id="confirm-timeframe" class="confirm-value">-</span>
                                        </div>
                                    </div>

                                    <div class="summary-reasoning">
                                        <span class="summary-label">Your Analysis</span>
                                        <p id="confirm-reasoning" class="confirm-reasoning-text">-</p>
                                    </div>
                                </div>

                                <!-- Confirmation Checkbox -->
                                <div class="confirmation-checkbox">
                                    <label class="confirm-label">
                                        <input type="checkbox" id="confirm-checkbox" required>
                                        <span class="checkmark"></span>
                                        <span class="confirm-text">I understand that this prediction cannot be edited after submission and will affect my reputation score.</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="form-navigation">
                            <button type="button" class="btn btn-nav btn-prev" id="prev-btn" style="display: none;">
                                <i class="bi bi-arrow-left"></i> Previous
                            </button>
                            <button type="button" class="btn btn-nav btn-next" id="next-btn">
                                Next <i class="bi bi-arrow-right"></i>
                            </button>
                            <button type="submit" class="btn btn-submit" id="submit-btn" style="display: none;">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i>{{ $isEditing ? 'Update' : 'Publish' }} Prediction
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ asset('js/prediction.js') }}"></script>
    <script type="text/javascript">
        // Update API endpoint for prediction.js to use Laravel routes
        const apiEndpoints = {
            searchStocks: '{{ route("api.search.stocks") }}',
            deletePrediction: '{{ route("api.predictions.delete", 0) }}',
            getStockPrice: '{{ url("api/stocks") }}' // Will append /{symbol}/price
        };
    </script>
@endsection
