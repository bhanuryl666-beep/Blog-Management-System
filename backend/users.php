<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/index.php");
    exit();
}

require_once 'db.php';

$users = [];
$error = '';

function usersTableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);

    return (bool) $stmt->fetchColumn();
}

function usersColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
    $stmt->execute([$columnName]);

    return (bool) $stmt->fetchColumn();
}

try {
    $pdo = create_pdo_connection();

    if (!usersTableExists($pdo, 'users')) {
        throw new RuntimeException("The users table was not found in the blog database.");
    }

    // Handle delete user
    if (isset($_GET['delete'])) {
        $deleteId = (int) $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$deleteId]);
        header("Location: users.php?success=deleted");
        exit();
    }

    $hasEmail = usersColumnExists($pdo, 'users', 'email');
    $hasRole = usersColumnExists($pdo, 'users', 'role');
    $hasCreatedAt = usersColumnExists($pdo, 'users', 'created_at');

    $selectParts = [
        "id",
        "username",
        $hasEmail ? "email" : "NULL AS email",
        $hasRole ? "role" : "'user' AS role",
        $hasCreatedAt ? "created_at" : "NULL AS created_at",
    ];
    $orderBy = $hasCreatedAt ? "created_at DESC" : "id DESC";

    $stmt = $pdo->query("SELECT " . implode(", ", $selectParts) . " FROM users ORDER BY $orderBy");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        th, td { padding: 20px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 600; }
        tr:hover { background: #f1f5f9; }
        .btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .status { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; }
        .status-admin { background: #fef3c7; color: #92400e; }
        .status-user { background: #d1fae5; color: #065f46; }
        .success { background: #10b981; color: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
            <a href="admin.php" style="color: #64748b; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">✅ User deleted successfully!</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#ef4444;color:white;padding:15px;border-radius:12px;margin-bottom:20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong>#<?php echo $user['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="status status-<?php echo strtolower($user['role']); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo !empty($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                    </td>
                    <td>
                        <?php if ($user['role'] != 'admin'): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure?')"
                               title="Delete User">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        <?php else: ?>
                            <span style="color: #64748b;">Admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$error && empty($users)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#64748b;">No users found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
