@extends('layouts.app')

@section('title', $pageTitle ?? 'My Predictions')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
@endsection

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-6">{{ $pageTitle ?? 'My Predictions' }}</h1>
            <a href="{{ route('predictions.create') }}" class="btn btn-primary">Create New Prediction</a>
        </div>

        @if(empty($predictions))
            <div class="empty-state prediction-card">
                <h4>No predictions yet</h4>
                <p>You haven't made any stock predictions yet. Create your first prediction to start building your reputation!
                </p>
                <a href="{{ route('predictions.create') }}" class="btn btn-primary mt-3">Create Your First Prediction</a>
            </div>
        @else
            @foreach($predictions as $prediction)
                <div class="prediction-card">
                    <div class="prediction-header">
                        <h4>{{ $prediction['symbol'] }} - {{ $prediction['company_name'] }}</h4>
                        <span class="badge {{ $prediction['prediction_type'] == 'Bullish' ? 'bg-success' : 'bg-danger' }}">
                            {{ $prediction['prediction_type'] }}
                        </span>
                    </div>
                    <div class="prediction-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Created:</strong> {{ date('M j, Y', strtotime($prediction['prediction_date'])) }}</p>
                                <p><strong>End Date:</strong> {{ date('M j, Y', strtotime($prediction['end_date'])) }}</p>
                                @if(!empty($prediction['target_price']))
                                    <p><strong>Target Price:</strong> ${{ number_format($prediction['target_price'], 2) }}</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                @php
                                    $statusClass = 'bg-secondary';
                                    $statusText = 'Inactive';

                                    if ($prediction['is_active'] == 1) {
                                        if (strtotime($prediction['end_date']) > time()) {
                                            $statusClass = 'bg-primary';
                                            $statusText = 'Active';
                                        } else {
                                            $statusClass = 'bg-warning text-dark';
                                            $statusText = 'Expired';
                                        }
                                    }
                                @endphp
                                <p>
                                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                </p>
                                <p><strong>Upvotes:</strong> {{ isset($prediction['votes']) ? $prediction['votes'] : 0 }}</p>
                                @if(isset($prediction['accuracy']) && $prediction['accuracy'] !== null)
                                    <p><strong>Accuracy:</strong> {{ number_format($prediction['accuracy'], 2) }}%</p>
                                @endif
                            </div>
                        </div>

                        @if(!empty($prediction['reasoning']))
                            <div class="reasoning mt-3">
                                <h5>Reasoning:</h5>
                                <p>{{ $prediction['reasoning'] }}</p>
                            </div>
                        @endif

                        @if($prediction['is_active'] == 1 && strtotime($prediction['end_date']) > time())
                            <div class="action-buttons mt-3">
                                <a href="{{ route('predictions.edit', ['id' => $prediction['prediction_id']]) }}"
                                    class="btn btn-sm btn-outline-primary">Edit</a>
                                <button class="btn btn-sm btn-outline-danger delete-prediction"
                                    data-id="{{ $prediction['prediction_id'] }}" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal">Delete</button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this prediction? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/prediction.js') }}"></script>
    <script type="text/javascript">
        // Update API endpoint for prediction.js to use Laravel routes
        const apiEndpoints = {
            deletePrediction: '{{ route('api.predictions.delete') }}',
            searchStocks: '{{ route('api.search_stocks') }}'
        };
    </script>
@endsection