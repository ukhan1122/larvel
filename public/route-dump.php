<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$routeCollection = $app->make('router')->getRoutes();
echo "<pre>";
foreach($routeCollection as $route) {
    if(strpos($route->uri(), 'api/') === 0) {
        echo $route->uri() . "\n";
    }
}