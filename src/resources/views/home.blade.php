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
            <div class="prediction-card p-4 border rounded mb-5 shadow-sm bg-white">

                @php
                    $profilePicture = $prediction->user->profile_picture 
                        ? asset('images/' . $prediction->user->profile_picture) 
                        : asset('images/default.png');
                @endphp

                {{-- 🔝 Top section: Profile on left, dates on right --}}
                <div class="top-container-prediction d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex">
                        <img src="{{ $profilePicture }}" alt="profile picture" width="60" height="60"
                            style="border-radius: 50%; object-fit: cover;">
                        <div class="ms-3">
                            <div class="fw-bold">{{ $prediction->user->first_name }}</div>
                            <small class="text-muted">Reputation: {{ $prediction->user->reputation_score }}</small>
                        </div>
                    </div>

                    {{-- 📅 Created & End Dates --}}
                    <div class="text-end">
                        <p class="mb-1"><strong>Created:</strong> {{ date('M j, Y', strtotime($prediction['prediction_date'])) }}</p>
                        <p class="mb-0"><strong>Ends:</strong> {{ date('M j, Y', strtotime($prediction['end_date'])) }}</p>
                    </div>
                </div>

                {{-- 🧠 Company + Reasoning --}}
                @if(!empty($prediction['reasoning']))
                    @if(!empty($prediction->stock->company_name))
                        <p class="mb-2 fw-semibold text-primary">
                            {{ $prediction->stock->company_name }} ({{ $prediction->stock->symbol }})
                        </p>
                    @endif

                    {{-- 📈 Prediction Type & Target Price --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $prediction['prediction_type'] == 'Bullish' ? 'bg-success' : 'bg-danger' }}">
                            {{ $prediction['prediction_type'] }}
                        </span>
                        @if(!empty($prediction['target_price']))
                            <p class="mb-0"><strong>Target:</strong> ${{ number_format($prediction['target_price'], 2) }}</p>
                        @endif
                    </div>

                    {{-- ✍️ Reasoning text --}}
                    <p class="reasoning-text mb-4">{{ $prediction['reasoning'] }}</p>
                @endif

                {{-- 🔻 Bottom row: Status (left) & Votes (right) --}}
                <div class="bottom-content-prediction d-flex justify-content-between align-items-center">
                    {{-- Status --}}
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
                    <p class="mb-0"><strong>Status:</strong> <span class="badge {{ $statusClass }}">{{ $statusText }}</span></p>

                    {{-- Upvotes --}}
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm me-2 vote-btn" 
                                data-id="{{ $prediction->prediction_id }}" 
                                data-action="upvote">⬆️</button>

                        <span id="upvote-count-{{ $prediction->id }}">{{ $prediction['upvotes'] ?? 0 }}</span>

                        <button class="btn btn-sm ms-2 vote-btn" 
                                data-id="{{ $prediction->prediction_id }}" 
                                data-action="downvote">⬇️</button>
                    </div>
                </div>

                {{-- Accuracy --}}
                @if(isset($prediction['accuracy']) && $prediction['accuracy'] !== null)
                    <p class="mt-2 mb-0"><strong>Accuracy:</strong> {{ number_format($prediction['accuracy'], 2) }}%</p>
                @endif
            </div>

            @push('scripts')
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                $('.vote-btn').on('click', function () {
                    const predictionId = $(this).data('id');
                    const action = $(this).data('action');

                    $.ajax({
                        url: `/predictions/${predictionId}/${action}`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            $('#upvote-count-' + predictionId).text(response.upvotes);
                        },
                        error: function (xhr) {
                            console.error('AJAX Error:', xhr.responseText);
                            alert('Something went wrong: ' + xhr.responseText);
                        }
                    });
                });
            </script>
            @endpush

            @endforeach
            @endif
    </div>

    <div class="right-col ps-4" style="min-width: 250px;">
        <div class="sidebar-container">
    <!-- Create New Prediction Button -->
    <a href="{{ route('predictions.create') }}" class="btn btn-primary home mb-4">Create New Prediction</a>
    <br>
    <div class="sidebar-header">
        <h5> ACTIVE PREDICTIONS</h5>
        </div>
        <br>
    <!-- User Predictions Section -->
    
        @foreach($Userpredictions as $index => $prediction)
            <div class="user-prediction-card">
                <a href="{{ route('predictions.view', ['id' => $prediction->prediction_id]) }}" class="prediction-link">
                    <div class="prediction-card-body">
                        <h5 class="prediction-title">{{ $prediction->stock->company_name }}</h5>
                        <p class="prediction-price">Target Price: ${{ $prediction->target_price }}</p>
                        <!-- Format the end_date to remove time -->
                        <p class="prediction-date">End Date: {{ \Carbon\Carbon::parse($prediction->end_date)->format('Y-m-d') }}</p>
                    </div>
                </a>
            </div>
        @endforeach
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