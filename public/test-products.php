<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Boot the app
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

try {
    // Try to fetch products directly
    $products = DB::table('products')->where('approval_status', 'approved')->whereNull('deleted_at')->get();
    
    echo "<h1>Products Test</h1>";
    echo "Found " . $products->count() . " approved products<br>";
    
    if ($products->count() > 0) {
        echo "Sample product: " . $products->first()->title;
    } else {
        echo "No approved products found. Checking all products...<br>";
        $total = DB::table('products')->count();
        echo "Total products in database: " . $total;
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}