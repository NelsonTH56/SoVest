@extends('layouts.app')

@section('title', 'Sovest - <Username>')

@section('content')

    <div class="container profile-header">
        <img src="{{ asset($user['profile_picture']) }}" class="profile-picture" alt="Profile Picture">
        <h2>{{ $user['full_name'] }}</h2>
        <p class="bio">{{ $user['username'] }} | {{ $user['bio'] }}</p>
    </div>

    <div class="container predictions-list">
        <h3 class="text-center">Predictions</h3>
        <div class="row">
            @foreach ($user['predictions'] as $prediction)
                <div class="col-md-4">
                    <div class="prediction-card">
                        <h5>{{ $prediction['symbol'] }}</h5>
                        <p>Prediction: <strong>{{ $prediction['prediction'] }}</strong></p>
                        <p>Accuracy: <strong>{{ $prediction['accuracy'] }}</strong></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .profile-header {
            text-align: center;
            padding: 20px;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #28a745;
        }

        .bio {
            font-size: 1.1em;
            color: #b0b0b0;
        }

        .predictions-list {
            margin-top: 20px;
        }

        .prediction-card {
            background: #1f1f1f;
            padding: 15px;
            border-radius: 10px;
        }
    </style>
@endpush