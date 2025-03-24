@extends('layouts.app')

@section('title', $pageTitle ?? "{$prediction['symbol']} {$prediction['prediction_type']} Prediction")

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
@endsection

@section('content')
    @php
    // Functions from prediction_score_display.php
    function getAccuracyClass($accuracy) {
        if ($accuracy === null) {
            return 'text-secondary';  // Pending
        } else if ($accuracy >= 70) {
            return 'text-success';    // Good
        } else if ($accuracy >= 40) {
            return 'text-warning';    // Average
        } else {
            return 'text-danger';     // Poor
        }
    }

    function getAccuracyIcon($accuracy) {
        if ($accuracy === null) {
            return '<i class="bi bi-hourglass"></i>';  // Pending
        } else if ($accuracy >= 70) {
            return '<i class="bi bi-check-circle-fill"></i>';  // Good
        } else if ($accuracy >= 40) {
            return '<i class="bi bi-exclamation-circle-fill"></i>';  // Average
        } else {
            return '<i class="bi bi-x-circle-fill"></i>';  // Poor
        }
    }

    function formatAccuracy($accuracy) {
        if ($accuracy === null) {
            return 'Pending';
        }
        return number_format($accuracy, 0) . '%';
    }

    function renderPredictionBadge($accuracy) {
        $class = getAccuracyClass($accuracy);
        $icon = getAccuracyIcon($accuracy);
        $text = formatAccuracy($accuracy);
        
        return "<span class=\"badge $class\">$icon $text</span>";
    }

    function renderReputationScore($reputation, $avgAccuracy = null) {
        $reputationClass = $reputation >= 20 ? 'text-success' : 
                          ($reputation >= 10 ? 'text-info' : 
                          ($reputation >= 0 ? 'text-warning' : 'text-danger'));
        
        $accuracyHtml = '';
        if ($avgAccuracy !== null) {
            $accuracyClass = getAccuracyClass($avgAccuracy);
            $accuracyHtml = "<div class=\"mt-2\">Average Accuracy: <span class=\"$accuracyClass\">" . 
                           formatAccuracy($avgAccuracy) . "</span></div>";
        }
        
        $html = <<<HTML
<div class="reputation-score">
    <h4>REP SCORE: <span class="$reputationClass">$reputation</span></h4>
    $accuracyHtml
</div>
HTML;
        
        return $html;
    }

    // Calculate prediction status
    $isPending = $prediction['accuracy'] === null;
    $isActive = $prediction['is_active'] == 1;
    $endDate = new DateTime($prediction['end_date']);
    $today = new DateTime();
    $daysRemaining = $today > $endDate ? 0 : $today->diff($endDate)->days;

    // Generate prediction class and icon
    $predictionClass = $prediction['prediction_type'] == 'Bullish' ? 'text-success' : 'text-danger';
    $predictionIcon = $prediction['prediction_type'] == 'Bullish' ? 
        '<i class="bi bi-graph-up-arrow"></i>' : 
        '<i class="bi bi-graph-down-arrow"></i>';

    // Generate badge for accuracy
    $accuracyBadge = renderPredictionBadge($prediction['accuracy']);

    // Determine user's existing vote (if any)
    $userVoted = false;
    $userVoteType = null;
    @endphp

    <div class="row">
        <!-- Main prediction content -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <span class="{{ $predictionClass }}">{!! $predictionIcon !!} {{ $prediction['prediction_type'] }}</span>
                        on <strong>{{ $prediction['symbol'] }}</strong>
                    </h3>
                    <div>
                        {!! $accuracyBadge !!}
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Prediction details -->
                    <div class="mb-4">
                        <h5>Prediction by {{ $prediction['username'] }}</h5>
                        <div class="text-muted mb-3">
                            <small>
                                Created: {{ date('M j, Y', strtotime($prediction['prediction_date'])) }} • 
                                @if ($isPending)
                                    Ends: {{ date('M j, Y', strtotime($prediction['end_date'])) }}
                                    ({{ $daysRemaining }} days remaining)
                                @else
                                    Ended: {{ date('M j, Y', strtotime($prediction['end_date'])) }}
                                @endif
                            </small>
                        </div>
                        
                        @if ($prediction['target_price'])
                        <div class="mb-3">
                            <h6>Target Price:</h6>
                            <p class="fs-4 {{ $predictionClass }}">
                                ${{ number_format($prediction['target_price'], 2) }}
                            </p>
                        </div>
                        @endif
                        
                        <div class="mb-3">
                            <h6>Reasoning:</h6>
                            <div class="p-3 bg-light rounded">
                                {!! nl2br(e($prediction['reasoning'])) !!}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Voting section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Prediction Voting</h5>
                            <p class="text-muted">Do you agree with this prediction?</p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    @auth
                                    <button class="btn {{ $userVoteType == 'upvote' ? 'btn-success' : 'btn-outline-success' }} me-2 vote-btn" 
                                            data-prediction-id="{{ $prediction['prediction_id'] }}" 
                                            data-vote-type="upvote">
                                        <i class="bi bi-hand-thumbs-up"></i> Agree
                                    </button>
                                    @else
                                    <a href="{{ route('login.form') }}" class="btn btn-outline-success me-2">
                                        <i class="bi bi-hand-thumbs-up"></i> Agree
                                    </a>
                                    @endauth
                                    <span class="badge bg-success ms-1">{{ $prediction['upvotes'] }}</span>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    @auth
                                    <button class="btn {{ $userVoteType == 'downvote' ? 'btn-danger' : 'btn-outline-danger' }} me-2 vote-btn" 
                                            data-prediction-id="{{ $prediction['prediction_id'] }}" 
                                            data-vote-type="downvote">
                                        <i class="bi bi-hand-thumbs-down"></i> Disagree
                                    </button>
                                    @else
                                    <a href="{{ route('login.form') }}" class="btn btn-outline-danger me-2">
                                        <i class="bi bi-hand-thumbs-down"></i> Disagree
                                    </a>
                                    @endauth
                                    <span class="badge bg-danger ms-1">{{ $prediction['downvotes'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Owner actions -->
                    @if (Auth::check() && Auth::id() == $prediction['user_id'] && $isActive)
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('prediction.edit', ['id' => $prediction['prediction_id']]) }}" 
                           class="btn btn-outline-primary me-2">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-outline-danger delete-prediction" 
                                data-id="{{ $prediction['prediction_id'] }}" 
                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Back button -->
            <div class="mb-4">
                <a href="{{ route('prediction.trending') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Trending
                </a>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Stock information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Stock Information</h5>
                </div>
                <div class="card-body">
                    <h3>{{ $prediction['stock']['symbol'] }}</h3>
                    <p class="text-muted">{{ $prediction['stock']['company_name'] }}</p>
                    
                    @if (isset($prediction['stock']['sector']))
                    <div class="mb-3">
                        <strong>Sector:</strong> {{ $prediction['stock']['sector'] }}
                    </div>
                    @endif
                    
                    @if (isset($prediction['stock']['current_price']))
                    <div class="mb-3">
                        <strong>Current Price:</strong> ${{ number_format($prediction['stock']['current_price'], 2) }}
                    </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('search', ['symbol' => $prediction['stock']['symbol']]) }}" class="btn btn-outline-primary btn-sm">
                            View Stock Details
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- User information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Predictor Profile</h5>
                </div>
                <div class="card-body">
                    <h5>{{ $prediction['username'] }}</h5>
                    
                    <!-- Display user reputation if available -->
                    @if (isset($prediction['user']['reputation_score']))
                        {!! renderReputationScore($prediction['user']['reputation_score']) !!}
                    @endif
                    
                    <!-- If user has other predictions, show link -->
                    <div class="mt-3">
                        <a href="{{ route('user.predictions', ['id' => $prediction['user_id']]) }}" class="btn btn-outline-secondary btn-sm">
                            View User's Predictions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    <script>
    // Handle voting
    document.addEventListener('DOMContentLoaded', function() {
        const voteButtons = document.querySelectorAll('.vote-btn');
        
        voteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const predictionId = this.getAttribute('data-prediction-id');
                const voteType = this.getAttribute('data-vote-type');
                
                // Create form data
                const formData = new FormData();
                formData.append('prediction_id', predictionId);
                formData.append('vote_type', voteType);
                
                // Send vote request
                fetch('{{ route('prediction.vote') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload page to update vote counts
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error submitting vote:', error);
                    alert('An error occurred while submitting your vote');
                });
            });
        });

        // Handle delete confirmation
        document.getElementById('confirmDelete')?.addEventListener('click', function() {
            const predictionId = document.querySelector('.delete-prediction').getAttribute('data-id');
            
            fetch('{{ route('prediction.delete') }}', {
                method: 'POST',
                body: JSON.stringify({ id: predictionId }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '{{ route('prediction.index') }}';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting prediction:', error);
                alert('An error occurred while deleting the prediction');
            });
        });
    });
    </script>
@endsection