<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['scholarship', 'major', 'year', ]);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('scholarship',50)->nullable();
            $table->string('major', 100)->nullable();
            $table->string('year', 20)->nullable();
        });
    }
};
