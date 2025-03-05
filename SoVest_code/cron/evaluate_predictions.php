<?php
/**
 * SoVest Prediction Evaluation Cron Job
 * 
 * This script should be run daily via cron to evaluate predictions
 * Example cron entry (daily at midnight):
 * 0 0 * * * php /path/to/SoVest/SoVest_code/cron/evaluate_predictions.php
 */

// Include the PredictionScoringService
require_once __DIR__ . '/../services/PredictionScoringService.php';

// Set error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/prediction_evaluation.log');

// Function to log messages
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logDir . '/prediction_evaluation.log', $logEntry, FILE_APPEND);
    
    // Output to console if run from CLI
    if (php_sapi_name() === 'cli') {
        echo $logEntry;
    }
}

// Log script start
logMessage("Starting scheduled prediction evaluation");

try {
    // Create service instance
    $scoringService = new PredictionScoringService();
    
    // Evaluate all active predictions
    $results = $scoringService->evaluateActivePredictions();
    
    // Log results
    logMessage("Evaluation results: " . 
               "Total predictions: {$results['total']}, " . 
               "Successfully evaluated: {$results['evaluated']}, " . 
               "Errors: {$results['errors']}");
    
} catch (Exception $e) {
    // Log error
    logMessage("Error in prediction evaluation: " . $e->getMessage());
}

// Log script end
logMessage("Prediction evaluation completed");
?>