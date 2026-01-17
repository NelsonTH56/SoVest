@extends('layouts.app')

@section('title', $pageTitle ?? 'My Predictions')

@section('content')
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-6 page-title">{{ $pageTitle ?? 'My Predictions' }}</h1>
            <a href="{{ route('predictions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Create New Prediction
            </a>
        </div>

        @if(empty($predictions))
            <div class="prediction-card text-center" style="padding: 3rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                <h4 style="margin-bottom: 0.5rem;">No predictions yet</h4>
                <p class="text-muted">You haven't made any stock predictions yet. Create your first prediction to start building your reputation!</p>
                <a href="{{ route('predictions.create') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-2"></i>Create Your First Prediction
                </a>
            </div>
        @else
            @foreach($predictions as $prediction)
                <x-prediction-card
                    :prediction="$prediction"
                    :show-comments="true"
                    :show-votes="true"
                    :clickable="false"
                />
            @endforeach
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this prediction? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
<style>
    /* Page title styling */
    .page-title {
        color: #111827;
        font-weight: 700;
        transition: color 0.3s ease;
    }

    body.dark-mode .page-title {
        color: #f3f4f6;
    }

    /* Prediction card hover effects */
    .prediction-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .prediction-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
    }

    /* Badge animation styles (same as home page) */
    .prediction-badge-vibrant {
        padding: 0.5rem 1.2rem;
        font-size: 0.875rem;
        font-weight: 700;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .badge-bullish {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    .badge-bearish {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    @keyframes bounceIn {
        0% {
            transform: scale(0.3);
            opacity: 0;
        }
        50% {
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Vote button hover effects */
    .vote-btn:hover {
        transform: scale(1.05);
    }

    .upvote-btn:hover {
        background: rgba(16, 185, 129, 0.2) !important;
        border-color: #10b981 !important;
    }

    .downvote-btn:hover {
        background: rgba(239, 68, 68, 0.2) !important;
        border-color: #ef4444 !important;
    }

    .vote-btn.voted-up {
        background: rgba(16, 185, 129, 0.25) !important;
        border-color: #10b981 !important;
    }

    .vote-btn.voted-down {
        background: rgba(239, 68, 68, 0.25) !important;
        border-color: #ef4444 !important;
    }

    /* Dark mode vote buttons */
    body.dark-mode .upvote-btn {
        background: rgba(16, 185, 129, 0.15) !important;
    }

    body.dark-mode .downvote-btn {
        background: rgba(239, 68, 68, 0.15) !important;
    }

    body.dark-mode .upvote-btn:hover {
        background: rgba(16, 185, 129, 0.25) !important;
    }

    body.dark-mode .downvote-btn:hover {
        background: rgba(239, 68, 68, 0.25) !important;
    }

    /* Comments toggle button */
    .comments-toggle:hover {
        background: rgba(59, 130, 246, 0.2) !important;
        border-color: #3b82f6 !important;
    }

    body.dark-mode .comments-toggle {
        background: rgba(59, 130, 246, 0.15) !important;
    }

    body.dark-mode .comments-toggle:hover {
        background: rgba(59, 130, 246, 0.25) !important;
    }

    /* Comments section styles */
    .comments-section {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 1000px;
        }
    }

    .comment-input {
        border: 2px solid #e5e7eb;
        transition: all 0.2s;
    }

    .comment-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    body.dark-mode .comment-input {
        background-color: #2a2a2a;
        border-color: #404040;
        color: #e5e7eb;
    }

    body.dark-mode .comment-input:focus {
        border-color: #3b82f6;
        background-color: #2d2d2d;
    }

    body.dark-mode .comment-input::placeholder {
        color: #6b7280;
    }

    /* Individual comment styles */
    .comment-item {
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        background: rgba(0, 0, 0, 0.02);
        border-left: 3px solid #e5e7eb;
    }

    .comment-item:hover {
        background: rgba(0, 0, 0, 0.04);
    }

    body.dark-mode .comment-item {
        background: rgba(255, 255, 255, 0.03);
        border-left-color: #404040;
    }

    body.dark-mode .comment-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .comment-author {
        font-weight: 600;
        font-size: 0.9rem;
        color: #111827;
    }

    body.dark-mode .comment-author {
        color: #f3f4f6;
    }

    .comment-content {
        font-size: 0.9rem;
        color: #374151;
        margin-top: 0.25rem;
        line-height: 1.5;
    }

    body.dark-mode .comment-content {
        color: #d1d5db;
    }

    .comment-meta {
        font-size: 0.8rem;
        color: #6b7280;
    }

    .reply-item {
        margin-left: 1.5rem;
        padding-left: 0.75rem;
        border-left: 2px solid rgba(59, 130, 246, 0.3);
    }

    .no-comments-msg {
        text-align: center;
        padding: 1rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .reply-btn {
        background: none;
        border: none;
        color: #6b7280;
        font-size: 0.8rem;
        cursor: pointer;
        padding: 0;
    }

    .reply-btn:hover {
        color: #3b82f6;
    }

    .reply-form {
        margin-top: 0.5rem;
        display: none;
    }

    .reply-form.show {
        display: block;
    }

    /* Dark mode text colors */
    body.dark-mode .prediction-card .fw-bold {
        color: #f3f4f6 !important;
    }

    body.dark-mode .prediction-card h5 {
        color: #f3f4f6 !important;
    }

    body.dark-mode .prediction-card .reasoning-text {
        color: #d1d5db !important;
    }

    body.dark-mode .prediction-card small {
        color: #9ca3af !important;
    }

    body.dark-mode .prediction-card .border-top {
        border-color: #404040 !important;
    }

    body.dark-mode .prediction-card h4 {
        color: #f3f4f6 !important;
    }
</style>
@endsection

@section('scripts')
    <script src="{{ asset('js/prediction.js') }}"></script>
    <script type="text/javascript">
        // Update API endpoint for prediction.js to use Laravel routes
        const apiEndpoints = {
            searchStocks: '{{ route('api.search.stocks') }}',
        };
    </script>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const voteButtons = document.querySelectorAll(".vote-btn");

    voteButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const predictionId = this.getAttribute('data-id');
            const voteType = this.getAttribute('data-action');

            fetch('/predictions/vote/' + predictionId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    vote_type: voteType,
                    prediction_id: predictionId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    updateVoteCount(predictionId);
                    toggleVoteStyle(button, voteType);
                } else {
                    alert(data.message || "Something went wrong.");
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error submitting vote.");
            });
        });
    });

    function updateVoteCount(predictionId) {
        fetch(`/predictions/${predictionId}/vote-counts`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`upvotes-${predictionId}`).textContent = data.upvotes;
                    document.getElementById(`downvotes-${predictionId}`).textContent = data.downvotes;
                }
            });
    }

    function toggleVoteStyle(button, voteType) {
        const voteClass = voteType === 'upvote' ? 'voted-up' : 'voted-down';
        if (button.classList.contains(voteClass)) {
            button.classList.remove(voteClass);
        } else {
            button.classList.add(voteClass);
        }
    }

    // Comments functionality
    const loadedComments = new Set();

    // Toggle comments section
    document.querySelectorAll('.comments-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const predictionId = this.getAttribute('data-prediction-id');
            const commentsSection = document.getElementById('comments-' + predictionId);

            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                if (!loadedComments.has(predictionId)) {
                    loadComments(predictionId);
                    loadedComments.add(predictionId);
                }
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });

    // Load comments for a prediction
    function loadComments(predictionId) {
        const commentsList = document.getElementById('comments-list-' + predictionId);

        fetch('/predictions/' + predictionId + '/comments')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    commentsList.innerHTML = data.data.map(comment => renderComment(comment, predictionId)).join('');
                } else {
                    commentsList.innerHTML = '<div class="no-comments-msg"><i class="bi bi-chat-square-text me-2"></i>No comments yet. Be the first to share your thoughts!</div>';
                }
            })
            .catch(err => {
                console.error(err);
                commentsList.innerHTML = '<div class="no-comments-msg text-danger">Error loading comments</div>';
            });
    }

    // Render a single comment with replies
    function renderComment(comment, predictionId, isReply = false) {
        const replyClass = isReply ? 'reply-item' : '';
        let html = `
            <div class="comment-item ${replyClass}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="comment-author">${escapeHtml(comment.user.name)}</span>
                        <span class="comment-meta ms-2">
                            <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                            ${comment.user.reputation_score} pts
                        </span>
                    </div>
                    <span class="comment-meta">${comment.created_at}</span>
                </div>
                <div class="comment-content">${escapeHtml(comment.content)}</div>
                <div class="mt-2">
                    <button class="reply-btn" onclick="toggleReplyForm(${comment.comment_id}, ${predictionId})">
                        <i class="bi bi-reply me-1"></i>Reply
                    </button>
                </div>
                <div class="reply-form" id="reply-form-${comment.comment_id}">
                    <div class="d-flex gap-2 mt-2">
                        <input type="text" class="form-control comment-input reply-input"
                               placeholder="Write a reply..."
                               id="reply-input-${comment.comment_id}"
                               style="border-radius: 20px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                        <button class="btn btn-primary btn-sm" onclick="submitReply(${comment.comment_id}, ${predictionId})" style="border-radius: 20px;">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
        `;

        // Render replies
        if (comment.replies && comment.replies.length > 0) {
            html += '<div class="mt-2">';
            comment.replies.forEach(reply => {
                html += renderComment(reply, predictionId, true);
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    // Submit new comment
    document.querySelectorAll('.submit-comment').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const predictionId = this.getAttribute('data-prediction-id');
            const input = document.querySelector(`.comment-input[data-prediction-id="${predictionId}"]`);
            const content = input.value.trim();

            if (!content) return;

            submitComment(predictionId, content, null, input);
        });
    });

    // Handle enter key for comment input
    document.querySelectorAll('.comment-input[data-prediction-id]').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const predictionId = this.getAttribute('data-prediction-id');
                const content = this.value.trim();

                if (!content) return;

                submitComment(predictionId, content, null, this);
            }
        });
    });

    function submitComment(predictionId, content, parentId, inputElement) {
        const formData = new FormData();
        formData.append('prediction_id', predictionId);
        formData.append('content', content);
        if (parentId) {
            formData.append('parent_comment_id', parentId);
        }

        fetch('/comments', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                inputElement.value = '';
                loadedComments.delete(predictionId);
                loadComments(predictionId);

                // Update comment count
                const countSpan = document.querySelector(`.comments-toggle[data-prediction-id="${predictionId}"] .comment-count`);
                if (countSpan) {
                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                }
            } else {
                alert(data.message || 'Error posting comment');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error posting comment');
        });
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// Global functions for reply handling
function toggleReplyForm(commentId, predictionId) {
    const replyForm = document.getElementById('reply-form-' + commentId);
    if (replyForm) {
        replyForm.classList.toggle('show');
        if (replyForm.classList.contains('show')) {
            document.getElementById('reply-input-' + commentId).focus();
        }
    }
}

function submitReply(parentId, predictionId) {
    const input = document.getElementById('reply-input-' + parentId);
    const content = input.value.trim();

    if (!content) return;

    const formData = new FormData();
    formData.append('prediction_id', predictionId);
    formData.append('content', content);
    formData.append('parent_comment_id', parentId);

    fetch('/comments', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            document.getElementById('reply-form-' + parentId).classList.remove('show');
            // Reload comments
            const commentsList = document.getElementById('comments-list-' + predictionId);
            fetch('/predictions/' + predictionId + '/comments')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        commentsList.innerHTML = data.data.map(comment => renderCommentGlobal(comment, predictionId)).join('');
                    }
                });
        } else {
            alert(data.message || 'Error posting reply');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error posting reply');
    });
}

// Global version of renderComment for use outside DOMContentLoaded
function renderCommentGlobal(comment, predictionId, isReply = false) {
    const replyClass = isReply ? 'reply-item' : '';
    let html = `
        <div class="comment-item ${replyClass}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="comment-author">${escapeHtmlGlobal(comment.user.name)}</span>
                    <span class="comment-meta ms-2">
                        <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i>
                        ${comment.user.reputation_score} pts
                    </span>
                </div>
                <span class="comment-meta">${comment.created_at}</span>
            </div>
            <div class="comment-content">${escapeHtmlGlobal(comment.content)}</div>
            <div class="mt-2">
                <button class="reply-btn" onclick="toggleReplyForm(${comment.comment_id}, ${predictionId})">
                    <i class="bi bi-reply me-1"></i>Reply
                </button>
            </div>
            <div class="reply-form" id="reply-form-${comment.comment_id}">
                <div class="d-flex gap-2 mt-2">
                    <input type="text" class="form-control comment-input reply-input"
                           placeholder="Write a reply..."
                           id="reply-input-${comment.comment_id}"
                           style="border-radius: 20px; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                    <button class="btn btn-primary btn-sm" onclick="submitReply(${comment.comment_id}, ${predictionId})" style="border-radius: 20px;">
                        <i class="bi bi-send"></i>
                    </button>
                </div>
            </div>
    `;

    if (comment.replies && comment.replies.length > 0) {
        html += '<div class="mt-2">';
        comment.replies.forEach(reply => {
            html += renderCommentGlobal(reply, predictionId, true);
        });
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function escapeHtmlGlobal(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
