<?php
$databaseUrl = getenv('DATABASE_URL') ?: '';

if ($databaseUrl !== '') {
    $databaseParts = parse_url($databaseUrl);
    $host = $databaseParts['host'] ?? '127.0.0.1';
    $user = $databaseParts['user'] ?? 'root';
    $pass = $databaseParts['pass'] ?? '';
    $db = isset($databaseParts['path']) ? ltrim($databaseParts['path'], '/') : 'blog';
    $port = (int) ($databaseParts['port'] ?? 3306);
} else {
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: 'root';
    $db = getenv('DB_NAME') ?: 'blog';
    $port = (int) (getenv('DB_PORT') ?: 8889);
}

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
