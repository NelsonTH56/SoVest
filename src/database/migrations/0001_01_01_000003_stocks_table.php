<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::create('stocks', function ($table) {
            $table->increments('stock_id');
            $table->string('symbol', 10)->unique();
            $table->string('company_name', 100);
            $table->string('sector', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            //$table->unique('symbol');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('stocks');
    }
};