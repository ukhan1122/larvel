<?php 
require __DIR__./vendor/autoload.php; 
$app = require_once __DIR__./bootstrap/app.php; 
try { 
    echo "=== Laravel Bootstrap Debug ===\n"; 
    echo "PHP Version: " . phpversion() . "\n"; 
    echo "Storage Writable: " . (is_writable(storage_path()) ? 'Yes' : 'No') . "\n"; 
    echo "\n=== Database Config ===\n"; 
    echo "DB_HOST: " . env('DB_HOST') . "\n"; 
    echo "DB_DATABASE: " . env('DB_DATABASE') . "\n"; 
    echo "\n=== Testing Database ===\n"; 
    DB::connection()-
    echo "Database: Connected\n"; 
    echo "APP_KEY: " . env('APP_KEY') . "\n"; 
} catch (\Exception $e) { 
    echo "ERROR: " . $e- . "\n"; 
} 
