<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('prediction_votes', function ($table) {
            $table->increments('vote_id');
            $table->integer('prediction_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->enum('vote_type', ['upvote', 'downvote']);
            $table->timestamp('vote_date')->useCurrent();
            
            $table->foreign('prediction_id')
                    ->references('prediction_id')
                    ->on('predictions')
                    ->onDelete('cascade');
                    
            $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                    
            $table->unique(['prediction_id', 'user_id']);
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('prediction_votes');
    }
};