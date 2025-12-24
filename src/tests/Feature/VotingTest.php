<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Prediction;
use App\Models\Stock;
use App\Models\PredictionVote;

class VotingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create a test stock
        $this->stock = Stock::factory()->create([
            'symbol' => 'AAPL',
            'company_name' => 'Apple Inc.',
            'active' => true,
        ]);

        // Create a test prediction
        $this->prediction = Prediction::factory()->create([
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->stock_id,
            'prediction_type' => 'Bullish',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'reasoning' => 'Strong fundamentals and market position',
            'is_active' => 1,
        ]);
    }

    /**
     * Test that an authenticated user can upvote a prediction
     */
    public function test_authenticated_user_can_upvote_prediction()
    {
        $response = $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'upvote',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify vote was recorded in database
        $this->assertDatabaseHas('prediction_votes', [
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
            'vote_type' => 'upvote',
        ]);
    }

    /**
     * Test that an authenticated user can downvote a prediction
     */
    public function test_authenticated_user_can_downvote_prediction()
    {
        $response = $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'downvote',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify vote was recorded in database
        $this->assertDatabaseHas('prediction_votes', [
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
            'vote_type' => 'downvote',
        ]);
    }

    /**
     * Test that a user can toggle their vote (remove it by voting again)
     */
    public function test_user_can_toggle_vote()
    {
        // First vote
        $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'upvote',
            ]);

        // Second vote (toggle off)
        $response = $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'upvote',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify vote was removed from database
        $this->assertDatabaseMissing('prediction_votes', [
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test that a user can change their vote from upvote to downvote
     */
    public function test_user_can_change_vote_type()
    {
        // First vote (upvote)
        $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'upvote',
            ]);

        // Change to downvote
        $response = $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => $this->prediction->prediction_id,
                'vote_type' => 'downvote',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify vote was updated in database
        $this->assertDatabaseHas('prediction_votes', [
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
            'vote_type' => 'downvote',
        ]);

        // Verify no upvote exists
        $this->assertDatabaseMissing('prediction_votes', [
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
            'vote_type' => 'upvote',
        ]);
    }

    /**
     * Test that vote counts are retrieved correctly
     */
    public function test_vote_counts_retrieved_correctly()
    {
        // Create additional users and votes
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Add 2 upvotes and 1 downvote
        PredictionVote::create([
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $this->user->id,
            'vote_type' => 'upvote',
            'vote_date' => now(),
        ]);

        PredictionVote::create([
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $user2->id,
            'vote_type' => 'upvote',
            'vote_date' => now(),
        ]);

        PredictionVote::create([
            'prediction_id' => $this->prediction->prediction_id,
            'user_id' => $user3->id,
            'vote_type' => 'downvote',
            'vote_date' => now(),
        ]);

        // Fetch vote counts
        $response = $this->get("/predictions/{$this->prediction->prediction_id}/vote-counts");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'upvotes' => 2,
            'downvotes' => 1,
            'netvotes' => 1,
        ]);
    }

    /**
     * Test that unauthenticated users cannot vote
     */
    public function test_unauthenticated_user_cannot_vote()
    {
        $response = $this->post(route('prediction.vote'), [
            'prediction_id' => $this->prediction->prediction_id,
            'vote_type' => 'upvote',
        ]);

        // Should redirect to login
        $response->assertStatus(302);
    }

    /**
     * Test that voting on non-existent prediction returns error
     */
    public function test_voting_on_nonexistent_prediction_returns_error()
    {
        $response = $this->actingAs($this->user)
            ->post(route('prediction.vote'), [
                'prediction_id' => 99999,
                'vote_type' => 'upvote',
            ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }
}
