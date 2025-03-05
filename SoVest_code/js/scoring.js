/**
 * SoVest - Prediction Scoring Visualizations
 * 
 * This script handles visualizations and interactive elements for prediction scoring.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize prediction accuracy visualizations
    initAccuracyVisuals();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize reputation progress bars
    initReputationProgress();
});

/**
 * Initialize tooltip functionality
 */
function initTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize accuracy visualizations
 */
function initAccuracyVisuals() {
    // Find all elements with accuracy data
    const accuracyElements = document.querySelectorAll('[data-accuracy]');
    
    accuracyElements.forEach(function(element) {
        const accuracy = parseFloat(element.dataset.accuracy);
        
        // Skip elements without valid accuracy
        if (isNaN(accuracy) || accuracy === null) {
            return;
        }
        
        // Set background gradient based on accuracy value
        if (element.classList.contains('prediction-visual')) {
            const hue = calculateHue(accuracy);
            element.style.background = `linear-gradient(to right, hsl(${hue}, 70%, 40%) ${accuracy}%, #343a40 ${accuracy}%)`;
        }
    });
}

/**
 * Calculate hue for HSL color based on accuracy
 * Red (0) for low accuracy, green (120) for high accuracy
 */
function calculateHue(accuracy) {
    // Map accuracy (0-100) to hue (0-120)
    return Math.min(120, Math.max(0, accuracy * 1.2));
}

/**
 * Initialize reputation progress bars
 */
function initReputationProgress() {
    const repProgressElements = document.querySelectorAll('.reputation-progress');
    
    repProgressElements.forEach(function(element) {
        const reputation = parseInt(element.dataset.reputation || 0);
        const maxRep = parseInt(element.dataset.maxRep || 50);
        
        // Calculate progress percentage (capped at 100%)
        const progressPercent = Math.min(100, Math.max(0, (reputation / maxRep) * 100));
        
        // Get the progress bar element
        const progressBar = element.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progressPercent}%`;
            
            // Set color based on reputation level
            if (reputation >= 30) {
                progressBar.classList.add('bg-success');
            } else if (reputation >= 15) {
                progressBar.classList.add('bg-info');
            } else if (reputation >= 0) {
                progressBar.classList.add('bg-warning');
            } else {
                progressBar.classList.add('bg-danger');
            }
        }
    });
}

/**
 * Update prediction status visualizations when data changes
 */
function updatePredictionStatus(predictionId, status, accuracy) {
    const statusElement = document.querySelector(`.prediction-status[data-prediction-id="${predictionId}"]`);
    
    if (statusElement) {
        // Update status text and class
        statusElement.textContent = status;
        
        // Remove existing status classes
        statusElement.classList.remove('text-success', 'text-warning', 'text-danger', 'text-secondary');
        
        // Add appropriate class based on status
        if (status === 'Accurate' || accuracy >= 70) {
            statusElement.classList.add('text-success');
        } else if (status === 'Inaccurate' || accuracy < 40) {
            statusElement.classList.add('text-danger');
        } else if (status === 'Pending') {
            statusElement.classList.add('text-secondary');
        } else {
            statusElement.classList.add('text-warning');
        }
        
        // Update accuracy display if available
        const accuracyElement = document.querySelector(`.prediction-accuracy[data-prediction-id="${predictionId}"]`);
        if (accuracyElement && accuracy !== null) {
            accuracyElement.textContent = `${Math.round(accuracy)}%`;
            
            // Update visual if exists
            const visualElement = document.querySelector(`.prediction-visual[data-prediction-id="${predictionId}"]`);
            if (visualElement) {
                const hue = calculateHue(accuracy);
                visualElement.style.background = `linear-gradient(to right, hsl(${hue}, 70%, 40%) ${accuracy}%, #343a40 ${accuracy}%)`;
                visualElement.dataset.accuracy = accuracy;
            }
        }
    }
}