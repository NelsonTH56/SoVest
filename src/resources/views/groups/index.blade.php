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
                <div class="empty-state-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h2>Welcome to Groups</h2>
                <p class="text-muted">Join a group to compete on private leaderboards with friends, colleagues, or communities.</p>

                <div class="empty-state-actions">
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
        {{-- Has Groups State: Sidebar + Main Content --}}
        <div class="groups-layout">
            {{-- Page Header --}}
            <header class="groups-header">
                <div class="groups-header-content">
                    <h1 class="groups-title">
                        <i class="bi bi-people-fill"></i>
                        <span>Groups</span>
                    </h1>
                    <p class="groups-subtitle">Manage your groups and track leaderboard standings</p>
                </div>
                <div class="groups-header-actions">
                    <a href="{{ route('groups.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#joinGroupModal">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Join
                    </button>
                </div>
            </header>

            <div class="groups-content">
                {{-- Left Sidebar: Group Navigation --}}
                <aside class="groups-sidebar">
                    <div class="sidebar-card">
                        <div class="sidebar-header">
                            <i class="bi bi-star-fill"></i>
                            <span>My Groups</span>
                            <span class="group-count">{{ count($userGroups) }}</span>
                        </div>
                        <nav class="group-nav">
                            @foreach($userGroups as $group)
                                <a href="{{ route('groups.show', $group['id']) }}" class="group-nav-item">
                                    <div class="group-nav-content">
                                        <span class="group-nav-name">{{ $group['name'] }}</span>
                                        <span class="group-nav-meta">
                                            <i class="bi bi-people"></i>
                                            <span>{{ $group['member_count'] }}</span>
                                            @if($group['is_admin'])
                                                <span class="role-badge role-admin">Admin</span>
                                            @endif
                                        </span>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Main Content: Group Cards --}}
                <main class="groups-main">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="bi bi-lightning-fill"></i>
                            <span>Group Activity</span>
                        </h2>
                    </div>

                    <div class="group-cards">
                        @foreach($userGroups as $group)
                            <article class="group-card">
                                <div class="group-card-header">
                                    <div class="group-card-title-row">
                                        <h3 class="group-card-name">{{ $group['name'] }}</h3>
                                        @if($group['is_admin'])
                                            <span class="role-badge role-admin">Admin</span>
                                        @endif
                                    </div>
                                    @if(!empty($group['description']))
                                        <p class="group-card-description">{{ Str::limit($group['description'], 120) }}</p>
                                    @endif
                                </div>

                                <div class="group-card-stats">
                                    <div class="stat-item">
                                        <i class="bi bi-people-fill"></i>
                                        <span>{{ $group['member_count'] }} {{ $group['member_count'] === 1 ? 'member' : 'members' }}</span>
                                    </div>
                                </div>

                                <div class="group-card-actions">
                                    <a href="{{ route('groups.show', $group['id']) }}" class="btn btn-primary">
                                        <i class="bi bi-trophy me-2"></i>View Leaderboard
                                    </a>
                                    @if($group['is_admin'])
                                        <a href="{{ route('groups.settings', $group['id']) }}" class="btn btn-ghost" title="Group Settings">
                                            <i class="bi bi-gear-fill"></i>
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </main>
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
                <form id="joinGroupForm" action="{{ route('groups.joinByName') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">Enter the group name and passcode provided by the group admin.</p>

                        <div class="mb-3">
                            <label for="groupName" class="form-label">Group Name</label>
                            <input type="text"
                                   class="form-control"
                                   id="groupName"
                                   name="group_name"
                                   placeholder="Enter group name"
                                   required
                                   autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="groupPasscode" class="form-label">Group Passcode</label>
                            <input type="text"
                                   class="form-control"
                                   id="groupPasscode"
                                   name="passcode"
                                   placeholder="Enter group passcode"
                                   required
                                   autocomplete="off">
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
/* ========================================
   Groups Page - Modern Layout System
   ======================================== */

.groups-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}

/* ----------------------------------------
   Empty State (No Groups)
   ---------------------------------------- */
.no-groups-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
}

.empty-state-content {
    max-width: 420px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
    border-radius: 50%;
}

.empty-state-icon i {
    font-size: 2.5rem;
    color: #10b981;
}

.empty-state-content h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.75rem;
}

body.dark-mode .empty-state-content h2 {
    color: #f3f4f6;
}

.empty-state-content p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.empty-state-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* ----------------------------------------
   Page Header
   ---------------------------------------- */
.groups-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

body.dark-mode .groups-header {
    border-bottom-color: #404040;
}

.groups-header-content {
    flex: 1;
}

.groups-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    margin: 0 0 0.375rem 0;
}

.groups-title i {
    color: #10b981;
    font-size: 1.5rem;
}

body.dark-mode .groups-title {
    color: #f3f4f6;
}

.groups-subtitle {
    font-size: 0.9375rem;
    color: #6b7280;
    margin: 0;
}

body.dark-mode .groups-subtitle {
    color: #9ca3af;
}

.groups-header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

/* ----------------------------------------
   Content Layout (Sidebar + Main)
   ---------------------------------------- */
.groups-content {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
}

/* ----------------------------------------
   Sidebar
   ---------------------------------------- */
.groups-sidebar {
    position: sticky;
    top: 1.5rem;
}

.sidebar-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

body.dark-mode .sidebar-card {
    background: #2a2a2a;
    border-color: #404040;
}

.sidebar-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    background: rgba(16, 185, 129, 0.05);
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    font-size: 0.9375rem;
    color: #111827;
}

body.dark-mode .sidebar-header {
    background: rgba(16, 185, 129, 0.08);
    border-bottom-color: #404040;
    color: #f3f4f6;
}

.sidebar-header i {
    color: #f59e0b;
    font-size: 1rem;
}

.group-count {
    margin-left: auto;
    background: #10b981;
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    min-width: 1.5rem;
    text-align: center;
}

.group-nav {
    display: flex;
    flex-direction: column;
}

.group-nav-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    text-decoration: none;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.15s ease;
}

body.dark-mode .group-nav-item {
    color: #d1d5db;
    border-bottom-color: #333333;
}

.group-nav-item:last-child {
    border-bottom: none;
}

.group-nav-item:hover {
    background: rgba(16, 185, 129, 0.08);
}

.group-nav-item:hover .group-nav-name {
    color: #10b981;
}

.group-nav-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
    flex: 1;
}

.group-nav-name {
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.15s ease;
}

.group-nav-meta {
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

body.dark-mode .group-nav-meta {
    color: #9ca3af;
}

.group-nav-item > i {
    color: #9ca3af;
    font-size: 0.875rem;
    flex-shrink: 0;
    margin-left: 0.75rem;
}

/* Role Badges (shared) */
.role-badge {
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
}

.role-admin {
    background: #f59e0b;
    color: white;
}

/* ----------------------------------------
   Main Content Area
   ---------------------------------------- */
.groups-main {
    min-width: 0;
}

.section-header {
    margin-bottom: 1.25rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.section-title i {
    color: #10b981;
}

body.dark-mode .section-title {
    color: #f3f4f6;
}

/* ----------------------------------------
   Group Cards
   ---------------------------------------- */
.group-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.group-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

body.dark-mode .group-card {
    background: #2a2a2a;
    border-color: #404040;
}

.group-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-color: #d1d5db;
}

body.dark-mode .group-card:hover {
    border-color: #525252;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.group-card-header {
    margin-bottom: 1rem;
}

.group-card-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.group-card-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

body.dark-mode .group-card-name {
    color: #f3f4f6;
}

.group-card-description {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

body.dark-mode .group-card-description {
    color: #9ca3af;
}

.group-card-stats {
    display: flex;
    gap: 1.5rem;
    padding: 0.875rem 0;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 1rem;
}

body.dark-mode .group-card-stats {
    border-color: #404040;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

body.dark-mode .stat-item {
    color: #9ca3af;
}

.stat-item i {
    color: #9ca3af;
}

body.dark-mode .stat-item i {
    color: #6b7280;
}

.group-card-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Ghost Button Style */
.btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: transparent;
    color: #6b7280;
    font-size: 0.875rem;
    transition: all 0.15s ease;
    text-decoration: none;
}

body.dark-mode .btn-ghost {
    border-color: #404040;
    color: #9ca3af;
}

.btn-ghost:hover {
    background: #f3f4f6;
    color: #374151;
    border-color: #d1d5db;
}

body.dark-mode .btn-ghost:hover {
    background: #333333;
    color: #f3f4f6;
    border-color: #525252;
}

/* ----------------------------------------
   Modal Styles
   ---------------------------------------- */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

body.dark-mode .modal-content {
    background: #2a2a2a;
}

.modal-header {
    border-bottom: 1px solid #e5e7eb;
    padding: 1.25rem 1.5rem;
}

body.dark-mode .modal-header {
    border-bottom-color: #404040;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #e5e7eb;
    padding: 1rem 1.5rem;
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

/* ----------------------------------------
   Responsive Design
   ---------------------------------------- */
@media (max-width: 991.98px) {
    .groups-content {
        grid-template-columns: 240px 1fr;
        gap: 1.5rem;
    }
}

@media (max-width: 767.98px) {
    .groups-page {
        padding: 1rem;
    }

    .groups-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .groups-header-actions {
        justify-content: flex-start;
    }

    .groups-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .groups-sidebar {
        position: static;
    }

    .sidebar-card {
        border-radius: 10px;
    }

    .group-nav {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }

    .group-nav-item {
        border-bottom: none;
        border-radius: 8px;
        margin: 0.25rem;
        background: #f9fafb;
    }

    body.dark-mode .group-nav-item {
        background: #1f1f1f;
    }

    .group-card {
        padding: 1.25rem;
        border-radius: 10px;
    }

    .group-card-actions {
        flex-wrap: wrap;
    }

    .group-card-actions .btn-primary {
        flex: 1;
        min-width: 140px;
    }
}

@media (max-width: 479.98px) {
    .empty-state-actions {
        flex-direction: column;
    }

    .empty-state-actions .btn {
        width: 100%;
    }

    .group-nav {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const joinForm = document.getElementById('joinGroupForm');
    const groupNameInput = document.getElementById('groupName');
    const groupPasscodeInput = document.getElementById('groupPasscode');
    const joinError = document.getElementById('joinError');
    const joinSubmitBtn = document.getElementById('joinSubmitBtn');

    joinForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const groupName = groupNameInput.value.trim();
        const passcode = groupPasscodeInput.value.trim();

        if (!groupName) {
            showError('Please enter a group name.');
            return;
        }

        if (!passcode) {
            showError('Please enter the group passcode.');
            return;
        }

        // Disable submit button and show loading state
        joinSubmitBtn.disabled = true;
        joinSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Joining...';
        hideError();

        try {
            const response = await fetch('{{ route("groups.joinByName") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    group_name: groupName,
                    passcode: passcode
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect || '{{ route("groups.index") }}';
            } else {
                showError(data.message || 'Unable to join group. Please check the name and passcode.');
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
        groupNameInput.value = '';
        groupPasscodeInput.value = '';
        hideError();
        resetButton();
    });

    // Focus input when modal opens
    modal.addEventListener('shown.bs.modal', function() {
        groupNameInput.focus();
    });
});
</script>
@endsection
