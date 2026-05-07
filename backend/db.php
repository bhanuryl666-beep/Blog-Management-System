<?php
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = (int) (getenv('MYSQLPORT') ?: '3306');
$db = getenv('MYSQLDATABASE') ?: 'railway';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';

function show_database_connection_error(string $message): void
{
    http_response_code(500);
    echo '<h1>Database connection error</h1>';
    echo '<p>Please check the MySQL environment variables in Railway.</p>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    exit();
}

if (str_starts_with($host, 'your_') || str_starts_with($user, 'your_')) {
    show_database_connection_error('MYSQLHOST and MYSQLUSER must be replaced with real MySQL credentials.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    show_database_connection_error($conn->connect_error);
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
