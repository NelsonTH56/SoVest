<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateStocksTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('stocks')) {
            Capsule::schema()->create('stocks', function ($table) {
                $table->increments('stock_id');
                $table->string('symbol', 10);
                $table->string('company_name', 100);
                $table->string('sector', 50)->nullable();
                $table->timestamp('created_at')->useCurrent();
                
                $table->unique('symbol');
            });
            
            echo "Stocks table created successfully!\n";
        } else {
            echo "Stocks table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('stocks')) {
            Capsule::schema()->drop('stocks');
            echo "Stocks table dropped successfully!\n";
        } else {
            echo "Stocks table does not exist!\n";
        }
    }
}