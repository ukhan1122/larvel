<?php
echo "<pre>";
echo "=== SSL CONNECTION TEST ===\n\n";

$host = 'mysql-7fbad99-explain816-0da1.d.aivencloud.com';
$port = '13584';
$db = 'defaultdb';
$user = 'avnadmin';
$pass = getenv('DB_PASSWORD');
$cert = '/var/www/html/storage/certs/ca.pem';

echo "Testing connection with SSL...\n";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::MYSQL_ATTR_SSL_CA => $cert,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ SSL CONNECTION SUCCESSFUL!\n";
    echo "Products found: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "❌ SSL Connection failed: " . $e->getMessage() . "\n\n";
    
    echo "Trying without SSL verification...\n";
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::MYSQL_ATTR_SSL_CA => $cert,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Connection succeeded with SSL verification DISABLED!\n";
        echo "Products found: " . $result['count'] . "\n";
        echo "\n⚠️  This means your certificate is valid but verification is failing.\n";
    } catch (Exception $e2) {
        echo "❌ Still failed: " . $e2->getMessage() . "\n";
    }
}
echo "</pre>";