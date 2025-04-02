<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('predictions', function ($table) {
            $table->increments('prediction_id');
            $table->integer('user_id')->unsigned();
            $table->integer('stock_id')->unsigned();
            $table->enum('prediction_type', ['Bullish', 'Bearish']);
            $table->decimal('target_price', 10, 2)->nullable();
            $table->timestamp('prediction_date')->useCurrent();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('accuracy', 5, 2)->nullable();
            $table->text('reasoning')->nullable();
            
            $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                    
            $table->foreign('stock_id')
                    ->references('stock_id')
                    ->on('stocks')
                    ->onDelete('cascade');
        });   
    }

    public function down() : void
    {
        Schema::dropIfExists('predictions');
    }
};