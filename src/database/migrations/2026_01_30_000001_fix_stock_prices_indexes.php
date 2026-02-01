<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix stock_prices table indexes.
     *
     * The original migration incorrectly used individual unique constraints on
     * stock_id and price_date, which prevents storing multiple prices per stock.
     * This migration fixes it by using a composite unique constraint.
     */
    public function up(): void
    {
        Schema::table('stock_prices', function (Blueprint $table) {
            // First, drop the foreign key constraint (required before dropping the unique index)
            $table->dropForeign(['stock_id']);
        });

        Schema::table('stock_prices', function (Blueprint $table) {
            // Now drop the incorrect individual unique constraints
            $table->dropUnique(['stock_id']);
            $table->dropUnique(['price_date']);

            // Add the correct composite unique constraint (one price per stock per date)
            $table->unique(['stock_id', 'price_date'], 'stock_prices_stock_date_unique');

            // Add index for efficient date-range queries (used by getPriceHistory)
            $table->index('price_date', 'stock_prices_date_index');

            // Re-add the foreign key constraint
            $table->foreign('stock_id')->references('stock_id')->on('stocks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('stock_prices', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['stock_id']);

            // Remove the new indexes
            $table->dropUnique('stock_prices_stock_date_unique');
            $table->dropIndex('stock_prices_date_index');

            // Restore original (broken) constraints for rollback
            $table->unique('stock_id');
            $table->unique('price_date');

            // Re-add foreign key
            $table->foreign('stock_id')->references('stock_id')->on('stocks')->onDelete('cascade');
        });
    }
};
