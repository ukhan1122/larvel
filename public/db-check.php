<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "<pre>";
echo "=== DATABASE CHECK ===\n\n";

try {
    // Test connection
    DB::connection()->getPdo();
    echo "✅ Database connected\n\n";
    
    // Get all tables
    $tables = DB::select('SHOW TABLES');
    echo "Tables in database (" . count($tables) . "):\n";
    
    $hasProducts = false;
    foreach($tables as $table) {
        $tableName = current($table);
        echo "  - " . $tableName;
        
        // Count records in each table
        $count = DB::table($tableName)->count();
        echo " (" . $count . " records)\n";
        
        if($tableName == 'products') {
            $hasProducts = true;
        }
    }
    
    if(!$hasProducts) {
        echo "\n❌ PRODUCTS TABLE IS MISSING!\n";
        echo "Migrations did NOT run properly.\n";
    } else {
        echo "\n✅ Products table exists with records\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
echo "</pre>";