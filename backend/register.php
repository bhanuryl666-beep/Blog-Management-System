<?php
session_start();
require_once 'auth.php';

if (isset($_SESSION['user'])) {
    auth_redirect_for_role($_SESSION['role'] ?? 'user');
}

$error = '';
$success = '';
$verificationLink = '';
$form = [
    'username' => '',
    'email' => '',
];

try {
    $pdo = create_pdo_connection();
    ensure_auth_schema($pdo);
} catch (Throwable $e) {
    die('Connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['username'] = trim($_POST['username'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($form['username'] === '' || $form['email'] === '' || $password === '' || $confirmPassword === '') {
        $error = '❌ All fields are required!';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Please enter a valid email address.';
    } elseif (strlen($password) < 4) {
        $error = '❌ Password must be at least 4 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = '❌ Password and confirm password do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$form['username'], $form['email']]);

        if ($stmt->fetch()) {
            $error = '❌ Username or email already exists.';
        } else {
            $token = auth_generate_token();
            $verificationLink = auth_verification_link($token);

            $insert = $pdo->prepare(
                'INSERT INTO users (username, email, password, role, verification_token) VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $form['username'],
                $form['email'],
                password_hash($password, PASSWORD_DEFAULT),
                'user',
                $token,
            ]);

            auth_send_verification_email($form['email'], $form['username'], $verificationLink);
            $success = '✅ Registration successful. Verify your email before logging in.';
            $_SESSION['register_notice'] = $success;
            $_SESSION['verification_link'] = $verificationLink;
            header('Location: login.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Modern Blog</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link rel="stylesheet" href="../frontend/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-card auth-card">
        <div class="login-header">
            <i class="fas fa-user-plus"></i>
            <h1>Create Account</h1>
            <p>Register as a user and verify your email</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="login-notice">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($verificationLink): ?>
            <div class="verification-box">
                <strong>Verification link:</strong>
                <a href="<?php echo htmlspecialchars($verificationLink); ?>"><?php echo htmlspecialchars($verificationLink); ?></a>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($form['username']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($form['email']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary full-width">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">Login here</a>
        </p>
    </div>
</div>
</body>
</html>
