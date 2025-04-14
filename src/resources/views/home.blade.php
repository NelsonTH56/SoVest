@extends('layouts.app')

@section('content')
    <div class="container text-center mt-5">
        <h1>Welcome to SoVest<?php echo isset($user['first_name']) ? ', ' . $user['first_name'] : ''; ?></h1>
        <p>Analyze, Predict, and Improve Your Market Insights</p>
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
           
           
            @if(empty($predictions))
            <div class="empty-state prediction-card">
                <h4>No predictions yet</h4>
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
                            </div>
                                </div>
                            <br>
                            <br>
                            @endforeach
                            @endif
                       

                <!-- Pagination Links -->
                <div class="pagination">
                     {{ $predictions->links() }}
                 </div>
            </div>
        </div>
    </div>
@endsection