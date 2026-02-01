<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            // V3 Algorithm Fields
            $table->decimal('benchmark_performance', 10, 6)->nullable()->after('accuracy')->comment('Benchmark performance for alpha calculation');
            $table->tinyInteger('thesis_rating')->default(3)->after('benchmark_performance')->comment('Thesis quality rating (1-5 stars)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['benchmark_performance', 'thesis_rating']);
        });
    }
};
