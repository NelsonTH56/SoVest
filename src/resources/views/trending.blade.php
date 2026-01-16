@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
    <style>
        /* Trending page text transitions */
        .trending-card, .trending-username, .trending-symbol, .trending-value,
        .trending-label, .trending-price, .trending-page-title {
            transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        }

        /* Light mode styles (default) */
        .trending-page-title {
            color: #111827;
        }

        .trending-card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .trending-card:hover {
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .trending-username {
            color: #111827;
        }

        .trending-symbol {
            color: #6b7280;
        }

        .trending-label {
            color: #6b7280;
        }

        .trending-value {
            color: #111827;
        }

        .trending-price {
            color: #111827;
            font-weight: 700;
        }

        /* Dark mode styles */
        body.dark-mode .trending-page-title {
            color: #f3f4f6;
        }

        body.dark-mode .trending-card {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        body.dark-mode .trending-card:hover {
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        body.dark-mode .trending-username {
            color: #f3f4f6;
        }

        body.dark-mode .trending-symbol {
            color: #9ca3af;
        }

        body.dark-mode .trending-label {
            color: #9ca3af;
        }

        body.dark-mode .trending-value {
            color: #e5e7eb;
        }

        body.dark-mode .trending-price {
            color: #f3f4f6;
        }

        /* Override text-muted in dark mode for trending page */
        body.dark-mode .trending-card .text-muted {
            color: #9ca3af !important;
        }
    </style>
@endsection

@section('content')
<div class="container mt-4">
    @php
        $profilePicture = isset($Curruser) && $Curruser['profile_picture']
            ? asset('images/profile_pictures/' . $Curruser['profile_picture'])
            : asset('images/default.png');
    @endphp
    <h2 class="mb-4 trending-page-title">{{ $pageTitle }}</h2>

    @foreach ($trending_predictions as $pred)
        <a href="{{ route('predictions.view', ['id' => $pred['prediction_id']]) }}" class="text-decoration-none">
            <div class="card mb-3 p-3 prediction-card trending-card" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="trending-username">{{ $pred['username'] }}</strong>
                        <span class="ms-2 trending-symbol">{{ $pred['symbol'] }}</span>
                        @if(isset($pred['reputation_score']))
                            <span class="badge bg-info ms-2" title="Reputation Score">
                                <i class="bi bi-star-fill"></i> {{ $pred['reputation_score'] }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <span class="badge bg-{{ $pred['prediction'] === 'Bullish' ? 'success' : 'danger' }}">
                            @if($pred['prediction'] === 'Bullish')
                                <i class="bi bi-graph-up-arrow"></i>
                            @else
                                <i class="bi bi-graph-down-arrow"></i>
                            @endif
                            {{ $pred['prediction'] }}
                        </span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="row">
                        <div class="col-4">
                            <small class="trending-label">Votes:</small><br>
                            <span class="trending-value"><i class="bi bi-hand-thumbs-up-fill text-success"></i> {{ $pred['votes'] ?? 0 }}</span>
                        </div>
                        <div class="col-4">
                            <small class="trending-label">Accuracy:</small><br>
                            @php
                                $accuracyClass = $pred['accuracy'] !== null ?
                                    ($pred['accuracy'] >= 70 ? 'text-success' :
                                    ($pred['accuracy'] >= 40 ? 'text-warning' : 'text-danger')) :
                                    'text-secondary';
                            @endphp
                            <span class="{{ $accuracyClass }}">
                                {{ $pred['accuracy'] !== null ? $pred['accuracy'] . '%' : 'Pending' }}
                            </span>
                        </div>
                        <div class="col-4">
                            <small class="trending-label">Current Price:</small><br>
                            @if(isset($pred['current_price']) && $pred['current_price'] !== null)
                                <span class="trending-price">${{ number_format($pred['current_price'], 2) }}</span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if(isset($pred['target_price']))
                    <div class="mt-2">
                        <small class="trending-label">Target Price:</small>
                        <span class="trending-price"> ${{ number_format($pred['target_price'], 2) }}</span>

                        @if(isset($pred['current_price']) && $pred['current_price'] !== null && $pred['target_price'] > 0)
                            @php
                                $priceDiff = $pred['current_price'] - $pred['target_price'];
                                $percentDiff = (($pred['current_price'] - $pred['target_price']) / $pred['target_price']) * 100;
                                $predictionType = $pred['prediction'] ?? '';

                                // Determine if prediction is on track
                                $onTrack = false;
                                if ($predictionType === 'Bullish' && $pred['current_price'] >= $pred['target_price']) {
                                    $onTrack = true;
                                } elseif ($predictionType === 'Bearish' && $pred['current_price'] <= $pred['target_price']) {
                                    $onTrack = true;
                                }

                                $trackingClass = $onTrack ? 'text-success' : 'text-warning';
                                $trackingIcon = $onTrack ? 'bi-check-circle-fill' : 'bi-clock-fill';
                            @endphp
                            <span class="{{ $trackingClass }} ms-2" title="{{ $onTrack ? 'Target reached!' : 'In progress' }}">
                                <i class="bi {{ $trackingIcon }}"></i>
                                {{ abs($percentDiff) >= 0.01 ? number_format(abs($percentDiff), 2) . '%' : '0%' }}
                            </span>
                        @endif
                    </div>
                @endif

                @if(isset($pred['end_date']))
                    <div class="mt-2">
                        <small class="trending-label">
                            @php
                                $endDate = new DateTime($pred['end_date']);
                                $today = new DateTime();
                                $isActive = $pred['is_active'] ?? false;
                            @endphp
                            @if($isActive && $today < $endDate)
                                <i class="bi bi-clock"></i> Ends: {{ $endDate->format('M j, Y') }}
                            @else
                                <i class="bi bi-check-circle"></i> Ended: {{ $endDate->format('M j, Y') }}
                            @endif
                        </small>
                    </div>
                @endif

                <div class="mt-3 text-end">
                    <small class="text-primary">Click to view details <i class="bi bi-arrow-right"></i></small>
                </div>
            </div>
        </a>
    @endforeach
</div>
@endsection



