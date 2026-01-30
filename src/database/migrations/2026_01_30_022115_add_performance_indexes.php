<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // Predictions table indexes
        Schema::table('predictions', function (Blueprint $table) {
            $table->index('user_id', 'idx_predictions_user_id');
            $table->index('stock_id', 'idx_predictions_stock_id');
            $table->index('is_active', 'idx_predictions_is_active');
            $table->index(['is_active', 'end_date'], 'idx_predictions_active_end_date');
            $table->index(['user_id', 'is_active'], 'idx_predictions_user_active');
        });

        // Prediction votes table indexes
        Schema::table('prediction_votes', function (Blueprint $table) {
            $table->index(['prediction_id', 'vote_type'], 'idx_votes_prediction_type');
            $table->index('user_id', 'idx_votes_user_id');
        });

        // Stock prices table indexes
        Schema::table('stock_prices', function (Blueprint $table) {
            $table->index(['stock_id', 'price_date'], 'idx_stock_prices_stock_date');
        });

        // Users table index for leaderboard
        Schema::table('users', function (Blueprint $table) {
            $table->index('reputation_score', 'idx_users_reputation');
        });

        // Search history table index
        Schema::table('search_history', function (Blueprint $table) {
            $table->index('user_id', 'idx_search_history_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex('idx_predictions_user_id');
            $table->dropIndex('idx_predictions_stock_id');
            $table->dropIndex('idx_predictions_is_active');
            $table->dropIndex('idx_predictions_active_end_date');
            $table->dropIndex('idx_predictions_user_active');
        });

        Schema::table('prediction_votes', function (Blueprint $table) {
            $table->dropIndex('idx_votes_prediction_type');
            $table->dropIndex('idx_votes_user_id');
        });

        Schema::table('stock_prices', function (Blueprint $table) {
            $table->dropIndex('idx_stock_prices_stock_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_reputation');
        });

        Schema::table('search_history', function (Blueprint $table) {
            $table->dropIndex('idx_search_history_user_id');
        });
    }
};
