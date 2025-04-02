<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateUsersTable
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('users')) {
            Capsule::schema()->create('users', function ($table) {
                $table->increments('id');
                $table->string('email', 255)->unique();
                $table->string('password', 255);
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('major', 100)->nullable();
                $table->string('year', 20)->nullable();
                $table->string('scholarship', 50)->nullable();
                $table->integer('reputation_score')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
            
            echo "Users table created successfully!\n";
        } else {
            echo "Users table already exists!\n";
        }
    }

    public function down()
    {
        if (Capsule::schema()->hasTable('users')) {
            Capsule::schema()->drop('users');
            echo "Users table dropped successfully!\n";
        } else {
            echo "Users table does not exist!\n";
        }
    }
}