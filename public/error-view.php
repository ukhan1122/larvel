<?php
// Force error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "<h1>Laravel Error Diagnostics</h1>";

try {
    // Try to access the database through Laravel
    $users = DB::table('migrations')->get();
    echo "✅ Migrations table exists!";
} catch (\Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "</pre>";
    
    echo "<h2>Stack Trace:</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Check what tables exist
echo "<h2>Current Tables:</h2>";
try {
    $tables = DB::select('SHOW TABLES');
    echo "<ul>";
    foreach($tables as $table) {
        $name = current($table);
        echo "<li>$name</li>";
    }
    echo "</ul>";
} catch (\Exception $e) {
    echo "Can't list tables: " . $e->getMessage();
}