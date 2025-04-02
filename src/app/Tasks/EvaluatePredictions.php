<?php

namespace App\Tasks;

use App\Services\Interfaces\PredictionScoringServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * EvaluatePredictions
 * 
 * Invocable class for evaluating predictions that replaces the functionality
 * of the evaluate_predictions.php cron job
 */
class EvaluatePredictions
{
    /**
     * The prediction scoring service instance
     * 
     * @var PredictionScoringServiceInterface
     */
    protected $scoringService;
    
    /**
     * Create a new instance of the EvaluatePredictions task
     * 
     * @param PredictionScoringServiceInterface $scoringService
     * @return void
     */
    public function __construct(PredictionScoringServiceInterface $scoringService)
    {
        $this->scoringService = $scoringService;
    }
    
    /**
     * Execute the task
     * 
     * @return void
     */
    public function __invoke()
    {
        // Log task start
        Log::info("Starting scheduled prediction evaluation");
        
        try {
            // Evaluate all active predictions
            $results = $this->scoringService->evaluateActivePredictions();
            
            // Log results
            Log::info("Evaluation results: " . 
                     "Total predictions: {$results['total']}, " . 
                     "Successfully evaluated: {$results['evaluated']}, " . 
                     "Errors: {$results['errors']}");
            
            // Output results if run manually
            if (php_sapi_name() === 'cli') {
                echo "Evaluation results: " . 
                     "Total predictions: {$results['total']}, " . 
                     "Successfully evaluated: {$results['evaluated']}, " . 
                     "Errors: {$results['errors']}\n";
            }
            
            // Log task completion
            Log::info("Prediction evaluation completed");
            
        } catch (\Exception $e) {
            // Log error
            Log::error("Error in prediction evaluation: " . $e->getMessage());
            
            // Output error if run manually
            if (php_sapi_name() === 'cli') {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}