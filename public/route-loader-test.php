<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "<pre>";
echo "=== CHECKING ROUTE FILE LOADING ===\n\n";

$files = [
    'routes/api.php' => file_exists('../routes/api.php'),
    'routes/api-group/listing/products.php' => file_exists('../routes/api-group/listing/products.php'),
];

foreach($files as $file => $exists) {
    echo $file . ": " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    if($exists) {
        echo "Size: " . filesize('../' . $file) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime('../' . $file)) . "\n\n";
    }
}

echo "\n=== TESTING REQUIRE ===\n";
try {
    require_once '../routes/api.php';
    echo "✅ routes/api.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load routes/api.php: " . $e->getMessage() . "\n";
}