@extends('layouts.app')

@section('title', 'Feedback - SoVest')

@section('content')
<div class="feedback-page">
    {{-- Page Header --}}
    <div class="feedback-header text-center mb-4">
        <h1 class="feedback-title">
            <i class="bi bi-chat-dots-fill" style="color: #10b981;"></i>
            Send Feedback
        </h1>
        <p class="feedback-subtitle">We'd love to hear from you! Share your thoughts, suggestions, or report issues.</p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="form-container">
        <form action="{{ route('feedback.send') }}" method="POST">
            @csrf

            {{-- Feedback Type --}}
            <div class="mb-4">
                <label for="type" class="form-label">Feedback Type</label>
                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                    <option value="" disabled selected>Select a type...</option>
                    <option value="suggestion" {{ old('type') == 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                    <option value="bug" {{ old('type') == 'bug' ? 'selected' : '' }}>Bug Report</option>
                    <option value="feature" {{ old('type') == 'feature' ? 'selected' : '' }}>Feature Request</option>
                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General Feedback</option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Subject --}}
            <div class="mb-4">
                <label for="subject" class="form-label">Subject</label>
                <input type="text"
                       class="form-control @error('subject') is-invalid @enderror"
                       id="subject"
                       name="subject"
                       value="{{ old('subject') }}"
                       placeholder="Brief summary of your feedback"
                       required
                       maxlength="200">
                @error('subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Message --}}
            <div class="mb-4">
                <label for="message" class="form-label">Your Feedback</label>
                <textarea class="form-control @error('message') is-invalid @enderror"
                          id="message"
                          name="message"
                          rows="6"
                          placeholder="Please share your feedback in detail..."
                          required
                          minlength="10"
                          maxlength="2000">{{ old('message') }}</textarea>
                @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">10-2000 characters</div>
            </div>

            {{-- Submit Button --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-send me-2"></i>Send Feedback
                </button>
            </div>
        </form>
    </div>

    {{-- Info Box --}}
    <div class="info-box mt-4">
        <h6><i class="bi bi-envelope me-1"></i> Your feedback will be sent to:</h6>
        <p class="mb-0"><a href="mailto:tech.sovest.co@gmail.com">tech.sovest.co@gmail.com</a></p>
    </div>
</div>
@endsection

@section('styles')
<style>
.feedback-page {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}

.feedback-header {
    margin-bottom: 2rem;
}

.feedback-title {
    font-size: 2rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

body.dark-mode .feedback-title {
    color: #f3f4f6;
}

.feedback-subtitle {
    color: #6b7280;
    font-size: 1rem;
    margin-top: 0.5rem;
}

body.dark-mode .feedback-subtitle {
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

.form-control, .form-select {
    background: #ffffff;
    border-color: #d1d5db;
    color: #111827;
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    background: #1a1a1a;
    border-color: #404040;
    color: #f3f4f6;
}

.form-control:focus, .form-select:focus {
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
    text-align: center;
}

body.dark-mode .info-box {
    background: rgba(16, 185, 129, 0.15);
}

.info-box h6 {
    color: #10b981;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-box a {
    color: #10b981;
    text-decoration: none;
}

.info-box a:hover {
    text-decoration: underline;
}

.btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    font-weight: 600;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

body.dark-mode .form-select option {
    background: #1a1a1a;
    color: #f3f4f6;
}
</style>
@endsection
