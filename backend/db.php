<?php
$host = "127.0.0.1"; // use IP instead of localhost
$user = "root";
$pass = "root";
$db   = "blog";
$port = 8889;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!function_exists('create_pdo_connection')) {
    function create_pdo_connection(): PDO
    {
        global $host, $user, $pass, $db, $port;

        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user,
            $pass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
?>
