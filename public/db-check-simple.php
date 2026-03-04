<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting...\n";

require __DIR__.'/../vendor/autoload.php';
echo "Autoload loaded...\n";

$app = require_once __DIR__.'/../bootstrap/app.php';
echo "App loaded...\n";

use Illuminate\Support\Facades\DB;

try {
    echo "Testing database...\n";
    $pdo = DB::connection()->getPdo();
    echo "✅ Connected!\n";
    
    $tables = DB::select('SHOW TABLES');
    echo "Tables found: " . count($tables) . "\n";
    
    foreach($tables as $table) {
        $tableName = current($table);
        echo "  - " . $tableName . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}