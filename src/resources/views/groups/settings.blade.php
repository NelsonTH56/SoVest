@extends('layouts.app')

@section('title', $group->name . ' Settings - SoVest')

@section('content')
<div class="settings-page">
    <div class="settings-header text-center mb-4">
        <h1 class="page-title">
            <i class="bi bi-gear-fill" style="color: #10b981;"></i>
            Group Settings
        </h1>
        <p class="page-subtitle">Manage {{ $group->name }}</p>
    </div>

    {{-- Back Button --}}
    <div class="mb-4">
        <a href="{{ route('groups.show', $group->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Group
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Group Code Section --}}
    <div class="settings-section mb-4">
        <h4 class="section-title">
            <i class="bi bi-share me-2"></i>
            Invite Members
        </h4>
        <div class="section-content">
            <p class="text-muted mb-3">Share this group code with people you want to invite:</p>
            <div class="group-code-display">
                <span class="group-code" id="groupCode">{{ $group->code }}</span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="copyCodeBtn" onclick="copyGroupCode()">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
            <div class="form-text mt-2">Members will also need the passcode to join.</div>
        </div>
    </div>

    {{-- Change Passcode Section --}}
    <div class="settings-section mb-4">
        <h4 class="section-title">
            <i class="bi bi-key me-2"></i>
            Change Passcode
        </h4>
        <div class="section-content">
            <form action="{{ route('groups.updatePasscode', $group->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="passcode" class="form-label">New Passcode</label>
                    <div class="input-group">
                        <input type="password"
                               class="form-control"
                               id="passcode"
                               name="passcode"
                               placeholder="Enter new passcode"
                               required
                               minlength="4"
                               maxlength="50">
                        <button class="btn btn-outline-secondary" type="button" id="togglePasscode">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">4-50 characters. Share the new passcode with members who need to join.</div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Passcode
                </button>
            </form>
        </div>
    </div>

    {{-- Manage Members Section --}}
    <div class="settings-section">
        <h4 class="section-title">
            <i class="bi bi-people me-2"></i>
            Manage Members ({{ $members->count() }})
        </h4>
        <div class="section-content">
            @if($members->count() > 0)
                <div class="members-list">
                    @foreach($members as $member)
                        <div class="member-row">
                            <div class="member-info">
                                <span class="member-name">{{ $member->first_name }} {{ $member->last_name }}</span>
                                <span class="member-email">{{ $member->email }}</span>
                                @if($member->id == $group->admin_id)
                                    <span class="admin-badge">Admin (You)</span>
                                @endif
                            </div>
                            <div class="member-actions">
                                @if($member->id != $group->admin_id)
                                    <form action="{{ route('groups.removeMember', ['id' => $group->id, 'userId' => $member->id]) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to remove {{ $member->first_name }} {{ $member->last_name }} from the group?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-person-x"></i> Remove
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No members in this group yet.</p>
            @endif
        </div>
    </div>

    {{-- Group Info --}}
    <div class="settings-section mt-4">
        <h4 class="section-title">
            <i class="bi bi-info-circle me-2"></i>
            Group Information
        </h4>
        <div class="section-content">
            <dl class="info-list">
                <dt>Name</dt>
                <dd>{{ $group->name }}</dd>

                <dt>Description</dt>
                <dd>{{ $group->description ?: 'No description' }}</dd>

                <dt>Created</dt>
                <dd>{{ $group->created_at->format('F j, Y') }}</dd>

                <dt>Members</dt>
                <dd>{{ $members->count() }}</dd>
            </dl>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.settings-page {
    max-width: 700px;
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

.settings-section {
    background: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

body.dark-mode .settings-section {
    background: #2a2a2a;
    border-color: #404040;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #111827;
    padding: 1rem 1.25rem;
    margin: 0;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

body.dark-mode .section-title {
    color: #f3f4f6;
    background: #333333;
    border-bottom-color: #404040;
}

.section-content {
    padding: 1.25rem;
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

.members-list {
    max-height: 400px;
    overflow-y: auto;
}

.member-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
}

body.dark-mode .member-row {
    border-bottom-color: #404040;
}

.member-row:last-child {
    border-bottom: none;
}

.member-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.member-name {
    font-weight: 600;
    color: #111827;
}

body.dark-mode .member-name {
    color: #f3f4f6;
}

.member-email {
    font-size: 0.85rem;
    color: #6b7280;
}

body.dark-mode .member-email {
    color: #9ca3af;
}

.admin-badge {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    background: #f59e0b;
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    width: fit-content;
}

.info-list {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 0.75rem;
}

.info-list dt {
    font-weight: 600;
    color: #6b7280;
}

body.dark-mode .info-list dt {
    color: #9ca3af;
}

.info-list dd {
    margin: 0;
    color: #111827;
}

body.dark-mode .info-list dd {
    color: #f3f4f6;
}

/* Group Code Display */
.group-code-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    padding: 1rem;
}

body.dark-mode .group-code-display {
    background: #1a1a1a;
}

.group-code {
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 4px;
    color: #10b981;
    font-family: monospace;
}

@media (max-width: 640px) {
    .member-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .info-list {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .info-list dt {
        margin-bottom: 0.25rem;
    }
}
</style>
@endsection

@section('scripts')
<script>
function copyGroupCode() {
    const code = document.getElementById('groupCode').textContent;
    navigator.clipboard.writeText(code).then(function() {
        const btn = document.getElementById('copyCodeBtn');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}

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
