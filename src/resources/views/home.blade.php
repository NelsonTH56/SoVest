@extends('layouts.app')

@section('styles')
<style>
    .prediction-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .prediction-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
    }

    .text-decoration-none {
        color: inherit;
    }

    .text-decoration-none:hover {
        color: inherit;
    }
</style>
@endsection

@section('content')
    <div class="container d-flex mt-5">

    {{-- Left Column: Main Content --}}
    <div class="flex-grow-1 pe-4 border-end">
        <div class="text-center animate-fade-in">
            <h1 class="mb-3" style="font-size: 3rem; font-weight: 800;">
                <span class="welcome-text" style="color: #111827;">Welcome to </span>
                <span class="gradient-text">SoVest</span>
            </h1>
            <p class="welcome-subtext" style="font-size: 1.2rem; color: #6b7280; font-weight: 500;">Analyze, Predict, and Improve Your Market Insights</p>
        </div>

        <style>
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dark mode welcome text */
        body.dark-mode .welcome-text {
            color: #f3f4f6 !important;
        }

        body.dark-mode .welcome-subtext {
            color: #d1d5db !important;
        }
        </style>

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
            <a href="{{ route('predictions.view', ['id' => $prediction->prediction_id]) }}" class="text-decoration-none">
                <div class="prediction-card p-4 border rounded mb-5 shadow-sm bg-white" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">

                    @php
                        $profilePicture = $prediction->user->profile_picture
                            ? asset('images/profile_pictures/' . $prediction->user->profile_picture)
                            : asset('images/default.png');
                    @endphp

                    {{--  Top section: Profile on left, dates on right --}}
                    <div class="top-container-prediction d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex">
                            <img src="{{ $profilePicture }}" alt="User Picture" class="img-fluid rounded-circle" width="60" height="60"
                            style="border-radius: 50%; object-fit: cover;">
                        <div class="ms-3">
                            <div class="fw-bold">{{ $prediction->user->first_name }}</div>
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-star-fill text-warning"></i> Reputation: {{ $prediction->user->reputation_score }} pts
                            </small>
                        </div>
                    </div>

                    {{--  Created & End Dates --}}
                    <div class="text-end">
                        <p class="mb-1"><strong>Created:</strong> {{ date('M j, Y', strtotime($prediction['prediction_date'])) }}</p>
                        <p class="mb-0"><strong>Ends:</strong> {{ date('M j, Y', strtotime($prediction['end_date'])) }}</p>
                    </div>
                </div>

                {{--  Company + Reasoning --}}
                @if(!empty($prediction['reasoning']))
                    @if(!empty($prediction->stock->company_name))
                        <p class="mb-2 fw-semibold text-primary">
                            {{ $prediction->stock->company_name }} ({{ $prediction->stock->symbol }})
                        </p>
                    @endif

                    {{--  Prediction Type & Target Price --}}
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge prediction-badge-vibrant {{ $prediction['prediction_type'] == 'Bullish' ? 'badge-bullish' : 'badge-bearish' }}">
                            {{ $prediction['prediction_type'] }}
                        </span>
                        @if(!empty($prediction['target_price']))
                            <p class="mb-0" style="font-size: 1.1rem;"><strong style="color: #6b7280;">Target:</strong> <span style="color: #10b981; font-weight: 700;">${{ number_format($prediction['target_price'], 2) }}</span></p>
                        @endif
                    </div>

                    <style>
                    .prediction-badge-vibrant {
                        padding: 0.5rem 1.2rem;
                        font-size: 0.875rem;
                        font-weight: 700;
                        border-radius: 9999px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
                        animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    }
                    .badge-bullish {
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                    }
                    .badge-bearish {
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        color: white;
                    }
                    @keyframes bounceIn {
                        0% {
                            transform: scale(0.3);
                            opacity: 0;
                        }
                        50% {
                            transform: scale(1.05);
                        }
                        70% {
                            transform: scale(0.9);
                        }
                        100% {
                            transform: scale(1);
                            opacity: 1;
                        }
                    }
                    </style>

                    {{--  Reasoning text --}}
                    <p class="reasoning-text mb-4">{{ $prediction['reasoning'] }}</p>
                @endif

                {{--  Bottom row: Status (left) & Votes (right) --}}
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

                    {{-- Voting --}}
                    <div class="votes d-flex align-items-center gap-3">
                        {{-- Upvotes --}}
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm me-2 vote-btn upvote-btn"
                                    data-id="{{ $prediction->prediction_id }}"
                                    data-action="upvote">
                                <img src="/images/stock-market.png" class="stock-vote" alt="Upvote">
                            </button>
                            <span id="upvotes-{{ $prediction->prediction_id }}" class="text-success fw-bold">
                                {{ $prediction->upvotes ?? 0 }}
                            </span>
                        </div>

                        {{-- Downvotes --}}
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm me-2 vote-btn downvote-btn"
                                    data-id="{{ $prediction->prediction_id }}"
                                    data-action="downvote">
                                <img src="/images/stock-market.png" class="stock-vote downvote-icon" alt="Downvote" style="transform: rotate(180deg);">
                            </button>
                            <span id="downvotes-{{ $prediction->prediction_id }}" class="text-danger fw-bold">
                                {{ $prediction->downvotes ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Accuracy --}}
                @if(isset($prediction['accuracy']) && $prediction['accuracy'] !== null)
                    <p class="mt-2 mb-0"><strong>Accuracy:</strong> {{ number_format($prediction['accuracy'], 2) }}%</p>
                @endif
                </div>
            </a>

            @endforeach
            @endif
            @push('scripts')
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const voteButtons = document.querySelectorAll(".vote-btn");

                        voteButtons.forEach(button => {
                            button.addEventListener("click", function (event) {
                                event.preventDefault(); // Prevent link navigation
                                event.stopPropagation(); // Stop event from bubbling to parent link

                                const predictionId = this.getAttribute('data-id');
                                const voteType = this.getAttribute('data-action');

                                fetch('/predictions/vote/' + predictionId, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: new URLSearchParams({
                                        vote_type: voteType,
                                        prediction_id: predictionId // ✅ Ensures Laravel gets it
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        console.log(data.message);
                                        updateVoteCount(predictionId);
                                        const image = button.querySelector('img');
                                        updateColor(image);
                                    } else {
                                        alert(data.message || "Something went wrong.");
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    alert("Error submitting vote.");
                                });
                            });
                        });

                        function updateVoteCount(predictionId) {
                            fetch(`/predictions/${predictionId}/vote-counts`)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        document.getElementById(`upvotes-${predictionId}`).textContent = data.upvotes;
                                        document.getElementById(`downvotes-${predictionId}`).textContent = data.downvotes;
                                    }
                                });
                        }
                        function updateColor(image){
                            const defaultSrc = "/images/stock-market.png";
                            const greenSrc = "/images/stock-market-green.png";

                            // Remove the origin part of the URL if present
                            const currentSrc = image.src.split("/").slice(-1)[0];

                            if (currentSrc === "stock-market-green.png") {
                                image.src = defaultSrc;
                            } else {
                                image.src = greenSrc;
                            }
                        }
                    });
            </script>
                @endpush
    </div>

    <div class="right-col ps-4" style="min-width: 250px;">
        <div class="sidebar-container">
    <!-- Create New Prediction Button -->
    <a href="{{ route('predictions.create') }}" class="btn btn-primary home mb-4 create-btn-vibrant">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle me-2" viewBox="0 0 16 16" style="display: inline-block; vertical-align: middle;">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
        </svg>
        Create New Prediction
    </a>
    <br>
    <div class="sidebar-header sidebar-header-vibrant">
        <h5>ACTIVE PREDICTIONS</h5>
    </div>
    <br>

    <style>
    .create-btn-vibrant {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        position: relative;
        overflow: hidden;
    }
    .sidebar-header-vibrant {
        background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
        padding: 0.75rem 1rem;
        border-radius: 0.75rem;
        text-align: center;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        border: none;
        margin: 0;
        width: 100%;
    }
    .sidebar-header-vibrant h5 {
        color: white;
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0;
    }
    </style>
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