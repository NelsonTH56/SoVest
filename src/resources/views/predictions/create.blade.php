@extends('layouts.app')

@section('title', $pageTitle ?? 'Create Prediction')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
@endsection

@section('content')
    <div class="container mt-4">
        <h2 class="text-center mb-4">{{ $isEditing ? 'Edit' : 'Create New' }} Stock Prediction</h2>

        <!-- Prediction Guide Card -->
        <div class="row mb-4">
            <div class="col-md-8 offset-md-2">
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Prediction Guide</h5>
                        <p class="card-text">Creating effective predictions increases your reputation when they're accurate.
                            Here's how to make a good prediction:</p>
                        <ul class="mb-0">
                            <li><strong>Be specific</strong> - Clearly state what you expect to happen with the stock.</li>
                            <li><strong>Choose a reasonable timeframe</strong> - Not too short or too long.</li>
                            <li><strong>Provide detailed reasoning</strong> - Include catalysts, market trends, or events
                                that support your prediction.</li>
                            <li><strong>Set a target price</strong> (optional) - Adding a price target makes your prediction
                                more measurable.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Errors Display -->
        @if ($errors->any())
            <div class="row mb-4">
                <div class="col-md-8 offset-md-2">
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
            <div class="col-md-8 offset-md-2">
                <div class="prediction-form card shadow-sm">
                    <div class="card-body">
                        <form id="prediction-form"
                            action="{{ $isEditing ? route('predictions.update', $prediction['prediction_id']) : route('predictions.store') }}"
                            method="post"
                            autocomplete="off">
                            @csrf
                            <input type="hidden" name="action" value="{{ $isEditing ? 'update' : 'create' }}">
                            @if ($isEditing)
                                <input type="hidden" name="prediction_id" value="{{ $prediction['prediction_id'] }}">
                            @endif

                            <div class="mb-4">
                                <label for="stock-search" class="form-label fw-bold">
                                    <i class="bi bi-search me-1 text-primary"></i>Stock Symbol
                                </label>
                                <div class="input-group">
                                    @if ($isEditing)
                                        <input type="text" class="form-control" id="stock-search"
                                            value="{{ $prediction['symbol'] . ' - ' . $prediction['company_name'] }}" readonly>
                                        <input type="hidden" id="stock_id" name="stock_id" value="{{ $prediction['stock_id'] }}"
                                            required>
                                    @elseif (isset($hasPreselectedStock) && $hasPreselectedStock)
                                        <input type="text" class="form-control" id="stock-search"
                                            value="{{ $prediction['symbol'] . ' - ' . $prediction['company_name'] }}" readonly>
                                        <input type="hidden" id="stock_id" name="stock_id" value="{{ $prediction['stock_id'] }}"
                                            required>
                                    @else
                                        <input type="text" class="form-control @error('stock_id') is-invalid @enderror"
                                            id="stock-search" placeholder="Search for a stock symbol or name...">
                                        <span class="input-group-text bg-light text-muted" data-bs-toggle="tooltip"
                                            title="Type at least 2 characters to search for a stock. Select one from the dropdown.">
                                            <i class="bi bi-info-circle"></i>
                                        </span>
                                        <input type="hidden" id="stock_id" name="stock_id" required>
                                    @endif
                                </div>
                                @if ($isEditing == false && (isset($hasPreselectedStock) == false || $hasPreselectedStock == false))
                                    <div id="stock-suggestions" class="mt-2"></div>
                                    <div class="form-text">Start typing to search for stocks. Select one from the dropdown
                                        suggestions.</div>
                                @endif
                                @error('stock_id')
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="prediction_type" class="form-label fw-bold">
                                    <i class="bi bi-arrow-up-right me-1 text-success"></i>Prediction Type
                                </label>
                                <select class="form-select @error('prediction_type') is-invalid @enderror"
                                    id="prediction_type" name="prediction_type" required>
                                    <option value="" {{ !$isEditing ? 'selected' : '' }} disabled>Select prediction type
                                    </option>
                                    <option value="Bullish" {{ $isEditing && $prediction['prediction_type'] == 'Bullish' ? 'selected' : '' }}>
                                        Bullish (Stock will rise)
                                    </option>
                                    <option value="Bearish" {{ $isEditing && $prediction['prediction_type'] == 'Bearish' ? 'selected' : '' }}>
                                        Bearish (Stock will fall)
                                    </option>
                                </select>
                                <div class="form-text">
                                    <span class="badge bg-success me-1">Bullish</span>: You expect the stock price to
                                    increase over time.
                                    <br>
                                    <span class="badge bg-danger me-1">Bearish</span>: You expect the stock price to
                                    decrease over time.
                                </div>
                                @error('prediction_type')
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="target_price" class="form-label fw-bold">
                                    <i class="bi bi-currency-dollar me-1 text-warning"></i>Target Price (optional)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control @error('target_price') is-invalid @enderror"
                                        id="target_price" name="target_price" step="0.01" min="0"
                                        value="{{ $isEditing && $prediction['target_price'] ? $prediction['target_price'] : old('target_price', '') }}">
                                    <span class="input-group-text bg-light text-muted" data-bs-toggle="tooltip"
                                        title="Specify a price target to make your prediction more precise. This helps measure accuracy.">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                </div>
                                <div class="form-text">Your predicted price target makes your prediction more measurable and
                                    specific. Predictions with targets gain more reputation when correct.</div>
                                @error('target_price')
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="end_date" class="form-label fw-bold">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i>Timeframe (End Date)
                                </label>
                                <div class="input-group">
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                        id="end_date" name="end_date" required
                                        value="{{ $isEditing ? date('Y-m-d', strtotime($prediction['end_date'])) : old('end_date', '') }}">
                                    <span class="input-group-text bg-light text-muted" data-bs-toggle="tooltip"
                                        title="The date by which your prediction should be fulfilled. Must be in the future.">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                </div>
                                <div class="form-text">When do you expect your prediction to be fulfilled? Choose a
                                    realistic timeframe — not too short (at least a week) and not too far in the future
                                    (ideally within 6 months).</div>
                                <div id="end-date-feedback" class="invalid-feedback"></div>
                                @error('end_date')
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="reasoning" class="form-label fw-bold">
                                    <i class="bi bi-chat-square-text me-1 text-info"></i>Reasoning
                                </label>
                                <div class="input-group">
                                    <textarea class="form-control @error('reasoning') is-invalid @enderror" id="reasoning"
                                        name="reasoning" rows="4"
                                        required>{{ $isEditing ? $prediction['reasoning'] : old('reasoning', '') }}</textarea>
                                    <span class="input-group-text bg-light text-muted" data-bs-toggle="tooltip"
                                        title="Explain your research and why you believe this prediction will come true. Include catalysts, financials, or news.">
                                        <i class="bi bi-info-circle"></i>
                                    </span>
                                </div>
                                <div class="form-text">Explain why you believe this prediction will come true. Include
                                    specific factors like:</div>
                                <ul class="form-text small list-unstyled ms-2">
                                    <li><i class="bi bi-check-circle-fill text-success me-1"></i>Financial results or
                                        upcoming earnings</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-1"></i>Product launches or company
                                        developments</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-1"></i>Market trends or industry
                                        changes</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-1"></i>Technical indicators or
                                        chart patterns</li>
                                </ul>
                                <div id="reasoning-counter" class="text-muted small mt-1">0 characters (minimum 30
                                    recommended)</div>
                                @error('reasoning')
                                    <div class="text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-lightning-charge-fill me-1"></i>{{ $isEditing ? 'Update' : 'Create' }}
                                    Prediction
                                </button>
                            </div>
                        </form>
                    </div>
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
            deletePrediction: '{{ route("api.predictions.delete", 0) }}'
        };
    </script>
@endsection