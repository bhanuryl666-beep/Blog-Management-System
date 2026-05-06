<?php
require_once 'db.php';

function auth_project_base_path(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($scriptName === '') {
        return '';
    }

    $projectPath = dirname(dirname($scriptName));
    if ($projectPath === DIRECTORY_SEPARATOR || $projectPath === '\\' || $projectPath === '.') {
        return '';
    }

    return rtrim(str_replace('\\', '/', $projectPath), '/');
}

function auth_table_exists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);

    return (bool) $stmt->fetchColumn();
}

function auth_column_exists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
    $stmt->execute([$columnName]);

    return (bool) $stmt->fetchColumn();
}

function auth_index_exists(PDO $pdo, string $tableName, string $indexName): bool
{
    $stmt = $pdo->query("SHOW INDEX FROM `$tableName`");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (($row['Key_name'] ?? '') === $indexName) {
            return true;
        }
    }

    return false;
}

function ensure_auth_schema(PDO $pdo): void
{
    if (!auth_table_exists($pdo, 'users')) {
        throw new RuntimeException('The users table does not exist.');
    }

    $columnsToAdd = [
        'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL AFTER username",
        'email_verified_at' => "ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER role",
        'verification_token' => "ALTER TABLE users ADD COLUMN verification_token VARCHAR(128) NULL AFTER email_verified_at",
        'created_at' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER verification_token",
    ];

    foreach ($columnsToAdd as $column => $sql) {
        if (!auth_column_exists($pdo, 'users', $column)) {
            $pdo->exec($sql);
        }
    }

    if (!auth_index_exists($pdo, 'users', 'users_email_unique')) {
        $pdo->exec("ALTER TABLE users ADD UNIQUE KEY users_email_unique (email)");
    }

    $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
}

function auth_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001';
    $basePath = auth_project_base_path();

    return $scheme . '://' . $host . $basePath;
}

function auth_generate_token(): string
{
    return bin2hex(random_bytes(32));
}

function auth_set_login_session(array $user): void
{
    $_SESSION['user'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?? 'user';
    $_SESSION['login_notice'] = '✅ ' . ucfirst($_SESSION['role']) . ' logged in successfully.';
}

function auth_redirect_for_role(string $role): void
{
    if ($role === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: ../frontend/index.php');
    }
    exit();
}

function auth_verification_link(string $token): string
{
    return auth_base_url() . '/backend/verify.php?token=' . urlencode($token);
}

function auth_send_verification_email(string $email, string $username, string $verificationLink): bool
{
    $subject = 'Verify your account';
    $message = "Hello $username,\n\nPlease verify your account by opening this link:\n$verificationLink\n\nIf you did not create this account, you can ignore this message.";
    $headers = "From: no-reply@modernblog.local\r\n";

    return function_exists('mail') ? @mail($email, $subject, $message, $headers) : false;
}
