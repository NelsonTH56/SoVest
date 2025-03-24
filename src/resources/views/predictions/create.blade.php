@extends('layouts.app')

@section('title', $pageTitle ?? 'Create Prediction')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
@endsection

@section('content')
    <div class="container mt-4">
        <h2 class="text-center mb-4">{{ $isEditing ? 'Edit' : 'Create New' }} Stock Prediction</h2>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="prediction-form card shadow-sm">
                    <div class="card-body">
                        <form id="prediction-form"
                            action="{{ $isEditing ? route('predictions.update') : route('predictions.store') }}"
                            method="post">
                            @csrf
                            <input type="hidden" name="action" value="{{ $isEditing ? 'update' : 'create' }}">
                            @if ($isEditing)
                                <input type="hidden" name="prediction_id" value="{{ $prediction['prediction_id'] }}">
                            @endif

                            <div class="mb-3">
                                <label for="stock-search" class="form-label">Stock Symbol</label>
                                @if ($isEditing)
                                    <input type="text" class="form-control" id="stock-search"
                                        value="{{ $prediction['symbol'] . ' - ' . $prediction['company_name'] }}" readonly>
                                    <input type="hidden" id="stock_id" name="stock_id" value="{{ $prediction['stock_id'] }}"
                                        required>
                                @else
                                    <input type="text" class="form-control" id="stock-search"
                                        placeholder="Search for a stock symbol or name...">
                                    <div id="stock-suggestions" class="mt-2"></div>
                                    <input type="hidden" id="stock_id" name="stock_id" required>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label for="prediction_type" class="form-label">Prediction Type</label>
                                <select class="form-select" id="prediction_type" name="prediction_type" required>
                                    <option value="" {{ !$isEditing ? 'selected' : '' }} disabled>Select prediction type
                                    </option>
                                    <option value="Bullish" {{ $isEditing && $prediction['prediction_type'] == 'Bullish' ? 'selected' : '' }}>Bullish (Stock will rise)</option>
                                    <option value="Bearish" {{ $isEditing && $prediction['prediction_type'] == 'Bearish' ? 'selected' : '' }}>Bearish (Stock will fall)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="target_price" class="form-label">Target Price (optional)</label>
                                <input type="number" class="form-control" id="target_price" name="target_price" step="0.01"
                                    min="0"
                                    value="{{ $isEditing && $prediction['target_price'] ? $prediction['target_price'] : old('target_price', '') }}">
                                <small class="form-text text-muted">Your predicted price target for this stock</small>
                            </div>

                            <div class="mb-3">
                                <label for="end_date" class="form-label">Timeframe (End Date)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required
                                    value="{{ $isEditing ? date('Y-m-d', strtotime($prediction['end_date'])) : old('end_date', '') }}">
                                <small class="form-text text-muted">When do you expect your prediction to be
                                    fulfilled?</small>
                            </div>

                            <div class="mb-3">
                                <label for="reasoning" class="form-label">Reasoning</label>
                                <textarea class="form-control" id="reasoning" name="reasoning" rows="4"
                                    required>{{ $isEditing ? $prediction['reasoning'] : old('reasoning', '') }}</textarea>
                                <small class="form-text text-muted">Explain why you believe this prediction will come
                                    true</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    {{ $isEditing ? 'Update' : 'Create' }} Prediction
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
    <script src="{{ asset('js/prediction.js') }}"></script>
    <script>
        // Update API endpoint for prediction.js to use Laravel routes
        const apiEndpoints = {
            searchStocks: '{{ route("api.search_stocks") }}'
        };

        document.addEventListener('DOMContentLoaded', function () {
            // End date validation - must be in the future
            const endDateInput = document.getElementById('end_date');
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            const minDate = tomorrow.toISOString().split('T')[0];
            endDateInput.min = minDate;

            endDateInput.addEventListener('change', function () {
                const selectedDate = new Date(this.value);
                if (selectedDate <= today) {
                    this.setCustomValidity('End date must be in the future');
                } else {
                    this.setCustomValidity('');
                }
            });
        });
    </script>
@endsection