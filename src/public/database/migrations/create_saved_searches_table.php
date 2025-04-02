<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSavedSearchesTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('saved_searches')) {
            Capsule::schema()->create('saved_searches', function ($table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->string('search_query', 100);
                $table->string('search_type', 20)->default('all');
                $table->timestamp('created_at')->useCurrent();
                
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');
                      
                $table->unique(['user_id', 'search_query', 'search_type']);
            });
            
            echo "Saved searches table created successfully!\n";
        } else {
            echo "Saved searches table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('saved_searches')) {
            Capsule::schema()->drop('saved_searches');
            echo "Saved searches table dropped successfully!\n";
        } else {
            echo "Saved searches table does not exist!\n";
        }
    }
}