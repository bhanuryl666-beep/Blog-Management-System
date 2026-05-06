<?php
session_start();
require_once 'auth.php';

$message = '';
$isSuccess = false;

try {
    $pdo = create_pdo_connection();
    ensure_auth_schema($pdo);
} catch (Throwable $e) {
    die('Connection failed: ' . $e->getMessage());
}

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    $message = '❌ Verification token is missing.';
} else {
    $stmt = $pdo->prepare('SELECT id, username FROM users WHERE verification_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = '❌ Invalid or expired verification link.';
    } else {
        $update = $pdo->prepare('UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?');
        $update->execute([$user['id']]);
        $message = '✅ Email verified successfully. You can now log in as User.';
        $isSuccess = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link rel="stylesheet" href="../frontend/style.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-card auth-card">
        <div class="login-header">
            <i class="fas fa-shield-check"></i>
            <h1>Account Verification</h1>
            <p>Complete your user registration</p>
        </div>

        <div class="<?php echo $isSuccess ? 'login-notice' : 'alert alert-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <a href="login.php" class="btn btn-primary full-width">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </a>
    </div>
</div>
</body>
</html>
