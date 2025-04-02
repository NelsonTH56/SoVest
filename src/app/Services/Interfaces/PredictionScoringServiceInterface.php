<?php

namespace App\Services\Interfaces;

/**
 * PredictionScoringServiceInterface
 *
 * This interface defines the contract for prediction scoring and user reputation
 * management in the SoVest application.
 */
interface PredictionScoringServiceInterface
{
    /**
     * Evaluate all active predictions that have reached their end date
     * 
     * @return array Results of evaluations
     */
    public function evaluateActivePredictions();

    /**
     * Evaluate a single prediction
     * 
     * @param array $prediction Prediction data
     * @return bool Success status
     */
    public function evaluatePrediction($prediction);

    /**
     * Update user reputation score
     * 
     * @param int $userId User ID
     * @param float $accuracy Accuracy of prediction
     * @return bool Success status
     */
    public function updateUserReputation($userId, $accuracy);

    /**
     * Get top users by reputation score
     * 
     * @param int $limit Number of users to return
     * @return array Top users
     */
    public function getTopUsers($limit = 10);

    /**
     * Get prediction stats for a user
     * 
     * @param int $userId User ID
     * @return array User prediction stats
     */
    public function getUserPredictionStats($userId);
}