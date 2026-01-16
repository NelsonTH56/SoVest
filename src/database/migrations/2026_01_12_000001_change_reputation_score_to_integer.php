<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Change reputation_score from decimal to integer
     */
    public function up(): void
    {
        // First, convert all existing decimal values to integers
        DB::statement('UPDATE users SET reputation_score = CAST(reputation_score AS SIGNED)');

        // Then change the column type to integer
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reputation_score')->default(500)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('reputation_score', 10, 2)->default(500.00)->change();
        });
    }
};
