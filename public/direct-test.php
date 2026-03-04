<?php
$host = 'mysql-7fbad99-explain816-0da1.d.aivencloud.com';
$port = '13584';
$db = 'defaultdb';
$user = 'avnadmin';
$pass = getenv('DB_PASSWORD');

try {
    // REMOVED SSL FOR TESTING
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $result['count'],
        'message' => 'Connected without SSL!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}