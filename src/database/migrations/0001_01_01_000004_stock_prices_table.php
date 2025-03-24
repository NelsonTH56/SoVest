<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('stock_prices', function ($table) {
            $table->increments('price_id');
            $table->integer('stock_id')->unsigned()->unique();;
            $table->date('price_date')->unique();;
            $table->decimal('open_price', 10, 2);
            $table->decimal('close_price', 10, 2);
            $table->decimal('high_price', 10, 2);
            $table->decimal('low_price', 10, 2);
            $table->bigInteger('volume')->nullable();
            
            $table->foreign('stock_id')
                    ->references('stock_id')
                    ->on('stocks')
                    ->onDelete('cascade');
                    
            //$table->unique(['stock_id', 'price_date']);
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('stock_prices');
    }
};