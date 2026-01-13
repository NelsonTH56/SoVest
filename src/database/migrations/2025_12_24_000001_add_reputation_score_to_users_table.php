<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add reputation_score field to users table with new scoring algorithm default
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add reputation_score field if it doesn't exist
            // Default to 500 (middle of 0-1000 range) per new algorithm
            if (!Schema::hasColumn('users', 'reputation_score')) {
                $table->decimal('reputation_score', 10, 2)->default(500.00)->after('bio');
            } else {
                // If column exists, just update default value
                $table->decimal('reputation_score', 10, 2)->default(500.00)->change();
            }
        });

        // Update existing users who have NULL or 0 reputation_score to the new default
        DB::table('users')
            ->whereNull('reputation_score')
            ->orWhere('reputation_score', 0)
            ->update(['reputation_score' => 500.00]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optionally drop the column (commented out to preserve data)
            // $table->dropColumn('reputation_score');

            // Or restore old default
            $table->decimal('reputation_score', 10, 2)->default(0)->change();
        });
    }
};
