@extends('layouts.app')

@section('title', 'Join ' . $group->name . ' - SoVest')

@section('content')
<div class="join-group-page">
    <div class="join-group-header text-center mb-4">
        <h1 class="page-title">
            <i class="bi bi-box-arrow-in-right" style="color: #10b981;"></i>
            Join Group
        </h1>
        <p class="page-subtitle">Enter the passcode to join this group</p>
    </div>

    <div class="group-info-card mb-4">
        <h3 class="group-name">{{ $group->name }}</h3>
        @if($group->description)
            <p class="group-description">{{ $group->description }}</p>
        @endif
        <div class="group-meta">
            <span class="member-count">
                <i class="bi bi-people"></i> {{ $group->member_count }} members
            </span>
        </div>
    </div>

    <div class="form-container">
        <form action="{{ route('groups.processJoin', $group->id) }}" method="POST">
            @csrf

            {{-- Passcode --}}
            <div class="mb-4">
                <label for="passcode" class="form-label">Passcode</label>
                <div class="input-group">
                    <input type="password"
                           class="form-control @error('passcode') is-invalid @enderror"
                           id="passcode"
                           name="passcode"
                           placeholder="Enter the group passcode"
                           required
                           autofocus>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasscode">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('passcode')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="form-text">Ask the group admin for the passcode.</div>
            </div>

            {{-- Submit Buttons --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Join Group
                </button>
                <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
.join-group-page {
    max-width: 450px;
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

.group-info-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
    border: 1px solid #10b981;
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
}

body.dark-mode .group-info-card {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%);
}

.group-info-card .group-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

body.dark-mode .group-info-card .group-name {
    color: #f3f4f6;
}

.group-info-card .group-description {
    color: #6b7280;
    margin-bottom: 0.75rem;
}

body.dark-mode .group-info-card .group-description {
    color: #9ca3af;
}

.group-meta {
    color: #6b7280;
    font-size: 0.9rem;
}

body.dark-mode .group-meta {
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
