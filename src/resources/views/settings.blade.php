@extends('layouts.app')

@section('title', 'Settings - SoVest')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="settings-header mb-4">
                <h1 class="display-5 fw-bold">Settings</h1>
                <p class="text-muted">Manage your account preferences and settings</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Account Information Section -->
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-person-circle me-2"></i>Account Information
                    </h3>
                </div>
                <div class="settings-card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Full Name</div>
                        <div class="col-md-9">{{ $Curruser['full_name'] }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Email</div>
                        <div class="col-md-9">{{ $Curruser['email'] }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">User ID</div>
                        <div class="col-md-9">{{ $Curruser['id'] }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Reputation Score</div>
                        <div class="col-md-9">
                            <span class="badge bg-success">{{ number_format($Curruser['reputation_score'], 0) }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Member Since</div>
                        <div class="col-md-9">{{ \Carbon\Carbon::parse($Curruser['created_at'])->format('F j, Y') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 fw-semibold text-muted">Bio</div>
                        <div class="col-md-9">{{ $Curruser['bio'] ?? 'No bio added yet' }}</div>
                    </div>
                </div>
            </div>

            <!-- Statistics Section -->
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-graph-up me-2"></i>Your Statistics
                    </h3>
                </div>
                <div class="settings-card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Total Predictions</div>
                        <div class="col-md-9">{{ $userStats['total'] ?? 0 }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Active Predictions</div>
                        <div class="col-md-9">{{ $userStats['active'] ?? 0 }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-semibold text-muted">Completed Predictions</div>
                        <div class="col-md-9">{{ $userStats['completed'] ?? 0 }}</div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 fw-semibold text-muted">Average Accuracy</div>
                        <div class="col-md-9">
                            @if(isset($userStats['avg_accuracy']) && $userStats['avg_accuracy'] !== null)
                                <span class="badge bg-info">{{ number_format($userStats['avg_accuracy'], 1) }}%</span>
                            @else
                                <span class="text-muted">No completed predictions yet</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Appearance Section -->
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-palette me-2"></i>Appearance
                    </h3>
                </div>
                <div class="settings-card-body">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="fw-semibold mb-1">Dark Mode</div>
                            <div class="text-muted small">Toggle between light and dark theme</div>
                        </div>
                        <div class="col-md-3 text-end">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" id="darkModeToggle" style="width: 3rem; height: 1.5rem; cursor: pointer;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy & Security Section -->
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Privacy & Security
                    </h3>
                </div>
                <div class="settings-card-body">
                    <div class="row mb-3">
                        <div class="col-md-9">
                            <div class="fw-semibold mb-1">Change Password</div>
                            <div class="text-muted small">Update your password to keep your account secure</div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-primary btn-sm" disabled>Coming Soon</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="fw-semibold mb-1">Delete Account</div>
                            <div class="text-muted small">Permanently delete your account and all data</div>
                        </div>
                        <div class="col-md-3 text-end">
                            <button class="btn btn-outline-danger btn-sm" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Section -->
            <div class="settings-card mb-4">
                <div class="settings-card-header">
                    <h3 class="h5 mb-0">
                        <i class="bi bi-gear me-2"></i>Actions
                    </h3>
                </div>
                <div class="settings-card-body">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('user.account') }}" class="btn btn-outline-primary">
                            <i class="bi bi-person me-2"></i>View Profile
                        </a>
                        <a href="{{ route('predictions.index') }}" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul me-2"></i>My Predictions
                        </a>
                        <a href="{{ route('logout') }}" class="btn btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .settings-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }

    .settings-card-header {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .settings-card-header h3 {
        color: #111827;
        font-weight: 600;
    }

    .settings-card-body {
        padding: 1.5rem;
    }

    .settings-header h1 {
        color: #111827;
    }

    /* Dark mode styles */
    body.dark-mode {
        background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
        color: #e5e7eb;
    }

    body.dark-mode .settings-card {
        background: #2d2d2d;
        border: 1px solid #404040;
    }

    body.dark-mode .settings-card-header {
        background: linear-gradient(135deg, #1f1f1f 0%, #2a2a2a 100%);
        border-bottom-color: #404040;
    }

    body.dark-mode .settings-card-header h3 {
        color: #f3f4f6;
    }

    body.dark-mode .settings-header h1 {
        color: #f3f4f6;
    }

    body.dark-mode .text-muted {
        color: #9ca3af !important;
    }

    body.dark-mode .btn-outline-primary {
        color: #10b981;
        border-color: #10b981;
    }

    body.dark-mode .btn-outline-primary:hover {
        background: #10b981;
        color: white;
    }

    body.dark-mode .btn-outline-danger {
        color: #ef4444;
        border-color: #ef4444;
    }

    body.dark-mode .btn-outline-danger:hover {
        background: #ef4444;
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        // Check for saved dark mode preference
        const darkMode = localStorage.getItem('darkMode');

        if (darkMode === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    });
</script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
@endsection
