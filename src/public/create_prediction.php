@extends('layouts.app')

@section('content')
<div class="container form-container">
    <h2 class="text-center mb-4">{{ $isEditing ? 'Edit' : 'Create New' }} Stock Prediction</h2>

    <div class="prediction-form bg-dark p-4 rounded">
        <form id="prediction-form" action="{{ route('predictions.' . ($isEditing ? 'update' : 'store')) }}" method="POST">
            @csrf
            @if ($isEditing)
                @method('PUT')
                <input type="hidden" name="prediction_id" value="{{ $prediction['prediction_id'] }}">
            @endif

            <div class="mb-3">
                <label for="stock-search" class="form-label">Stock Symbol</label>
                @if ($isEditing)
                    <input type="text" class="form-control" value="{{ $prediction['symbol'] }} - {{ $prediction['company_name'] }}" readonly>
                    <input type="hidden" name="stock_id" value="{{ $prediction['stock_id'] }}" required>
                @else
                    <input type="text" class="form-control" id="stock-search" placeholder="Search for a stock symbol or name...">
                    <div id="stock-suggestions" class="mt-2"></div>
                    <input type="hidden" id="stock_id" name="stock_id" required>
                @endif
            </div>

            <div class="mb-3">
                <label for="prediction_type" class="form-label">Prediction Type</label>
                <select class="form-select" name="prediction_type" required>
                    <option value="" disabled {{ !$isEditing ? 'selected' : '' }}>Select prediction type</option>
                    <option value="Bullish" {{ old('prediction_type', $prediction['prediction_type'] ?? '') == 'Bullish' ? 'selected' : '' }}>Bullish</option>
                    <option value="Bearish" {{ old('prediction_type', $prediction['prediction_type'] ?? '') == 'Bearish' ? 'selected' : '' }}>Bearish</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="target_price" class="form-label">Target Price (optional)</label>
                <input type="number" class="form-control" name="target_price" step="0.01" min="0"
                       value="{{ old('target_price', $prediction['target_price'] ?? '') }}">
                <small class="form-text text-muted">Your predicted price target for this stock</small>
            </div>

            <div class="mb-3">
                <label for="end_date" class="form-label">Timeframe (End Date)</label>
                <input type="date" class="form-control" name="end_date" required
                       value="{{ old('end_date', $prediction['end_date'] ?? '') }}">
                <small class="form-text text-muted">When do you expect your prediction to be fulfilled?</small>
            </div>

            <div class="mb-3">
                <label for="reasoning" class="form-label">Reasoning</label>
                <textarea class="form-control" name="reasoning" rows="4" required>{{ old('reasoning', $prediction['reasoning'] ?? '') }}</textarea>
                <small class="form-text text-muted">Explain why you believe this prediction will come true</small>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    {{ $isEditing ? 'Update' : 'Create' }} Prediction
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/prediction/prediction.js') }}"></script>
@endsection