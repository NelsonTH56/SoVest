@extends('layouts.app')

@section('content')
<div class="container mt-4">
    @php
        $profilePicture = $Curruser['profile_picture']
            ? asset('images/profile_pictures/' . $Curruser['profile_picture']) 
            : asset('images/default.png');
    @endphp
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



