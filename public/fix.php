<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Force clear all caches programmatically
$app->make('config')->clear();
$app->make('cache')->clear();

echo "Cache cleared! Try your site now.";
