@extends('layouts.app')

@section('title', 'Groups - SoVest')

@section('content')
<div class="groups-page">
    {{-- Page Header --}}
    <div class="groups-header text-center mb-4">
        <h1 class="groups-title">
            <i class="bi bi-people-fill" style="color: #10b981;"></i>
            Groups
        </h1>
        <p class="groups-subtitle">Join groups to compete on private leaderboards</p>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-center gap-3 mb-4">
        <a href="{{ route('groups.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Create Group
        </a>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#joinGroupModal">
            <i class="bi bi-box-arrow-in-right me-1"></i> Join Group
        </button>
    </div>

    {{-- Join Group Modal --}}
    <div class="modal fade" id="joinGroupModal" tabindex="-1" aria-labelledby="joinGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="joinGroupModalLabel">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Join a Group
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="joinGroupForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">Enter the group code provided by the group admin.</p>

                        <div class="mb-3">
                            <label for="groupCode" class="form-label">Group Code</label>
                            <input type="text"
                                   class="form-control form-control-lg text-center"
                                   id="groupCode"
                                   name="group_code"
                                   placeholder="Enter group code"
                                   required
                                   autocomplete="off"
                                   style="letter-spacing: 2px; font-weight: 600;">
                        </div>

                        <div id="joinError" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="joinSubmitBtn">
                            <i class="bi bi-check-lg me-1"></i> Join Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- My Groups Section --}}
    @if(!empty($userGroups))
        <div class="my-groups-section mb-5">
            <h4 class="section-title mb-3">
                <i class="bi bi-star-fill" style="color: #f59e0b;"></i>
                My Groups
            </h4>
            <div class="groups-grid">
                @foreach($userGroups as $group)
                    <div class="group-card my-group">
                        <div class="group-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="group-name">{{ $group['name'] }}</h5>
                                @if($group['is_admin'])
                                    <span class="admin-badge">Admin</span>
                                @endif
                            </div>
                            @if(!empty($group['description']))
                                <p class="group-description">{{ Str::limit($group['description'], 100) }}</p>
                            @endif
                            <div class="group-meta">
                                <span class="member-count">
                                    <i class="bi bi-people"></i> {{ $group['member_count'] }} members
                                </span>
                            </div>
                            <div class="group-actions mt-3">
                                <a href="{{ route('groups.show', $group['id']) }}" class="btn btn-sm btn-primary">
                                    View Leaderboard
                                </a>
                                @if($group['is_admin'])
                                    <a href="{{ route('groups.settings', $group['id']) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-gear"></i> Settings
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- All Groups Section --}}
    <div class="all-groups-section">
        <h4 class="section-title mb-3">
            <i class="bi bi-globe"></i>
            Discover Groups
        </h4>

        @if(!empty($allGroups))
            <div class="groups-grid">
                @foreach($allGroups as $group)
                    @if(!$group['is_member'])
                        <div class="group-card">
                            <div class="group-card-body">
                                <h5 class="group-name">{{ $group['name'] }}</h5>
                                @if(!empty($group['description']))
                                    <p class="group-description">{{ Str::limit($group['description'], 100) }}</p>
                                @endif
                                <div class="group-meta">
                                    <span class="member-count">
                                        <i class="bi bi-people"></i> {{ $group['member_count'] }} members
                                    </span>
                                    <span class="admin-name">
                                        <i class="bi bi-person"></i> {{ $group['admin_name'] }}
                                    </span>
                                </div>
                                <div class="group-actions mt-3">
                                    <a href="{{ route('groups.join', $group['id']) }}" class="btn btn-sm btn-success">
                                        <i class="bi bi-box-arrow-in-right"></i> Join Group
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @php
                $nonMemberGroups = array_filter($allGroups, fn($g) => !$g['is_member']);
            @endphp

            @if(empty($nonMemberGroups))
                <div class="empty-state text-center py-4">
                    <i class="bi bi-check-circle" style="font-size: 2rem; color: #10b981;"></i>
                    <p class="mt-2 text-muted">You're a member of all available groups!</p>
                </div>
            @endif
        @else
            <div class="empty-state text-center py-5">
                <i class="bi bi-people" style="font-size: 3rem; color: #9ca3af;"></i>
                <h4 class="mt-3">No Groups Yet</h4>
                <p class="text-muted">Be the first to create a group!</p>
                <a href="{{ route('groups.create') }}" class="btn btn-primary mt-2">Create a Group</a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
.groups-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 1rem;
}

.groups-header {
    margin-bottom: 2rem;
}

.groups-title {
    font-size: 2rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

body.dark-mode .groups-title {
    color: #f3f4f6;
}

.groups-subtitle {
    color: #6b7280;
    font-size: 1rem;
    margin-top: 0.5rem;
}

body.dark-mode .groups-subtitle {
    color: #9ca3af;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.dark-mode .section-title {
    color: #f3f4f6;
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.group-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

body.dark-mode .group-card {
    background: #2a2a2a;
    border-color: #404040;
}

.group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-card.my-group {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
}

body.dark-mode .group-card.my-group {
    border-color: #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, transparent 100%);
}

.group-card-body {
    padding: 1.25rem;
}

.group-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
}

body.dark-mode .group-name {
    color: #f3f4f6;
}

.group-description {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 0.75rem;
}

body.dark-mode .group-description {
    color: #9ca3af;
}

.group-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #6b7280;
}

body.dark-mode .group-meta {
    color: #9ca3af;
}

.member-count, .admin-name {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.admin-badge {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #f59e0b;
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
}

.group-actions {
    display: flex;
    gap: 0.5rem;
}

.empty-state h4 {
    color: #111827;
}

body.dark-mode .empty-state h4 {
    color: #f3f4f6;
}

@media (max-width: 640px) {
    .groups-grid {
        grid-template-columns: 1fr;
    }
}

/* Modal Styles */
.modal-content {
    border-radius: 0.75rem;
}

body.dark-mode .modal-content {
    background: #2a2a2a;
    border-color: #404040;
}

body.dark-mode .modal-header {
    border-bottom-color: #404040;
}

body.dark-mode .modal-footer {
    border-top-color: #404040;
}

body.dark-mode .modal-title {
    color: #f3f4f6;
}

body.dark-mode .btn-close {
    filter: invert(1);
}

#groupCode {
    font-size: 1.25rem;
}

#groupCode::placeholder {
    letter-spacing: normal;
    font-weight: 400;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const joinForm = document.getElementById('joinGroupForm');
    const groupCodeInput = document.getElementById('groupCode');
    const joinError = document.getElementById('joinError');
    const joinSubmitBtn = document.getElementById('joinSubmitBtn');

    joinForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const groupCode = groupCodeInput.value.trim();
        if (!groupCode) {
            showError('Please enter a group code.');
            return;
        }

        // Disable submit button and show loading state
        joinSubmitBtn.disabled = true;
        joinSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Joining...';
        hideError();

        try {
            // First, look up the group by code
            const response = await fetch('{{ route("api.groups.lookup") }}?code=' + encodeURIComponent(groupCode), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success && data.group_id) {
                // Redirect to the join page for that group
                window.location.href = '{{ url("groups") }}/' + data.group_id + '/join?code=' + encodeURIComponent(groupCode);
            } else {
                showError(data.message || 'Invalid group code. Please check and try again.');
                resetButton();
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
            resetButton();
        }
    });

    function showError(message) {
        joinError.textContent = message;
        joinError.classList.remove('d-none');
    }

    function hideError() {
        joinError.classList.add('d-none');
    }

    function resetButton() {
        joinSubmitBtn.disabled = false;
        joinSubmitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Join Group';
    }

    // Clear form when modal is closed
    const modal = document.getElementById('joinGroupModal');
    modal.addEventListener('hidden.bs.modal', function() {
        groupCodeInput.value = '';
        hideError();
        resetButton();
    });

    // Focus input when modal opens
    modal.addEventListener('shown.bs.modal', function() {
        groupCodeInput.focus();
    });
});
</script>
@endsection
