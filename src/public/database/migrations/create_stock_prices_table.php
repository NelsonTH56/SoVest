<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateStockPricesTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('stock_prices')) {
            Capsule::schema()->create('stock_prices', function ($table) {
                $table->increments('price_id');
                $table->integer('stock_id')->unsigned();
                $table->date('price_date');
                $table->decimal('open_price', 10, 2);
                $table->decimal('close_price', 10, 2);
                $table->decimal('high_price', 10, 2);
                $table->decimal('low_price', 10, 2);
                $table->bigInteger('volume')->nullable();
                
                $table->foreign('stock_id')
                      ->references('stock_id')
                      ->on('stocks')
                      ->onDelete('cascade');
                      
                $table->unique(['stock_id', 'price_date']);
            });
            
            echo "Stock prices table created successfully!\n";
        } else {
            echo "Stock prices table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('stock_prices')) {
            Capsule::schema()->drop('stock_prices');
            echo "Stock prices table dropped successfully!\n";
        } else {
            echo "Stock prices table does not exist!\n";
        }
    }
}