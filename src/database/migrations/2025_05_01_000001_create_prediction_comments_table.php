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
            $table->id('comment_id'); // bigInteger unsigned auto-increment
            $table->unsignedInteger('prediction_id'); // Match predictions.prediction_id type (increments = unsignedInteger)
            $table->unsignedInteger('user_id'); // Match users.id type (increments = unsignedInteger)
            $table->unsignedBigInteger('parent_comment_id')->nullable(); // For replies - matches comment_id (id() = bigInteger)
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
