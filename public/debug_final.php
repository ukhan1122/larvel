<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "<pre>";
echo "=== FINAL DEBUG ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Laravel Version: " . app()->version() . "\n";
echo "Environment: " . app()->environment() . "\n";
echo "Database Host: " . config('database.connections.mysql.host', 'NOT SET') . "\n";
echo "Database Name: " . config('database.connections.mysql.database', 'NOT SET') . "\n";
echo "APP_KEY exists: " . (config('app.key') ? 'YES' : 'NO') . "\n";
echo "</pre>";
