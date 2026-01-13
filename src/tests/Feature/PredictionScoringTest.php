<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Prediction;
use App\Models\Stock;
use App\Models\StockPrice;
use App\Services\PredictionScoringService;

class PredictionScoringTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $scoringService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scoringService = app(PredictionScoringService::class);

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'reputation_score' => 0,
        ]);

        // Create a test stock
        $this->stock = Stock::factory()->create([
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'active' => true,
        ]);

        // Create initial stock price
        $this->initialPrice = StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDays(30)->format('Y-m-d'),
            'open_price' => 150.00,
            'close_price' => 150.00,
            'high_price' => 152.00,
            'low_price' => 148.00,
            'volume' => 1000000,
        ]);
    }

    /**
     * Test that a bullish prediction is scored correctly when price increases
     */
    public function test_bullish_prediction_scored_correctly_on_price_increase()
    {
        // Create a bullish prediction
        $prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bullish',
            'prediction_date' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'reasoning' => 'Testing bullish prediction',
            'is_active' => 1,
            'accuracy' => null,
        ]);

        // Create end price (10% increase)
        $endPrice = StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDay()->format('Y-m-d'),
            'open_price' => 165.00,
            'close_price' => 165.00,
            'high_price' => 166.00,
            'low_price' => 164.00,
            'volume' => 1000000,
        ]);

        // Evaluate the prediction
        $result = $this->scoringService->evaluatePrediction($prediction->prediction_id);

        // Refresh prediction to get updated values
        $prediction->refresh();

        // Assert prediction was marked as inactive
        $this->assertEquals(0, $prediction->is_active);

        // Assert accuracy is high (should be close to 100%)
        $this->assertGreaterThan(90, $prediction->accuracy);

        // Assert user reputation increased
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->reputation_score);
    }

    /**
     * Test that a bullish prediction is scored correctly when price decreases
     */
    public function test_bullish_prediction_scored_correctly_on_price_decrease()
    {
        // Create a bullish prediction
        $prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bullish',
            'prediction_date' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'reasoning' => 'Testing bullish prediction failure',
            'is_active' => 1,
            'accuracy' => null,
        ]);

        // Create end price (10% decrease)
        $endPrice = StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDay()->format('Y-m-d'),
            'open_price' => 135.00,
            'close_price' => 135.00,
            'high_price' => 136.00,
            'low_price' => 134.00,
            'volume' => 1000000,
        ]);

        // Evaluate the prediction
        $result = $this->scoringService->evaluatePrediction($prediction->prediction_id);

        // Refresh prediction to get updated values
        $prediction->refresh();

        // Assert prediction was marked as inactive
        $this->assertEquals(0, $prediction->is_active);

        // Assert accuracy is low (prediction was wrong)
        $this->assertLessThan(50, $prediction->accuracy);

        // Assert user reputation may have decreased or stayed same
        $this->user->refresh();
        $this->assertLessThanOrEqual(0, $this->user->reputation_score);
    }

    /**
     * Test that a bearish prediction is scored correctly when price decreases
     */
    public function test_bearish_prediction_scored_correctly_on_price_decrease()
    {
        // Create a bearish prediction
        $prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bearish',
            'prediction_date' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'reasoning' => 'Testing bearish prediction',
            'is_active' => 1,
            'accuracy' => null,
        ]);

        // Create end price (10% decrease)
        $endPrice = StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDay()->format('Y-m-d'),
            'open_price' => 135.00,
            'close_price' => 135.00,
            'high_price' => 136.00,
            'low_price' => 134.00,
            'volume' => 1000000,
        ]);

        // Evaluate the prediction
        $result = $this->scoringService->evaluatePrediction($prediction->prediction_id);

        // Refresh prediction to get updated values
        $prediction->refresh();

        // Assert prediction was marked as inactive
        $this->assertEquals(0, $prediction->is_active);

        // Assert accuracy is high (prediction was correct)
        $this->assertGreaterThan(90, $prediction->accuracy);

        // Assert user reputation increased
        $this->user->refresh();
        $this->assertGreaterThan(0, $this->user->reputation_score);
    }

    /**
     * Test that a bearish prediction is scored correctly when price increases
     */
    public function test_bearish_prediction_scored_correctly_on_price_increase()
    {
        // Create a bearish prediction
        $prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bearish',
            'prediction_date' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'reasoning' => 'Testing bearish prediction failure',
            'is_active' => 1,
            'accuracy' => null,
        ]);

        // Create end price (10% increase)
        $endPrice = StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDay()->format('Y-m-d'),
            'open_price' => 165.00,
            'close_price' => 165.00,
            'high_price' => 166.00,
            'low_price' => 164.00,
            'volume' => 1000000,
        ]);

        // Evaluate the prediction
        $result = $this->scoringService->evaluatePrediction($prediction->prediction_id);

        // Refresh prediction to get updated values
        $prediction->refresh();

        // Assert prediction was marked as inactive
        $this->assertEquals(0, $prediction->is_active);

        // Assert accuracy is low (prediction was wrong)
        $this->assertLessThan(50, $prediction->accuracy);
    }

    /**
     * Test reputation scoring for highly accurate predictions (90%+)
     */
    public function test_reputation_increase_for_highly_accurate_predictions()
    {
        $initialReputation = $this->user->reputation_score;

        // Create a prediction
        $prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bullish',
            'prediction_date' => now()->subDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->subDay()->format('Y-m-d'),
            'reasoning' => 'Testing reputation increase',
            'is_active' => 1,
            'accuracy' => null,
        ]);

        // Create end price with significant increase
        StockPrice::create([
            'stock_id' => $this->stock->stock_id,
            'price_date' => now()->subDay()->format('Y-m-d'),
            'open_price' => 165.00,
            'close_price' => 165.00,
            'high_price' => 166.00,
            'low_price' => 164.00,
            'volume' => 1000000,
        ]);

        // Evaluate the prediction
        $this->scoringService->evaluatePrediction($prediction->prediction_id);

        // Refresh user
        $this->user->refresh();

        // Assert reputation increased by 10 points (90%+ accuracy)
        $this->assertEquals($initialReputation + 10, $this->user->reputation_score);
    }

    /**
     * Test getUserPredictionStats method
     */
    public function test_get_user_prediction_stats()
    {
        // Create multiple predictions with different accuracies
        Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'accuracy' => 95,
            'is_active' => 0,
        ]);

        Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'accuracy' => 80,
            'is_active' => 0,
        ]);

        Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'accuracy' => 20,
            'is_active' => 0,
        ]);

        Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'accuracy' => null,
            'is_active' => 1,
        ]);

        // Get stats
        $stats = $this->scoringService->getUserPredictionStats($this->user->id);

        // Assert stats are correct
        $this->assertEquals(4, $stats['total_predictions']);
        $this->assertEquals(2, $stats['accurate_predictions']); // 95% and 80%
        $this->assertEquals(1, $stats['inaccurate_predictions']); // 20%
        $this->assertEquals(1, $stats['pending_predictions']);
        $this->assertEquals(65, $stats['avg_accuracy']); // (95 + 80 + 20) / 3
    }
}
