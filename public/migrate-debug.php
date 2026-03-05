<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "<h1>Running Migrations with Debug</h1>";

try {
    // Check current migration status
    $hasMigrationsTable = Schema::hasTable('migrations');
    echo "Migrations table exists: " . ($hasMigrationsTable ? 'YES' : 'NO') . "<br>";
    
    if($hasMigrationsTable) {
        $count = DB::table('migrations')->count();
        echo "Migration records: $count<br>";
    }
    
    echo "<h2>Attempting to run migrations...</h2>";
    
    // Run migrations and capture output
    Artisan::call('migrate', ['--force' => true, '--verbose' => true]);
    echo "<pre>" . Artisan::output() . "</pre>";
    
    echo "<h2 style='color:green'>✅ Migrations completed!</h2>";
    
} catch (\Exception $e) {
    echo "<h2 style='color:red'>❌ Migration Failed:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}