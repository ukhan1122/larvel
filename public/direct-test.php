<?php
// Direct database query - no Laravel routing
$host = 'mysql-7fbad99-explain816-0da1.d.aivencloud.com';
$port = '13584';
$db = 'defaultdb';
$user = 'avnadmin';
$pass = getenv('DB_PASSWORD'); // Get password from environment

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/cert.pem',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE approval_status = 'approved' AND deleted_at IS NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'approved_products' => $result['count'],
        'message' => 'Direct PHP file works!'
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}