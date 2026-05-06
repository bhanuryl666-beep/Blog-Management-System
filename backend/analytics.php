<?php
session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../frontend/index.php");
    exit();
}

require_once 'db.php';

$analyticsRows = [];
$totalViews = 0;
$todayViews = 0;
$avgViews = 0;
$error = '';

function analyticsTableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$tableName]);

    return (bool) $stmt->fetchColumn();
}

function analyticsColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
    $stmt->execute([$columnName]);

    return (bool) $stmt->fetchColumn();
}

try {
    $pdo = create_pdo_connection();

    if (!analyticsTableExists($pdo, 'analytics')) {
        throw new RuntimeException("The analytics table does not exist yet.");
    }

    $hasDate = analyticsColumnExists($pdo, 'analytics', 'date');
    $hasViews = analyticsColumnExists($pdo, 'analytics', 'page_views');

    if (!$hasDate || !$hasViews) {
        throw new RuntimeException("The analytics table must include both date and page_views columns.");
    }

    $analyticsRows = $pdo->query(
        "SELECT DATE(date) AS visit_date, SUM(page_views) AS views
         FROM analytics
         GROUP BY DATE(date)
         ORDER BY visit_date DESC
         LIMIT 7"
    )->fetchAll(PDO::FETCH_ASSOC);

    $totalViews = (int) ($pdo->query("SELECT COALESCE(SUM(page_views), 0) FROM analytics")->fetchColumn() ?: 0);
    $todayViews = (int) ($pdo->query("SELECT COALESCE(SUM(page_views), 0) FROM analytics WHERE DATE(date) = CURDATE()")->fetchColumn() ?: 0);
    $avgViews = (int) round((float) ($pdo->query("SELECT COALESCE(AVG(daily_views), 0) FROM (SELECT SUM(page_views) AS daily_views FROM analytics GROUP BY DATE(date)) daily_totals")->fetchColumn() ?: 0));
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            padding: 24px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .header, .card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .header {
            padding: 28px;
            margin-bottom: 24px;
        }
        .header a {
            display: inline-block;
            margin-top: 12px;
            color: #475569;
            text-decoration: none;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .card {
            padding: 24px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 12px;
        }
        .error {
            background: #ef4444;
            color: white;
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            color: #475569;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Analytics</h1>
            <p style="margin-top:10px;color:#475569;">Overview of recent traffic recorded in your analytics table.</p>
            <a href="admin.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="stats">
                <div class="card">
                    <div>Total Views</div>
                    <div class="stat-number"><?php echo number_format($totalViews); ?></div>
                </div>
                <div class="card">
                    <div>Today's Views</div>
                    <div class="stat-number"><?php echo number_format($todayViews); ?></div>
                </div>
                <div class="card">
                    <div>Daily Average</div>
                    <div class="stat-number"><?php echo number_format($avgViews); ?></div>
                </div>
            </div>

            <div class="card">
                <h2 style="margin-bottom:12px;">Last 7 Days</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analyticsRows as $row): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($row['visit_date'])); ?></td>
                            <td><?php echo number_format((int) $row['views']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($analyticsRows)): ?>
                        <tr>
                            <td colspan="2" style="color:#64748b;">No analytics data available.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
