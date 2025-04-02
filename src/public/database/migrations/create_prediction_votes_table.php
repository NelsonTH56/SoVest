<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreatePredictionVotesTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('prediction_votes')) {
            Capsule::schema()->create('prediction_votes', function ($table) {
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
            
            echo "Prediction votes table created successfully!\n";
        } else {
            echo "Prediction votes table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('prediction_votes')) {
            Capsule::schema()->drop('prediction_votes');
            echo "Prediction votes table dropped successfully!\n";
        } else {
            echo "Prediction votes table does not exist!\n";
        }
    }
}