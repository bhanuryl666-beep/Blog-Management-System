<?php
session_start();

require_once '../backend/post_media.php';

ensure_posts_media_schema($conn);

$id = max(0, (int) ($_GET['id'] ?? 0));
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post ? htmlspecialchars($post['title']) : 'Blog Not Found'; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="public-blog-page">
<header class="site-topbar">
    <a href="index.php" class="site-logo">
        <i class="fas fa-newspaper"></i>
        Blog Updates
    </a>
    <nav class="site-nav">
        <a href="index.php">All Blogs</a>
        <?php if (isset($_SESSION['user']) && ($_SESSION['role'] ?? '') === 'admin' && $post): ?>
            <a href="../backend/admin.php">Admin Panel</a>
        <?php else: ?>
            <a href="../backend/login.php">Admin Login</a>
        <?php endif; ?>
    </nav>
</header>

<main class="blog-shell">
    <?php if (!$post): ?>
        <section class="blog-detail-card">
            <h1>Blog not found</h1>
            <p>The blog you opened does not exist or has been removed.</p>
            <a href="index.php" class="read-back-link"><i class="fas fa-arrow-left"></i> Back to blogs</a>
        </section>
    <?php else: ?>
        <article class="blog-detail-card">
            <a href="index.php<?php echo !empty($post['category']) ? '?category=' . urlencode(normalize_blog_category($post['category'])) : ''; ?>" class="read-back-link">
                <i class="fas fa-arrow-left"></i> Back to <?php echo htmlspecialchars(blog_category_label($post['category'] ?? '')); ?>
            </a>

            <div class="blog-detail-heading">
                <span class="category-pill"><?php echo htmlspecialchars(blog_category_label($post['category'] ?? '')); ?></span>
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                <p><?php echo !empty($post['created_at']) ? date('M j, Y', strtotime($post['created_at'])) : 'Published recently'; ?></p>
            </div>

            <?php if (!empty($post['image_path'])): ?>
                <img src="<?php echo htmlspecialchars(normalize_post_image_path($post['image_path'])); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="blog-detail-image">
            <?php endif; ?>

            <div class="blog-content">
                <?php echo sanitize_blog_content($post['content'] ?? ''); ?>
            </div>
        </article>
    <?php endif; ?>
</main>
</body>
</html>
