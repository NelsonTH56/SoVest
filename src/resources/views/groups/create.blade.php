@extends('layouts.app')

@section('title', 'Create Group - SoVest')

@section('content')
<div class="create-group-page">
    <div class="create-group-header text-center mb-4">
        <h1 class="page-title">
            <i class="bi bi-plus-circle-fill" style="color: #10b981;"></i>
            Create a Group
        </h1>
        <p class="page-subtitle">Start your own group and invite others to compete</p>
    </div>

    <div class="form-container">
        <form action="{{ route('groups.store') }}" method="POST">
            @csrf

            {{-- Group Name --}}
            <div class="mb-4">
                <label for="name" class="form-label">Group Name</label>
                <input type="text"
                       class="form-control @error('name') is-invalid @enderror"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="Enter a unique group name"
                       required
                       minlength="3"
                       maxlength="100">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">3-100 characters. Must be unique.</div>
            </div>

            {{-- Passcode --}}
            <div class="mb-4">
                <label for="passcode" class="form-label">Passcode</label>
                <div class="input-group">
                    <input type="password"
                           class="form-control @error('passcode') is-invalid @enderror"
                           id="passcode"
                           name="passcode"
                           placeholder="Enter a passcode for members to join"
                           required
                           minlength="4"
                           maxlength="50">
                    <button class="btn btn-outline-secondary" type="button" id="togglePasscode">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('passcode')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="form-text">4-50 characters. Share this with people you want to join.</div>
            </div>

            {{-- Info Box --}}
            <div class="info-box mb-4">
                <h6><i class="bi bi-info-circle me-1"></i> As the group admin, you can:</h6>
                <ul class="mb-0">
                    <li>View the group's private leaderboard</li>
                    <li>Change the group passcode anytime</li>
                    <li>Remove members from the group</li>
                </ul>
            </div>

            {{-- Submit Buttons --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Create Group
                </button>
                <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
.create-group-page {
    max-width: 500px;
    margin: 0 auto;
    padding: 1rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

body.dark-mode .page-title {
    color: #f3f4f6;
}

.page-subtitle {
    color: #6b7280;
    font-size: 1rem;
    margin-top: 0.5rem;
}

body.dark-mode .page-subtitle {
    color: #9ca3af;
}

.form-container {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
}

body.dark-mode .form-container {
    background: #2a2a2a;
    border-color: #404040;
}

.form-label {
    font-weight: 600;
    color: #111827;
}

body.dark-mode .form-label {
    color: #f3f4f6;
}

.form-control {
    background: #ffffff;
    border-color: #d1d5db;
    color: #111827;
}

body.dark-mode .form-control {
    background: #1a1a1a;
    border-color: #404040;
    color: #f3f4f6;
}

.form-control:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
}

.form-text {
    color: #6b7280;
}

body.dark-mode .form-text {
    color: #9ca3af;
}

.info-box {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid #10b981;
    border-radius: 0.5rem;
    padding: 1rem;
}

body.dark-mode .info-box {
    background: rgba(16, 185, 129, 0.15);
}

.info-box h6 {
    color: #10b981;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-box ul {
    padding-left: 1.25rem;
    color: #374151;
}

body.dark-mode .info-box ul {
    color: #d1d5db;
}

.info-box li {
    margin-bottom: 0.25rem;
}
</style>
@endsection

@section('scripts')
<script>
document.getElementById('togglePasscode').addEventListener('click', function() {
    const passcodeInput = document.getElementById('passcode');
    const icon = this.querySelector('i');

    if (passcodeInput.type === 'password') {
        passcodeInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passcodeInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>
@endsection
