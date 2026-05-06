<?php
session_start();
require_once 'auth.php';

$message = '';
$error = '';

try {
    $pdo = create_pdo_connection();
    ensure_auth_schema($pdo);
} catch (Throwable $e) {
    die('Connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = '❌ Email is required.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, email_verified_at FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = '❌ No user found with this email.';
        } elseif (!empty($user['email_verified_at'])) {
            $message = '✅ This account is already verified. You can log in now.';
        } else {
            $token = auth_generate_token();
            $update = $pdo->prepare('UPDATE users SET verification_token = ? WHERE id = ?');
            $update->execute([$token, $user['id']]);
            $verificationLink = auth_verification_link($token);

            auth_send_verification_email($user['email'], $user['username'], $verificationLink);
            $message = '✅ A fresh verification link is ready below.';
            $_SESSION['verification_link'] = $verificationLink;
        }
    }
}

$verificationLink = $_SESSION['verification_link'] ?? '';
unset($_SESSION['verification_link']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link rel="stylesheet" href="../frontend/style.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-card auth-card">
        <div class="login-header">
            <i class="fas fa-envelope-open-text"></i>
            <h1>Resend Verification</h1>
            <p>Enter your email to get a new verification link</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="login-notice"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($verificationLink): ?>
            <div class="verification-box">
                <strong>Verification link:</strong>
                <a href="<?php echo htmlspecialchars($verificationLink); ?>"><?php echo htmlspecialchars($verificationLink); ?></a>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" required>
            </div>

            <button type="submit" class="btn btn-primary full-width">
                <i class="fas fa-paper-plane"></i> Send Verification Link
            </button>
        </form>

        <p class="auth-switch">
            <a href="login.php">Back to login</a>
        </p>
    </div>
</div>
</body>
</html>
