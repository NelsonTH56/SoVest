<?php
/**
 * Prediction Score Display Component
 * 
 * This component handles rendering prediction accuracy and status
 */

/**
 * Get CSS class for accuracy display
 * 
 * @param float|null $accuracy The accuracy value or null if pending
 * @return string CSS class name
 */
function getAccuracyClass($accuracy) {
    if ($accuracy === null) {
        return 'text-secondary';  // Pending
    } else if ($accuracy >= 70) {
        return 'text-success';    // Good
    } else if ($accuracy >= 40) {
        return 'text-warning';    // Average
    } else {
        return 'text-danger';     // Poor
    }
}

/**
 * Get appropriate icon for prediction based on accuracy
 * 
 * @param float|null $accuracy The accuracy value or null if pending
 * @return string HTML for icon
 */
function getAccuracyIcon($accuracy) {
    if ($accuracy === null) {
        return '<i class="bi bi-hourglass"></i>';  // Pending
    } else if ($accuracy >= 70) {
        return '<i class="bi bi-check-circle-fill"></i>';  // Good
    } else if ($accuracy >= 40) {
        return '<i class="bi bi-exclamation-circle-fill"></i>';  // Average
    } else {
        return '<i class="bi bi-x-circle-fill"></i>';  // Poor
    }
}

/**
 * Format accuracy for display
 * 
 * @param float|null $accuracy The accuracy value or null if pending
 * @return string Formatted accuracy text
 */
function formatAccuracy($accuracy) {
    if ($accuracy === null) {
        return 'Pending';
    }
    return number_format($accuracy, 0) . '%';
}

/**
 * Render a prediction score badge
 * 
 * @param float|null $accuracy The accuracy value or null if pending
 * @return string HTML for badge
 */
function renderPredictionBadge($accuracy) {
    $class = getAccuracyClass($accuracy);
    $icon = getAccuracyIcon($accuracy);
    $text = formatAccuracy($accuracy);
    
    return "<span class=\"badge $class\">$icon $text</span>";
}

/**
 * Render a prediction card with score
 * 
 * @param array $prediction Prediction data
 * @return string HTML for prediction card
 */
function renderPredictionCard($prediction) {
    $symbol = htmlspecialchars($prediction['symbol']);
    $predictionType = htmlspecialchars($prediction['prediction']);
    $accuracy = $prediction['accuracy'];
    $badgeHtml = renderPredictionBadge($accuracy);
    $predictionClass = $predictionType == 'Bullish' ? 'text-success' : 'text-danger';
    
    $targetPrice = isset($prediction['target_price']) ? 
        '<p>Target: $' . number_format($prediction['target_price'], 2) . '</p>' : '';
    
    $html = <<<HTML
<div class="prediction-card mb-3">
    <div class="card bg-dark text-light">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">$symbol</h5>
            $badgeHtml
        </div>
        <div class="card-body">
            <p>Prediction: <span class="$predictionClass">$predictionType</span></p>
            $targetPrice
        </div>
    </div>
</div>
HTML;
    
    return $html;
}

/**
 * Render a user reputation score
 * 
 * @param int $reputation User reputation score
 * @param float|null $avgAccuracy Average prediction accuracy or null
 * @return string HTML for reputation display
 */
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
?>