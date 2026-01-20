@extends('layouts.app')

@section('title', 'Groups - SoVest')

@section('content')
<div class="groups-page">
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

    @if(empty($userGroups))
        {{-- No Groups State: Centered buttons --}}
        <div class="no-groups-state">
            <div class="empty-state-content text-center">
                <i class="bi bi-people" style="font-size: 4rem; color: #9ca3af;"></i>
                <h2 class="mt-3">Welcome to Groups</h2>
                <p class="text-muted mb-4">Join a group to compete on private leaderboards with friends, colleagues, or communities.</p>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="{{ route('groups.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Create Group
                    </a>
                    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#joinGroupModal">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Join Group
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- Has Groups State: Three-column layout --}}
        <div class="groups-layout">
            {{-- Top bar with buttons on the right --}}
            <div class="groups-top-bar d-flex justify-content-between align-items-center mb-4">
                <h1 class="groups-title mb-0">
                    <i class="bi bi-people-fill" style="color: #10b981;"></i>
                    Groups
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('groups.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Create
                    </a>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#joinGroupModal">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Join
                    </button>
                </div>
            </div>

            <div class="row">
                {{-- Left Sidebar: Group Menu --}}
                <div class="col-lg-3 col-md-4">
                    <div class="group-menu-sidebar">
                        <h5 class="sidebar-title">
                            <i class="bi bi-star-fill" style="color: #f59e0b;"></i>
                            My Groups
                        </h5>
                        <div class="group-menu-list">
                            @foreach($userGroups as $group)
                                <a href="{{ route('groups.show', $group['id']) }}" class="group-menu-item">
                                    <div class="group-menu-info">
                                        <span class="group-menu-name">{{ $group['name'] }}</span>
                                        <span class="group-menu-meta">
                                            <i class="bi bi-people"></i> {{ $group['member_count'] }}
                                            @if($group['is_admin'])
                                                <span class="admin-indicator">Admin</span>
                                            @endif
                                        </span>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Main Content: Group Feed --}}
                <div class="col-lg-9 col-md-8">
                    <div class="group-feed-section">
                        <h4 class="section-title mb-3">
                            <i class="bi bi-lightning-fill" style="color: #10b981;"></i>
                            Group Activity
                        </h4>

                        <div class="group-feed-cards">
                            @foreach($userGroups as $group)
                                <div class="group-feed-card">
                                    <div class="group-feed-header">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="group-name mb-1">{{ $group['name'] }}</h5>
                                                @if(!empty($group['description']))
                                                    <p class="group-description mb-2">{{ Str::limit($group['description'], 100) }}</p>
                                                @endif
                                            </div>
                                            @if($group['is_admin'])
                                                <span class="admin-badge">Admin</span>
                                            @endif
                                        </div>
                                        <div class="group-meta">
                                            <span class="member-count">
                                                <i class="bi bi-people"></i> {{ $group['member_count'] }} members
                                            </span>
                                        </div>
                                    </div>
                                    <div class="group-feed-actions">
                                        <a href="{{ route('groups.show', $group['id']) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-trophy me-1"></i>View Leaderboard
                                        </a>
                                        @if($group['is_admin'])
                                            <a href="{{ route('groups.settings', $group['id']) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-gear"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Discover More Groups --}}
                        @php
                            $nonMemberGroups = array_filter($allGroups, fn($g) => !$g['is_member']);
                        @endphp

                        @if(!empty($nonMemberGroups))
                            <div class="discover-section mt-4">
                                <h4 class="section-title mb-3">
                                    <i class="bi bi-globe"></i>
                                    Discover More Groups
                                </h4>
                                <div class="discover-groups-grid">
                                    @foreach($nonMemberGroups as $group)
                                        <div class="discover-group-card">
                                            <div class="group-card-body">
                                                <h6 class="group-name">{{ $group['name'] }}</h6>
                                                @if(!empty($group['description']))
                                                    <p class="group-description">{{ Str::limit($group['description'], 60) }}</p>
                                                @endif
                                                <div class="group-meta">
                                                    <span class="member-count">
                                                        <i class="bi bi-people"></i> {{ $group['member_count'] }}
                                                    </span>
                                                </div>
                                                <a href="{{ route('groups.join', $group['id']) }}" class="btn btn-sm btn-success w-100 mt-2">
                                                    <i class="bi bi-box-arrow-in-right"></i> Join
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

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
</div>
@endsection

@section('styles')
<style>
.groups-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
}

/* No Groups State */
.no-groups-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
}

.empty-state-content h2 {
    font-size: 1.75rem;
    font-weight: 800;
    color: #111827;
}

body.dark-mode .empty-state-content h2 {
    color: #f3f4f6;
}

/* Groups Layout (with groups) */
.groups-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.dark-mode .groups-title {
    color: #f3f4f6;
}

/* Sidebar */
.group-menu-sidebar {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    padding: 1rem;
    position: sticky;
    top: 1rem;
}

body.dark-mode .group-menu-sidebar {
    background: #2a2a2a;
    border-color: #404040;
}

.sidebar-title {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

body.dark-mode .sidebar-title {
    color: #f3f4f6;
    border-bottom-color: #404040;
}

.group-menu-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.group-menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    border-radius: 0.5rem;
    text-decoration: none;
    color: #374151;
    background: #f9fafb;
    transition: all 0.2s ease;
}

body.dark-mode .group-menu-item {
    background: #1f1f1f;
    color: #d1d5db;
}

.group-menu-item:hover {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.group-menu-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.group-menu-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.group-menu-meta {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.dark-mode .group-menu-meta {
    color: #9ca3af;
}

.admin-indicator {
    background: #f59e0b;
    color: white;
    font-size: 0.6rem;
    padding: 0.1rem 0.35rem;
    border-radius: 999px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Section Title */
.section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

body.dark-mode .section-title {
    color: #f3f4f6;
}

/* Group Feed Cards */
.group-feed-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.group-feed-card {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    padding: 1.25rem;
    transition: box-shadow 0.2s;
}

body.dark-mode .group-feed-card {
    background: #2a2a2a;
    border-color: #404040;
}

.group-feed-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
}

body.dark-mode .group-name {
    color: #f3f4f6;
}

.group-description {
    font-size: 0.9rem;
    color: #6b7280;
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

.member-count {
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

.group-feed-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 0.5rem;
}

body.dark-mode .group-feed-actions {
    border-top-color: #404040;
}

/* Discover Groups */
.discover-groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.discover-group-card {
    background: #ffffff;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    transition: transform 0.2s, box-shadow 0.2s;
}

body.dark-mode .discover-group-card {
    background: #2a2a2a;
    border-color: #404040;
}

.discover-group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.discover-group-card .group-card-body {
    padding: 1rem;
}

.discover-group-card .group-name {
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.discover-group-card .group-description {
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
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

/* Responsive */
@media (max-width: 767.98px) {
    .groups-layout .row {
        flex-direction: column;
    }

    .group-menu-sidebar {
        position: static;
        margin-bottom: 1.5rem;
    }

    .group-menu-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }

    .discover-groups-grid {
        grid-template-columns: 1fr;
    }
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
