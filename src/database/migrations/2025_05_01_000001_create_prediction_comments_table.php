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
        Schema::create('prediction_comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->unsignedBigInteger('prediction_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_comment_id')->nullable(); // For replies
            $table->text('content'); // 600 character limit enforced at application level
            $table->timestamps();

            // Foreign keys
            $table->foreign('prediction_id')
                  ->references('prediction_id')
                  ->on('predictions')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('parent_comment_id')
                  ->references('comment_id')
                  ->on('prediction_comments')
                  ->onDelete('cascade');

            // Indexes for performance
            $table->index(['prediction_id', 'created_at']);
            $table->index('parent_comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_comments');
    }
};
