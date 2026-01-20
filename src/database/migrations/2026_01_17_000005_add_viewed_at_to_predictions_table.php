<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            // Track when the prediction owner last viewed this prediction
            // NULL means never viewed (unread)
            $table->timestamp('viewed_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn('viewed_at');
        });
    }
};
