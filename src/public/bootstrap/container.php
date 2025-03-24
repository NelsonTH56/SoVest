<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Database\Models\StockService;
use Database\DatabaseConnection;

// Create DI container
$container = new Container();

// Define dependencies
$container->set(DatabaseConnection::class, function () {
    return new DatabaseConnection();
});

$container->set(StockService::class, function ($container) {
    return new StockService($container->get(DatabaseConnection::class));
});

return $container;

?>
