@extends('layouts.app')

@section('title', $pageTitle ?? "{$prediction['symbol']} {$prediction['prediction_type']} Prediction")

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/prediction.css') }}">
@endsection

@section('content')
    @php
    // Functions from prediction_score_display.php
    function getAccuracyClass($accuracy) {
        if ($accuracy === null) {
            return 'text-secondary';
        } else if ($accuracy >= 70) {
            return 'text-success';
        } else if ($accuracy >= 40) {
            return 'text-warning';
        } else {
            return 'text-danger';
        }
    }

    function getAccuracyIcon($accuracy) {
        if ($accuracy === null) {
            return '<i class="bi bi-hourglass"></i>';
        } else if ($accuracy >= 70) {
            return '<i class="bi bi-check-circle-fill"></i>';
        } else if ($accuracy >= 40) {
            return '<i class="bi bi-exclamation-circle-fill"></i>';
        } else {
            return '<i class="bi bi-x-circle-fill"></i>';
        }
    }

    function formatAccuracy($accuracy) {
        if ($accuracy === null) {
            return 'Pending';
        }
        return number_format($accuracy, 0) . '%';
    }

    function renderPredictionBadge($accuracy) {
        $class = getAccuracyClass($accuracy);
        $icon = getAccuracyIcon($accuracy);
        $text = formatAccuracy($accuracy);

        return "<span class=\"badge $class\">$icon $text</span>";
    }

    function renderReputationScore($reputation, $avgAccuracy = null) {
        $reputationClass = $reputation >= 20 ? 'text-success' :
                          ($reputation >= 10 ? 'text-info' :
                          ($reputation >= 0 ? 'text-warning' : 'text-danger'));

        $accuracyHtml = '';
        if ($avgAccuracy !== null) {
            $accuracyClass = getAccuracyClass($avgAccuracy);
            $accuracyHtml = "<div class=\"mt-2\">Average Accuracy: <span class=\"$accuracyClass\">" .
                           formatAccuracy($avgAccuracy) . "</span></div>";
        }

        $html = <<<HTML
<div class="reputation-score">
    <h4>REP SCORE: <span class="$reputationClass">$reputation</span></h4>
    $accuracyHtml
</div>
HTML;

        return $html;
    }

    // Calculate prediction status
    $isPending = $prediction['accuracy'] === null;
    $isActive = $prediction['is_active'] == 1;
    $endDate = new DateTime($prediction['end_date']);
    $today = new DateTime();
    $daysRemaining = $today > $endDate ? 0 : $today->diff($endDate)->days;

    // Generate prediction class and icon
    $predictionClass = $prediction['prediction_type'] == 'Bullish' ? 'text-success' : 'text-danger';
    $predictionIcon = $prediction['prediction_type'] == 'Bullish' ?
        '<i class="bi bi-graph-up-arrow"></i>' :
        '<i class="bi bi-graph-down-arrow"></i>';

    // Generate badge for accuracy
    $accuracyBadge = renderPredictionBadge($prediction['accuracy']);

    // Determine user's existing vote (if any)
    $userVoted = false;
    $userVoteType = null;
    @endphp

    <div class="container mt-4">
        <div class="row">
            <!-- Main prediction content -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <span class="{{ $predictionClass }}">{!! $predictionIcon !!} {{ $prediction['prediction_type'] }}</span>
                            on <strong>{{ $prediction['symbol'] }}</strong>
                        </h3>
                        <div>
                            {!! $accuracyBadge !!}
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Prediction details -->
                        <div class="mb-4">
                            <h5>Prediction by {{ $prediction['username'] }}</h5>
                            <div class="text-muted mb-3">
                                <small>
                                    Created: {{ date('M j, Y', strtotime($prediction['prediction_date'])) }} |
                                    @if ($isPending)
                                        Ends: {{ date('M j, Y', strtotime($prediction['end_date'])) }}
                                        ({{ $daysRemaining }} days remaining)
                                    @else
                                        Ended: {{ date('M j, Y', strtotime($prediction['end_date'])) }}
                                    @endif
                                </small>
                            </div>

                            @if ($prediction['target_price'])
                            <div class="mb-3">
                                <h6>Target Price:</h6>
                                <p class="fs-4 {{ $predictionClass }}">
                                    ${{ number_format($prediction['target_price'], 2) }}
                                </p>
                            </div>
                            @endif

                            <div class="mb-3">
                                <h6>Reasoning:</h6>
                                <div class="p-3 bg-light rounded">
                                    {!! nl2br(e($prediction['reasoning'])) !!}
                                </div>
                            </div>
                        </div>

                        <!-- Voting section -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Prediction Voting</h5>
                                <p class="text-muted">Do you agree with this prediction?</p>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        @auth
                                        <button class="btn {{ $userVoteType == 'upvote' ? 'btn-success' : 'btn-outline-success' }} me-2 vote-btn"
                                                data-prediction-id="{{ $prediction['prediction_id'] }}"
                                                data-vote-type="upvote">
                                            <i class="bi bi-hand-thumbs-up"></i> Agree
                                        </button>
                                        @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-success me-2">
                                            <i class="bi bi-hand-thumbs-up"></i> Agree
                                        </a>
                                        @endauth
                                        <span class="badge bg-success ms-1">{{ $prediction['upvotes'] }}</span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        @auth
                                        <button class="btn {{ $userVoteType == 'downvote' ? 'btn-danger' : 'btn-outline-danger' }} me-2 vote-btn"
                                                data-prediction-id="{{ $prediction['prediction_id'] }}"
                                                data-vote-type="downvote">
                                            <i class="bi bi-hand-thumbs-down"></i> Disagree
                                        </button>
                                        @else
                                        <a href="{{ route('login') }}" class="btn btn-outline-danger me-2">
                                            <i class="bi bi-hand-thumbs-down"></i> Disagree
                                        </a>
                                        @endauth
                                        <span class="badge bg-danger ms-1">{{ $prediction['downvotes'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Owner actions -->
                        @if (Auth::check() && Auth::id() == $prediction['user_id'] && $isActive)
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('predictions.edit', ['id' => $prediction['prediction_id']]) }}"
                               class="btn btn-outline-primary me-2">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <button type="button" class="btn btn-outline-danger delete-prediction"
                                    data-id="{{ $prediction['prediction_id'] }}"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="card shadow-sm mb-4 comment-section">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comments ({{ isset($comments) ? $comments->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                        <!-- New Comment Form -->
                        @auth
                        <div class="comment-form mb-4">
                            <form id="new-comment-form" data-prediction-id="{{ $prediction['prediction_id'] }}">
                                @csrf
                                <div class="mb-2">
                                    <textarea class="form-control" id="new-comment-content" name="content"
                                              placeholder="Share your thoughts on this prediction..."
                                              maxlength="600" rows="3"></textarea>
                                    <div class="char-counter">
                                        <span id="char-count">0</span>/600 characters
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i>Post Comment
                                </button>
                            </form>
                        </div>
                        @else
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <a href="{{ route('login') }}">Log in</a> to join the discussion.
                        </div>
                        @endauth

                        <!-- Comments List -->
                        <div id="comments-list">
                            @if(isset($comments) && $comments->count() > 0)
                                @foreach($comments as $comment)
                                    @include('predictions.partials.comment', ['comment' => $comment, 'depth' => 0])
                                @endforeach
                            @else
                                <div class="no-comments">
                                    <i class="bi bi-chat-square-text"></i>
                                    <p>No comments yet. Be the first to share your thoughts!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Back button -->
                <div class="mb-4">
                    <a href="{{ route('predictions.trending') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Trending
                    </a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Stock information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Stock Information</h5>
                    </div>
                    <div class="card-body">
                        <h3>{{ $prediction['stock']['symbol'] }}</h3>
                        <p class="text-muted">{{ $prediction['stock']['company_name'] }}</p>

                        @if (isset($prediction['stock']['sector']))
                        <div class="mb-3">
                            <strong>Sector:</strong> {{ $prediction['stock']['sector'] }}
                        </div>
                        @endif

                        @if (isset($prediction['stock']['current_price']))
                        <div class="mb-3">
                            <strong>Current Price:</strong> ${{ number_format($prediction['stock']['current_price'], 2) }}
                        </div>
                        @endif

                        <div class="mt-4">
                            <a href="{{ route('search', ['query' => $prediction['stock']['symbol']]) }}" class="btn btn-outline-primary btn-sm">
                                View Stock Details
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Predictor Profile</h5>
                    </div>
                    <div class="card-body">
                        <h5>{{ $prediction['username'] }}</h5>

                        <!-- Display user reputation if available -->
                        @if (isset($prediction['user']['reputation_score']))
                            {!! renderReputationScore($prediction['user']['reputation_score']) !!}
                        @endif
                    </div>
                    --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
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

@section('scripts')
    <script src="{{ asset('js/prediction.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle voting
        const voteButtons = document.querySelectorAll('.vote-btn');

        voteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const predictionId = this.getAttribute('data-prediction-id');
                const voteType = this.getAttribute('data-vote-type');

                const formData = new FormData();
                formData.append('prediction_id', predictionId);
                formData.append('vote_type', voteType);

                fetch('{{ route('prediction.vote') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error submitting vote:', error);
                    alert('An error occurred while submitting your vote');
                });
            });
        });

        // Character counter for comment textarea
        const commentTextarea = document.getElementById('new-comment-content');
        const charCount = document.getElementById('char-count');

        if (commentTextarea && charCount) {
            commentTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;

                const counter = charCount.parentElement;
                counter.classList.remove('warning', 'danger');
                if (count >= 550) {
                    counter.classList.add('danger');
                } else if (count >= 450) {
                    counter.classList.add('warning');
                }
            });
        }

        // Handle new comment submission
        const newCommentForm = document.getElementById('new-comment-form');
        if (newCommentForm) {
            newCommentForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const predictionId = this.getAttribute('data-prediction-id');
                const content = document.getElementById('new-comment-content').value.trim();

                if (!content) {
                    alert('Please enter a comment');
                    return;
                }

                submitComment(predictionId, content, null);
            });
        }

        // Handle reply button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.reply-btn')) {
                const btn = e.target.closest('.reply-btn');
                const commentId = btn.getAttribute('data-comment-id');
                const replyForm = document.getElementById('reply-form-' + commentId);

                // Hide all other reply forms
                document.querySelectorAll('.reply-form').forEach(form => {
                    if (form.id !== 'reply-form-' + commentId) {
                        form.classList.remove('show');
                    }
                });

                // Toggle this reply form
                replyForm.classList.toggle('show');

                if (replyForm.classList.contains('show')) {
                    replyForm.querySelector('textarea').focus();
                }
            }

            // Handle reply form submission
            if (e.target.closest('.submit-reply-btn')) {
                const btn = e.target.closest('.submit-reply-btn');
                const commentId = btn.getAttribute('data-parent-id');
                const predictionId = btn.getAttribute('data-prediction-id');
                const textarea = document.querySelector('#reply-form-' + commentId + ' textarea');
                const content = textarea.value.trim();

                if (!content) {
                    alert('Please enter a reply');
                    return;
                }

                submitComment(predictionId, content, commentId);
            }

            // Handle delete comment button
            if (e.target.closest('.delete-comment-btn')) {
                const btn = e.target.closest('.delete-comment-btn');
                const commentId = btn.getAttribute('data-comment-id');

                if (confirm('Are you sure you want to delete this comment?')) {
                    deleteComment(commentId);
                }
            }
        });

        // Handle reply form character counter
        document.addEventListener('input', function(e) {
            if (e.target.matches('.reply-textarea')) {
                const count = e.target.value.length;
                const counter = e.target.closest('.reply-form').querySelector('.reply-char-count');
                if (counter) {
                    counter.textContent = count;

                    const counterParent = counter.parentElement;
                    counterParent.classList.remove('warning', 'danger');
                    if (count >= 550) {
                        counterParent.classList.add('danger');
                    } else if (count >= 450) {
                        counterParent.classList.add('warning');
                    }
                }
            }
        });

        function submitComment(predictionId, content, parentCommentId) {
            const formData = new FormData();
            formData.append('prediction_id', predictionId);
            formData.append('content', content);
            if (parentCommentId) {
                formData.append('parent_comment_id', parentCommentId);
            }

            fetch('{{ route('comments.store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show new comment
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error posting comment:', error);
                alert('An error occurred while posting your comment');
            });
        }

        function deleteComment(commentId) {
            fetch('/comments/' + commentId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting comment:', error);
                alert('An error occurred while deleting the comment');
            });
        }
    });
    </script>
@endsection
