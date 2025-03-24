<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSearchHistoryTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('search_history')) {
            Capsule::schema()->create('search_history', function ($table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->string('search_query', 100);
                $table->string('search_type', 20)->default('all');
                $table->timestamp('created_at')->useCurrent();
                
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
            });
            
            echo "Search history table created successfully!\n";
        } else {
            echo "Search history table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('search_history')) {
            Capsule::schema()->drop('search_history');
            echo "Search history table dropped successfully!\n";
        } else {
            echo "Search history table does not exist!\n";
        }
    }
}