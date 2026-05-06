<?php
session_start();
require_once 'auth.php';

// If already logged in
if (isset($_SESSION['user'])) {
    auth_redirect_for_role($_SESSION['role'] ?? 'user');
}

$error = "";
unset($_SESSION['register_notice'], $_SESSION['verification_link']);

try {
    $pdo = create_pdo_connection();
    ensure_auth_schema($pdo);
} catch (Throwable $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 🔐 Validation
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "❌ All fields are required!";
    } else {

        // ✅ Prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            // 🔐 Password verify
            if (password_verify($password, $user['password'])) {
                $user_role = $user['role'] ?? 'user';

                if ($user_role !== 'admin') {
                    $error = "❌ Only admins need to log in. Visitors can read blogs without an account.";
                } else {
                    // ✅ Store session
                    auth_set_login_session($user);
                    auth_redirect_for_role($user_role);
                }

            } else {
                $error = "❌ Invalid password!";
            }

        } else {
            $error = "❌ User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Modern Blog</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link rel="stylesheet" href="../frontend/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-card">

        <div class="login-header">
            <i class="fas fa-blog"></i>
            <h1>Admin Login</h1>
            <p>Visitors can read blogs without logging in</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">

            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary full-width">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>

        </form>

    </div>
</div>
</body>
</html>
