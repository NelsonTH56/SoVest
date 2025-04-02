<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('saved_searches', function ($table) {
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
    }

    public function down() : void
    {
        Schema::dropIfExists('saved_searches');
    }
};