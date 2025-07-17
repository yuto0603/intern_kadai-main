<?php
$servername = "db"; // docker-compose.ymlのサービス名
$username = "root";
$password = "root";
$dbname = "equipment_db";

// MySQLi接続テスト
echo "Attempting MySQLi connection...\n";
$conn_mysqli = new mysqli($servername, $username, $password, $dbname, 3306);

if ($conn_mysqli->connect_error) {
    echo "MySQLi Connection failed: " . $conn_mysqli->connect_error . "\n";
} else {
    echo "MySQLi Connection successful!\n";
    $conn_mysqli->close();
}

// PDO_MySQL接続テスト
echo "\nAttempting PDO_MySQL connection...\n";
try {
    $conn_pdo = new PDO("mysql:host=$servername;port=3306;dbname=$dbname", $username, $password);
    $conn_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "PDO_MySQL Connection successful!\n";
    $conn_pdo = null; // 接続を閉じる
} catch (PDOException $e) {
    echo "PDO_MySQL Connection failed: " . $e->getMessage() . "\n";
}

echo "\nTests completed.\n";
?>