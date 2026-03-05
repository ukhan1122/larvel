<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::capture());

echo "<h1>Registered API Routes</h1>";
echo "<pre>";

$routeCollection = app('router')->getRoutes();
$apiRoutes = 0;

foreach ($routeCollection as $route) {
    if (strpos($route->uri(), 'api/') === 0) {
        echo $route->uri() . "\n";
        $apiRoutes++;
    }
}

echo "\nTotal API Routes Found: " . $apiRoutes;
echo "</pre>";