{{--
    Global Prediction Detail Modal
    Provides site-wide modal functionality for viewing prediction details.
    Included once in the main layout, triggered by clicking prediction cards.

    Features:
    - Stock info widget with current price and target
    - Threaded comments with depth limit
    - X-style horizontal voting
    - URL state management for deep linking
--}}

<!-- Global Prediction Detail Modal -->
<div class="prediction-detail-modal" id="globalPredictionModal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Prediction Details">
    <div class="prediction-modal-backdrop" id="predictionModalBackdrop"></div>
    <div class="prediction-modal-container">
        <button class="prediction-modal-close" id="predictionModalClose" aria-label="Close modal">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="prediction-modal-content" id="predictionModalContent">
            <!-- Loading state (shown initially) -->
            <div class="prediction-modal-loading">
                <svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <p>Loading prediction...</p>
            </div>
        </div>
    </div>
</div>

<!-- Share Toast Notification -->
<div class="share-toast" id="shareToast">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    Link copied to clipboard!
</div>

<style>
/* Spinner animation for loading state */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
