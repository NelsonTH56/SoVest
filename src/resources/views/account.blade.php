@extends('layouts.app')

@section('title', 'Sovest - {{ $Curruser["full_name"] }}')

@section('content')
<div class="container profile-header">
    @php
        $profilePicture = $Curruser['profile_picture']
            ? asset('images/profile_pictures/' . $Curruser['profile_picture']) 
            : asset('images/default.png');
    @endphp

<div class="acc-profile-pic d-flex flex-column align-items-center">
    <img src="{{ $profilePicture }}" class="profile-picture" alt="Profile Picture">

    <!-- Upload form below the profile picture -->
    <form action="{{ route('user.profile.uploadPhoto') }}" method="POST" enctype="multipart/form-data" class="mt-3">
        @csrf
        <label class="btn btn-outline-primary">
            Change Photo
            <input type="file" name="profile_picture" onchange="this.form.submit()" hidden accept="image/*">
        </label>
    </form>
</div>

    <h2 class="mt-3">{{ $Curruser['full_name'] }}</h2>
    <p class="bio">{{ $Curruser['username'] }}</p>
</div>

<!-- Editable Bio Section -->
<div class="container bio-section">
    <h3 class="mt-4">Bio</h3>
    <form action="{{ route('user.updateBio') }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="bio-edit-container d-flex flex-column align-items-start">
            <!-- Edit Bio button -->
            <button type="button" id="editBioBtn" class="edit-bio btn btn-primary mt-2">Edit Bio</button>
            <br>

            <!-- Bio Text -->
            <p id="bioText">{{ $Curruser['bio'] ?? 'No bio available' }}</p>
        </div>

        <!-- Editable Bio Field (Initially Hidden) -->
        <div id="bioInputSection" class="d-none mt-3">
            <button type="submit" class="save-bio btn btn-success mt-2" id="saveBioBtn">Save Bio</button>
            <!-- Bio Textarea -->
            <textarea name="bio" id="bioInput" rows="3" class="bio-form-control form-control">{{ $Curruser['bio'] ?? '' }}</textarea>

            
        </div>
    </form>
</div>




<div class="container predictions-list">
    <h3 class="text-center">Predictions</h3>
    <div class="row">
        @foreach ($Curruser['predictions'] as $prediction)
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

@section('scripts')
<script>
// Toggle bio input field visibility
document.getElementById('editBioBtn').addEventListener('click', function() {
    const bioText = document.getElementById('bioText');
    const bioInputSection = document.getElementById('bioInputSection');
    const saveBioBtn = document.getElementById('saveBioBtn');
    
    // Toggle visibility of the bio text and input section
    if (bioInputSection.classList.contains('d-none')) {
        bioText.classList.add('d-none');  // Hide bio text
        bioInputSection.classList.remove('d-none');  // Show bio input section
        saveBioBtn.classList.remove('d-none');  // Show Save Bio button
        this.classList.add('d-none');  // Hide Edit Bio button
    }
});
</script>
@endsection