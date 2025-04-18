

@extends('layouts.app') {{-- or whatever layout you're using --}}

@section('content')
<div class="container mt-4">
    <h2 class="mb-4">{{ $pageTitle }}</h2>

    @foreach ($trending_predictions as $pred)
        <div class="card mb-3 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $pred['username'] }}</strong>
                    <span class="ms-2 text-muted">{{ $pred['symbol'] }}</span>
                </div>
                <div>
                    <span class="badge bg-{{ $pred['prediction'] === 'Bullish' ? 'success' : 'danger' }}">
                        {{ $pred['prediction'] }}
                    </span>
                </div>
            </div>

            <div class="mt-2">
                <strong>Votes:</strong> {{ $pred['votes'] ?? 0 }}<br>
                <strong>Accuracy:</strong> 
                {{ $pred['accuracy'] !== null ? $pred['accuracy'] . '%' : 'Pending' }}
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('styles')
    <style type="text/css">
        /* body {
                background-color: #2c2c2c;
                color: #d4d4d4;
            }

            .navbar {
                background-color: #1f1f1f;
            } */

        .trending-container {
            max-width: 800px;
            margin: auto;
            margin-top: 30px;
        }

        .h2{
            width: 100%;
            text-align: center;
        }
        .post-card {
            /* background: #1f1f1f; */
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .vote-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vote-btn {
            background: none;
            border: none;
            color: #28a745;
            cursor: pointer;
            font-size: 1.5rem;
        }

        .vote-count {
            font-size: 1.2rem;
        }
    </style>
@endpush