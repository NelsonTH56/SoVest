<?php
// Include Laravel's autoload file if this is outside your usual Laravel controller flow
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
$kernel = $app->make(Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Get the CSRF token from the Laravel session
$token = csrf_token();
?>