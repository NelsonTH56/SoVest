<?php
    use Illuminate\Database\Capsule\Manager as Capsule;

    require __DIR__ . '/../vendor/autoload.php'; // Load Composer dependencies

    require_once __DIR__ . '/../includes/db_config.php';

    $capsule = new Capsule;

    $capsule->addConnection([
        'driver'    => 'mysql',  // Change to 'pgsql' for PostgreSQL
        'host'      => DB_SERVER,
        'database'  => DB_NAME,
        'username'  => DB_USERNAME,
        'password'  => DB_PASSWORD,
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);

    $capsule->setAsGlobal();  // Makes database globally accessible
    $capsule->bootEloquent(); // Boots Eloquent ORM

    return $capsule;
?>