<?php
// Force error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Handle the request to capture any errors
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    echo "<h1>Application ran successfully</h1>";
    
} catch (Throwable $e) {
    echo "<h1>Error Caught:</h1>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

// Also show recent logs
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "<h1>Recent Logs:</h1>";
    echo "<pre>" . file_get_contents($logFile) . "</pre>";
}