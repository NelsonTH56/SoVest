@extends('layouts.app')

@section('content')
    <div class="container d-flex mt-5">

    {{-- Left Column: Main Content --}}
    <div class="flex-grow-1 pe-4 border-end">
        <div class="text-center">
            <h1>
                <span style="color: black;">Welcome to </span>
                <span style="color: #28a745;">Sovest</span>
            </h1>
            <p>Analyze, Predict, and Improve Your Market Insights</p>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <form action="{{ url('search') }}" method="GET" class="search-form">
                <input type="text" name="query" placeholder="Search stocks..." class="search-input" />
                <button type="submit" class="search-button">
                    <img src="{{ asset('images/search-icon.png') }}" alt="Search" class="search-icon" />
                </button>
            </form>
        </div>
        <br>
        <br>

        {{-- Predictions go here --}}
        @if(empty($predictions))
            <div class="empty-state prediction-card">
                <h4>No predictions yet</h4>
            </div>
        @else
            @foreach($predictions as $index => $prediction)
            <div class="prediction-card p-3 border rounded mb-4">
                @php
                    $profilePicture = $prediction->user->profile_picture 
                        ? asset('images/' . $prediction->user->profile_picture) 
                        : asset('images/default.png');
                @endphp

                    {{-- Top section: user info and target price --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        {{-- User Info --}}
                        <div class="text-center me-4" style="width: 120px;">
                            <img src="{{ $profilePicture }}" alt="profile picture" width="70" height="70" style="border-radius: 50%; object-fit: cover;">
                            <div class="mt-2 fw-bold">{{ $prediction->user->first_name }}</div>
                            <small class="text-muted">Reputation: {{ $prediction->user->reputation_score }}</small>
                        </div>
                    </div>
                    {{-- Reasoning in center --}}
                    @if(!empty($prediction['reasoning']))
                        <div class="reasoning my-4">
                            <div class ="reasoning m-5">
                            @if(!empty($prediction->stock->company_name))
                                    <span class="company-name "> 
                                    {{ $prediction->stock->company_name}} - {{ $prediction->stock->symbol}}
                                    </span>
                            @endif
                             {{-- Target Price & Prediction Type --}}
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge {{ $prediction['prediction_type'] == 'Bullish' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $prediction['prediction_type'] }}
                                </span>
                                @if(!empty($prediction['target_price']))
                                    <p class="mb-0"><strong>Target Price:</strong> ${{ number_format($prediction['target_price'], 2) }}</p>
                                @endif
                            </div>
                        </div>
                            <p class="fs-5">{{ $prediction['reasoning'] }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Bottom section: Dates, Status, Upvotes, Accuracy --}}
                    <div class="row text-sm">
                        <div class="col-md-6">
                            <p><strong>Created:</strong> {{ date('M j, Y', strtotime($prediction['prediction_date'])) }}</p>
                            <p><strong>End Date:</strong> {{ date('M j, Y', strtotime($prediction['end_date'])) }}</p>
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
                            <p><strong>Status:</strong> <span class="badge {{ $statusClass }}">{{ $statusText }}</span></p>
                            <p>
                                <strong>Upvotes:</strong>
                                <span id="upvote-count-{{ $prediction->id }}">{{ $prediction['upvotes'] ?? 0 }}</span>
                                
                                <button class="btn btn-sm btn-outline-success ms-2 vote-btn" 
                                        data-id="{{ $prediction->prediction_id }}" 
                                        data-action="upvote">⬆️</button>

                                <button class="btn btn-sm btn-outline-danger vote-btn" 
                                        data-id="{{ $prediction->prediction_id }}" 
                                        data-action="downvote">⬇️</button>
                            </p>
                            @push('scripts')
                                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                <script>
                                    $('.vote-btn').on('click', function() {
                                        const predictionId = $(this).data('id');
                                        const action = $(this).data('action');

                                        $.ajax({
                                            url: `/predictions/${predictionId}/${action}`,
                                            type: 'POST',
                                            data: {
                                                _token: '{{ csrf_token() }}'
                                            },
                                            success: function(response) {
                                                $('#upvote-count-' + predictionId).text(response.upvotes);
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('AJAX Error:', xhr.responseText);
                                                alert('Something went wrong: ' + xhr.responseText);
                                            }
                                        });
                                    });
                                </script>
                                @endpush
                            @if(isset($prediction['accuracy']) && $prediction['accuracy'] !== null)
                                <p><strong>Accuracy:</strong> {{ number_format($prediction['accuracy'], 2) }}%</p>
                            @endif
                        </div>
                    </div>
                </div>
            <br>
            <br>
            @endforeach
            @endif
    </div>

    {{-- Right Column --}}
    <div class="right-col ps-4" style="min-width: 250px;">
        <a href="{{ route('predictions.create') }}" class="btn btn-primary home mb-4">Create New Prediction</a>
        {{-- Add more content here as needed --}}
    </div>
</div>

       <br>
       <br>
                <!-- Pagination Links -->
                <div class="pagination">
                     {{ $predictions->links() }}
                 </div>
                 
            </div>
@endsection