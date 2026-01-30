/**
 * Global Prediction Modal System
 * Provides site-wide modal functionality for viewing prediction details.
 * Handles URL state management, voting, comments, and sharing.
 */

class PredictionModal {
    constructor() {
        this.modal = null;
        this.backdrop = null;
        this.content = null;
        this.closeBtn = null;
        this.currentPredictionId = null;
        this.isModalNavigation = false;
        this.csrfToken = null;
        this.isAuthenticated = false;

        this.init();
    }

    init() {
        // Get DOM elements
        this.modal = document.getElementById('globalPredictionModal');
        if (!this.modal) return;

        this.backdrop = document.getElementById('predictionModalBackdrop');
        this.content = document.getElementById('predictionModalContent');
        this.closeBtn = document.getElementById('predictionModalClose');
        this.shareToast = document.getElementById('shareToast');

        // Get CSRF token and auth status
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.isAuthenticated = document.body.dataset.authenticated === 'true';

        // Bind event listeners
        this.bindEvents();

        // Check for prediction in URL on page load
        this.checkUrlForPrediction();
    }

    bindEvents() {
        // Close on backdrop click
        this.backdrop?.addEventListener('click', () => this.close());

        // Close on close button
        this.closeBtn?.addEventListener('click', () => this.close());

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal?.classList.contains('active')) {
                this.close();
            }
        });

        // Handle browser back/forward
        window.addEventListener('popstate', (e) => {
            this.isModalNavigation = true;

            if (e.state?.modal && e.state?.predictionId) {
                this.open(e.state.predictionId, false);
            } else if (this.modal?.classList.contains('active')) {
                this.close(false);
            }

            // Reset flag after a short delay
            setTimeout(() => {
                this.isModalNavigation = false;
            }, 100);
        });

        // Delegate click events for prediction cards
        document.addEventListener('click', (e) => {
            const card = e.target.closest('[data-prediction-id]');
            // Check if it's a clickable prediction card (reddit-card, hot-carousel-card, or mobile-hot-card)
            const isClickableCard = card && (
                card.classList.contains('reddit-card') ||
                card.classList.contains('hot-carousel-card') ||
                card.classList.contains('mobile-hot-card')
            );

            if (isClickableCard) {
                // Don't open modal if clicking on interactive elements (except the card itself if it's a link)
                if (e.target.closest('button, .reddit-vote-btn, .reddit-engagement-share')) {
                    return;
                }
                const predictionId = card.dataset.predictionId;
                if (predictionId) {
                    e.preventDefault();
                    this.open(predictionId);
                }
            }
        });

        // Handle keyboard activation for accessibility
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                const card = document.activeElement;
                if (card?.classList.contains('reddit-card') && card.dataset.predictionId) {
                    e.preventDefault();
                    this.open(card.dataset.predictionId);
                }
            }
        });
    }

    async open(predictionId, updateHistory = true) {
        if (!this.modal || !predictionId) return;

        this.currentPredictionId = predictionId;

        // Show modal with loading state
        this.content.innerHTML = `
            <div class="prediction-modal-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading prediction...</p>
            </div>
        `;

        this.modal.classList.add('active');
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        // Animate in
        requestAnimationFrame(() => {
            this.modal.classList.add('visible');
        });

        // Update URL
        if (updateHistory) {
            const url = new URL(window.location);
            url.searchParams.set('prediction', predictionId);
            history.pushState(
                { modal: true, predictionId: predictionId },
                '',
                url.toString()
            );
        }

        // Load content
        await this.loadPrediction(predictionId);
    }

    close(updateHistory = true) {
        if (!this.modal) return;

        this.modal.classList.remove('visible');

        setTimeout(() => {
            this.modal.classList.remove('active');
            this.modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            this.currentPredictionId = null;

            // Reset content to loading state
            this.content.innerHTML = `
                <div class="prediction-modal-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading prediction...</p>
                </div>
            `;
        }, 300);

        // Update URL
        if (updateHistory && !this.isModalNavigation) {
            const url = new URL(window.location);
            url.searchParams.delete('prediction');
            history.pushState({ modal: false }, '', url.toString());
        }
    }

    async loadPrediction(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/details`);
            const result = await response.json();

            if (result.success) {
                this.renderContent(result.data);
                this.loadComments(predictionId);
            } else {
                this.renderError(result.message || 'Prediction not found');
            }
        } catch (error) {
            console.error('Error loading prediction:', error);
            this.renderError('Failed to load prediction. Please try again.');
        }
    }

    renderContent(data) {
        const isBullish = data.prediction_type.toLowerCase() === 'bullish';
        const profilePicture = data.user.profile_picture
            ? `/images/profile_pictures/${data.user.profile_picture}`
            : '/images/default.png';

        // Format dates
        const predictionDate = data.prediction_date
            ? new Date(data.prediction_date).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            })
            : '';
        const endDate = data.end_date
            ? new Date(data.end_date).toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            })
            : '';

        const isEnded = data.end_date && new Date(data.end_date) <= new Date();

        // Calculate price change percentage
        let priceChangeHtml = '';
        if (data.target_price && data.stock?.current_price) {
            const change = ((data.target_price - data.stock.current_price) / data.stock.current_price * 100);
            const isPositive = change >= 0;
            priceChangeHtml = `
                <span class="modal-price-change modal-price-change--${isPositive ? 'up' : 'down'}">
                    ${isPositive ? '+' : ''}${change.toFixed(1)}%
                </span>
            `;
        }

        this.content.innerHTML = `
            <!-- User header -->
            <div class="modal-header-section">
                <div class="modal-user">
                    <img src="${profilePicture}" alt="${this.escapeHtml(data.user.first_name)}" class="modal-avatar" onerror="this.src='/images/default.png'">
                    <div class="modal-user-info">
                        <span class="modal-username">${this.escapeHtml(data.user.first_name)}</span>
                        <span class="modal-user-meta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" style="color: var(--reddit-warning);">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            ${this.formatNumber(data.user.reputation_score)} reputation
                            ${predictionDate ? `<span style="margin: 0 0.25rem;">&middot;</span>${predictionDate}` : ''}
                        </span>
                    </div>
                </div>
                <span class="modal-hint">ESC to close</span>
            </div>

            <!-- Stock Info Widget -->
            <div class="modal-stock-widget">
                <div class="modal-stock-row">
                    <div class="modal-stock-info">
                        <span class="modal-ticker">$${this.escapeHtml(data.stock.symbol)}</span>
                        ${data.stock.company_name ? `<span class="modal-company">${this.escapeHtml(data.stock.company_name)}</span>` : ''}
                    </div>
                    ${data.stock.current_price ? `
                        <div class="modal-stock-prices">
                            <span class="modal-current-price">Current Price</span>
                            <span class="modal-current-price-value">$${parseFloat(data.stock.current_price).toFixed(2)}</span>
                        </div>
                    ` : ''}
                </div>
                <div class="modal-prediction-row">
                    <span class="modal-badge modal-badge--${isBullish ? 'bullish' : 'bearish'}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            ${isBullish
                                ? '<path d="M12 19V5M5 12l7-7 7 7"/>'
                                : '<path d="M12 5v14M5 12l7 7 7-7"/>'
                            }
                        </svg>
                        ${data.prediction_type}
                    </span>
                    ${data.target_price ? `
                        <div class="modal-target-price">
                            <span class="modal-target-label">Target:</span>
                            <span class="modal-target-value">$${parseFloat(data.target_price).toFixed(2)}</span>
                            ${priceChangeHtml}
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Reasoning -->
            ${data.reasoning ? `
                <div class="modal-reasoning">
                    <div class="modal-reasoning-text">${this.escapeHtml(data.reasoning)}</div>
                </div>
            ` : ''}

            <!-- Meta info -->
            <div class="modal-meta-bar">
                ${endDate ? `
                    <span class="modal-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        ${isEnded ? 'Ended' : 'Ends'} ${endDate}
                    </span>
                ` : ''}
                ${data.accuracy !== null ? `
                    <span class="modal-meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="6"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                        ${parseFloat(data.accuracy).toFixed(1)}% accuracy
                    </span>
                ` : ''}
                <span class="modal-meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${data.is_active
                            ? '<polygon points="5 3 19 12 5 21 5 3"/>'
                            : '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>'
                        }
                    </svg>
                    ${data.is_active ? 'Active' : 'Inactive'}
                </span>
            </div>

            <!-- Voting & Actions -->
            <div class="modal-voting" id="modalVoting">
                <button class="modal-vote-btn modal-vote-up ${data.user_vote === 'upvote' ? 'active' : ''}"
                        data-action="upvote" data-prediction-id="${data.prediction_id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 19V5M5 12l7-7 7 7"/>
                    </svg>
                    <span id="modalUpvotes">${data.upvotes}</span>
                </button>
                <button class="modal-vote-btn modal-vote-down ${data.user_vote === 'downvote' ? 'active' : ''}"
                        data-action="downvote" data-prediction-id="${data.prediction_id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12l7 7 7-7"/>
                    </svg>
                    <span id="modalDownvotes">${data.downvotes}</span>
                </button>
                <button class="modal-share-btn" onclick="sharePrediction(${data.prediction_id}, '${this.escapeHtml(data.stock.symbol)}')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <polyline points="16 6 12 2 8 6"/>
                        <line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                    Share
                </button>
            </div>

            <!-- Comments section -->
            <div class="modal-comments" id="modalComments">
                <div class="modal-comments-header">
                    <h4 class="modal-comments-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Comments <span id="modalCommentsCount">(${data.comments_count})</span>
                    </h4>
                </div>
                ${this.isAuthenticated ? `
                    <div class="modal-comment-form">
                        <input type="text"
                               class="modal-comment-input"
                               id="modalCommentInput"
                               placeholder="Add a comment..."
                               maxlength="500">
                        <button class="modal-comment-submit" id="modalCommentSubmit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                ` : `
                    <div class="modal-login-prompt">
                        <a href="/login">Log in</a> to join the discussion.
                    </div>
                `}
                <div class="modal-comments-list" id="modalCommentsList">
                    <div class="prediction-modal-loading">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
            </div>
        `;

        // Attach event handlers
        this.attachModalHandlers(data.prediction_id);
    }

    renderError(message) {
        this.content.innerHTML = `
            <div class="prediction-modal-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p>${this.escapeHtml(message)}</p>
                <button class="modal-vote-btn" onclick="window.predictionModal.close()">
                    Close
                </button>
            </div>
        `;
    }

    async loadComments(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/comments`);
            const result = await response.json();

            const list = document.getElementById('modalCommentsList');
            if (!list) return;

            if (result.success && result.data && result.data.length > 0) {
                list.innerHTML = result.data.map(c => this.renderComment(c, predictionId)).join('');
            } else {
                list.innerHTML = `
                    <div class="modal-no-comments">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.5; margin-bottom: 0.5rem;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <p>No comments yet. Be the first to share your thoughts!</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            const list = document.getElementById('modalCommentsList');
            if (list) {
                list.innerHTML = '<div class="modal-no-comments">Failed to load comments.</div>';
            }
        }
    }

    renderComment(comment, predictionId, isReply = false, depth = 0) {
        const dateStr = comment.created_at
            ? this.formatRelativeTime(new Date(comment.created_at))
            : '';

        const authorName = comment.user?.first_name || comment.user?.name || 'Anonymous';
        const authorInitial = authorName.charAt(0).toUpperCase();
        const reputation = comment.user?.reputation_score || 0;

        let html = `
            <div class="modal-comment ${isReply ? 'modal-comment--reply' : ''}">
                <div class="modal-comment-header">
                    <div class="modal-comment-avatar">${authorInitial}</div>
                    <span class="modal-comment-author">${this.escapeHtml(authorName)}</span>
                    ${reputation > 0 ? `
                        <span class="modal-comment-reputation">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            ${this.formatNumber(reputation)}
                        </span>
                    ` : ''}
                    <span class="modal-comment-date">${dateStr}</span>
                </div>
                <div class="modal-comment-body">${this.escapeHtml(comment.content)}</div>
                <div class="modal-comment-actions">
                    ${!isReply && this.isAuthenticated ? `
                        <button class="modal-reply-btn" data-comment-id="${comment.comment_id}">Reply</button>
                    ` : ''}
                </div>
        `;

        // Render replies if present (limit depth to 2 levels for clarity)
        if (comment.replies && comment.replies.length > 0 && depth < 2) {
            html += '<div class="modal-replies">';
            comment.replies.forEach(reply => {
                html += this.renderComment(reply, predictionId, true, depth + 1);
            });
            if (comment.replies.length > 3 && depth === 0) {
                html += `<div class="modal-continue-thread">Continue thread →</div>`;
            }
            html += '</div>';
        } else if (comment.replies && comment.replies.length > 0 && depth >= 2) {
            html += `<div class="modal-continue-thread">View ${comment.replies.length} more ${comment.replies.length === 1 ? 'reply' : 'replies'} →</div>`;
        }

        html += '</div>';
        return html;
    }

    formatRelativeTime(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;

        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    attachModalHandlers(predictionId) {
        // Vote handlers
        document.querySelectorAll('#modalVoting .modal-vote-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!this.isAuthenticated) {
                    window.location.href = '/login';
                    return;
                }
                const action = btn.dataset.action;
                if (action) {
                    await this.vote(predictionId, action);
                }
            });
        });

        // Comment submit
        const submitBtn = document.getElementById('modalCommentSubmit');
        const input = document.getElementById('modalCommentInput');

        submitBtn?.addEventListener('click', () => this.submitComment(predictionId, input));
        input?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.submitComment(predictionId, input);
            }
        });

        // Reply buttons (delegated)
        document.getElementById('modalCommentsList')?.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-reply-btn')) {
                const input = document.getElementById('modalCommentInput');
                if (input) {
                    input.focus();
                    input.placeholder = 'Add a reply...';
                }
            }
        });
    }

    async vote(predictionId, action) {
        try {
            const response = await fetch(`/predictions/vote/${predictionId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    vote_type: action,
                    prediction_id: predictionId
                })
            });

            const result = await response.json();

            if (result.success || response.ok) {
                // Refresh vote counts
                await this.refreshVoteCounts(predictionId);

                // Update button states
                const upBtn = document.querySelector('.modal-vote-up');
                const downBtn = document.querySelector('.modal-vote-down');

                if (action === 'upvote') {
                    upBtn?.classList.toggle('active');
                    downBtn?.classList.remove('active');
                } else {
                    downBtn?.classList.toggle('active');
                    upBtn?.classList.remove('active');
                }

                // Also update the feed card if visible
                this.updateFeedCardVotes(predictionId);
            }
        } catch (error) {
            console.error('Vote error:', error);
        }
    }

    async refreshVoteCounts(predictionId) {
        try {
            const response = await fetch(`/predictions/${predictionId}/vote-counts`);
            const counts = await response.json();

            if (counts.success) {
                const upvotesEl = document.getElementById('modalUpvotes');
                const downvotesEl = document.getElementById('modalDownvotes');

                if (upvotesEl) upvotesEl.textContent = counts.upvotes;
                if (downvotesEl) downvotesEl.textContent = counts.downvotes;
            }
        } catch (error) {
            console.error('Error refreshing vote counts:', error);
        }
    }

    updateFeedCardVotes(predictionId) {
        const scoreEl = document.querySelector(`.reddit-vote-score[data-prediction-id="${predictionId}"]`);
        if (scoreEl) {
            // Fetch fresh counts and update
            fetch(`/predictions/${predictionId}/vote-counts`)
                .then(res => res.json())
                .then(counts => {
                    if (counts.success) {
                        const score = counts.upvotes - counts.downvotes;
                        const formatted = score >= 1000 ? (score / 1000).toFixed(1) + 'k' : score;
                        scoreEl.textContent = formatted;
                        scoreEl.classList.add('vote-changed');
                        setTimeout(() => scoreEl.classList.remove('vote-changed'), 300);
                    }
                });
        }
    }

    async submitComment(predictionId, input) {
        const content = input?.value?.trim();
        if (!content) return;

        const submitBtn = document.getElementById('modalCommentSubmit');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const response = await fetch('/comments', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    prediction_id: predictionId,
                    content: content
                })
            });

            const result = await response.json();

            if (result.success || response.ok) {
                input.value = '';
                input.placeholder = 'Add a comment...';

                // Reload comments
                await this.loadComments(predictionId);

                // Update comment count
                const countEl = document.getElementById('modalCommentsCount');
                if (countEl) {
                    const currentCount = parseInt(countEl.textContent.replace(/[()]/g, '')) || 0;
                    countEl.textContent = `(${currentCount + 1})`;
                }
            }
        } catch (error) {
            console.error('Comment error:', error);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    checkUrlForPrediction() {
        const params = new URLSearchParams(window.location.search);
        const predictionId = params.get('prediction');
        if (predictionId) {
            // Small delay to ensure DOM is ready
            setTimeout(() => this.open(predictionId, false), 100);
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatNumber(num) {
        if (!num) return '0';
        return num.toLocaleString();
    }
}

/**
 * Vote on a prediction from the feed card
 * @param {number} predictionId - The prediction ID
 * @param {string} voteType - 'upvote' or 'downvote'
 * @param {HTMLElement} button - The clicked button element
 */
async function votePrediction(predictionId, voteType, button) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const isAuthenticated = document.body.dataset.authenticated === 'true';

    if (!isAuthenticated) {
        window.location.href = '/login';
        return;
    }

    // Find the vote column
    const voteColumn = button.closest('.reddit-card-vote-column');
    const upBtn = voteColumn?.querySelector('.reddit-vote-up');
    const downBtn = voteColumn?.querySelector('.reddit-vote-down');
    const scoreEl = voteColumn?.querySelector('.reddit-vote-score');

    try {
        const response = await fetch(`/predictions/vote/${predictionId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams({
                vote_type: voteType,
                prediction_id: predictionId
            })
        });

        const result = await response.json();

        if (result.success || response.ok) {
            // Toggle button states
            if (voteType === 'upvote') {
                upBtn?.classList.toggle('active');
                downBtn?.classList.remove('active');
            } else {
                downBtn?.classList.toggle('active');
                upBtn?.classList.remove('active');
            }

            // Refresh vote counts
            const countsResponse = await fetch(`/predictions/${predictionId}/vote-counts`);
            const counts = await countsResponse.json();

            if (counts.success && scoreEl) {
                const score = counts.upvotes - counts.downvotes;
                const formatted = score >= 1000 ? (score / 1000).toFixed(1) + 'k' : score;
                scoreEl.textContent = formatted;
                scoreEl.classList.add('vote-changed');
                setTimeout(() => scoreEl.classList.remove('vote-changed'), 300);
            }
        }
    } catch (error) {
        console.error('Vote error:', error);
    }
}

/**
 * Share prediction functionality
 * Called from the share button on prediction cards
 */
function sharePrediction(predictionId, symbol) {
    const url = new URL(window.location);
    url.searchParams.set('prediction', predictionId);

    if (navigator.share) {
        // Use native share on mobile
        navigator.share({
            title: `SoVest - ${symbol} Prediction`,
            url: url.toString()
        }).catch(() => {
            // Fallback to clipboard
            copyToClipboard(url.toString());
        });
    } else {
        // Copy to clipboard on desktop
        copyToClipboard(url.toString());
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showShareToast();
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showShareToast();
    });
}

function showShareToast() {
    const toast = document.getElementById('shareToast');
    if (toast) {
        toast.classList.add('visible');
        setTimeout(() => {
            toast.classList.remove('visible');
        }, 2500);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.predictionModal = new PredictionModal();
});

// Make functions available globally
window.sharePrediction = sharePrediction;
window.votePrediction = votePrediction;

// Export for module usage
export default PredictionModal;
export { sharePrediction, votePrediction };
