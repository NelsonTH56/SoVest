<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * V3 algorithm uses decimal scores (0.0 - 1000.0) with dynamic learning rates
     * that produce fractional changes. Change from integer to decimal.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Change to decimal with 2 decimal places for V3 precision
            // Range: 0.00 to 1000.00
            $table->decimal('reputation_score', 7, 2)->default(500.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reputation_score')->default(500)->change();
        });
    }
};
