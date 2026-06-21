<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$routes = $app->make('router')->getRoutes();
foreach ($routes as $route) {
    echo $route->methods()[0] . ' ' . $route->uri() . "\n";
}
echo "\nTotal routes: " . count($routes) . "\n";
