<?php
session_start();

// 🔐 Allow only admin
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/index.php");
    exit();
}

require_once 'post_media.php';

ensure_posts_media_schema($conn);

$login_notice = $_SESSION['login_notice'] ?? '';
unset($_SESSION['login_notice']);
$view = ($_GET['view'] ?? 'dashboard') === 'blogs' ? 'blogs' : 'dashboard';

try {
    $pdo = create_pdo_connection();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function adminTableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);

    return (bool) $stmt->fetchColumn();
}

$total_posts = adminTableExists($pdo, 'posts')
    ? (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn()
    : 0;
$total_visitors = adminTableExists($pdo, 'analytics')
    ? (int) ($pdo->query("SELECT COALESCE(SUM(page_views), 0) FROM analytics WHERE DATE(date) = CURDATE()")->fetchColumn() ?: 0)
    : 0;
$uptime = adminTableExists($pdo, 'server_status')
    ? (float) ($pdo->query("SELECT COALESCE(AVG(uptime), 100) FROM server_status WHERE DATE(date) = CURDATE()")->fetchColumn() ?: 100)
    : 100;
$recent_posts = adminTableExists($pdo, 'posts')
    ? $pdo->query("SELECT id, title, category, created_at FROM posts ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC)
    : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* [Previous CSS remains the same - keeping it clean] */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        .admin-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .sidebar {
            position: sticky;
            top: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #4338ca;
            margin-bottom: 24px;
        }

        .sidebar-brand i {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 14px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .sidebar-nav .sidebar-danger {
            color: #dc2626;
            margin-top: 12px;
        }

        .sidebar-nav .sidebar-danger:hover {
            background: #dc2626;
            color: white;
        }

        .main-content {
            min-width: 0;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: absolute;
            top: 25px;
            right: 25px;
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #dc2626;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card, .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before, .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-card:hover, .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .stat-icon, .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: white;
        }

        .stat-primary { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-success { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .action-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }

        .action-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .admin-layout,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .logout-btn {
                position: static;
                margin-top: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-layout">
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <i class="fas fa-crown"></i>
                    <span>Admin Panel</span>
                </div>
                <nav class="sidebar-nav" aria-label="Admin dashboard">
                    <a href="admin.php" class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-gauge-high"></i> Dashboard</a>
                    <a href="admin.php?view=blogs" class="<?php echo $view === 'blogs' ? 'active' : ''; ?>"><i class="fas fa-list-check"></i> Manage Blogs</a>
                    <a href="create.php"><i class="fas fa-plus"></i> Add Blog</a>
                    <a href="../frontend/index.php"><i class="fas fa-eye"></i> View Site</a>
                    <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                    <a href="logout.php" class="sidebar-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </aside>

            <main class="main-content">
        <?php if ($view === 'dashboard'): ?>
        <!-- Header -->
        <div class="header">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            <h1><i class="fas fa-crown"></i> Admin Dashboard</h1>
            <p style="font-size: 1.2rem; color: #64748b; font-weight: 500;">
                Welcome back, <?php echo htmlspecialchars($_SESSION['user']); ?>! 👑
            </p>
            <p style="margin-top:10px;font-size:1rem;color:#4338ca;font-weight:600;">
                Logged in as Admin
            </p>
        </div>
        <?php endif; ?>

        <?php if ($login_notice): ?>
            <div style="background:#dcfce7;color:#166534;padding:16px 18px;border-radius:16px;margin-bottom:24px;font-weight:600;">
                <?php echo htmlspecialchars($login_notice); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) || isset($_GET['updated']) || isset($_GET['deleted'])): ?>
            <div style="background:#dcfce7;color:#166534;padding:16px 18px;border-radius:16px;margin-bottom:24px;font-weight:600;">
                Blog changes saved successfully.
            </div>
        <?php endif; ?>

        <?php if ($view === 'dashboard'): ?>
        <!-- Real-time Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-primary">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_posts); ?></div>
                <div class="stat-label">Total Posts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-warning">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_visitors); ?></div>
                <div class="stat-label">Today's Visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-danger">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="stat-number"><?php echo round($uptime, 1); ?>%</div>
                <div class="stat-label">Uptime Today</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="action-card" id="manage-blogs" style="margin-bottom:30px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
                <h3 class="action-title" style="margin:0;">Manage Blogs</h3>
                <a href="create.php" class="action-btn" style="width:auto;">
                    <i class="fas fa-plus"></i> Add New Blog
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;background:white;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:14px;border-bottom:1px solid #e2e8f0;">Title</th>
                            <th style="text-align:left;padding:14px;border-bottom:1px solid #e2e8f0;">Category</th>
                            <th style="text-align:left;padding:14px;border-bottom:1px solid #e2e8f0;">Date</th>
                            <th style="text-align:left;padding:14px;border-bottom:1px solid #e2e8f0;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_posts as $post): ?>
                            <tr>
                                <td style="padding:14px;border-bottom:1px solid #e2e8f0;font-weight:600;">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </td>
                                <td style="padding:14px;border-bottom:1px solid #e2e8f0;">
                                    <?php echo htmlspecialchars(blog_category_label($post['category'] ?? '')); ?>
                                </td>
                                <td style="padding:14px;border-bottom:1px solid #e2e8f0;">
                                    <?php echo !empty($post['created_at']) ? date('M j, Y', strtotime($post['created_at'])) : 'N/A'; ?>
                                </td>
                                <td style="padding:14px;border-bottom:1px solid #e2e8f0;">
                                    <a href="../frontend/blog.php?id=<?php echo (int) $post['id']; ?>" style="margin-right:12px;">View</a>
                                    <a href="edit.php?id=<?php echo (int) $post['id']; ?>" style="margin-right:12px;">Edit</a>
                                    <a href="delete.php?id=<?php echo (int) $post['id']; ?>" onclick="return confirm('Delete this blog?')" style="color:#dc2626;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_posts)): ?>
                            <tr>
                                <td colspan="4" style="padding:20px;text-align:center;color:#64748b;">No blogs yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

            </main>
        </div>
    </div>
</body>
</html>
