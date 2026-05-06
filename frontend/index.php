<?php
session_start();

require_once '../backend/post_media.php';

ensure_posts_media_schema($conn);

$categories = blog_categories();
$activeCategory = isset($_GET['category']) ? normalize_blog_category($_GET['category']) : '';
$search = trim($_GET['search'] ?? '');
$date = trim($_GET['date'] ?? '');
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}
$limit = 6;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$categoryCounts = array_fill_keys(array_keys($categories), 0);
$totalBlogCount = 0;

$categoryCountResult = $conn->query("SELECT category, COUNT(*) AS total FROM posts GROUP BY category");
if ($categoryCountResult) {
    while ($countRow = $categoryCountResult->fetch_assoc()) {
        $category = normalize_blog_category($countRow['category'] ?? '');
        $count = (int) ($countRow['total'] ?? 0);
        $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + $count;
        $totalBlogCount += $count;
    }
}

$where = [];
$types = '';
$params = [];

if ($activeCategory !== '') {
    $where[] = 'category = ?';
    $types .= 's';
    $params[] = $activeCategory;
}

if ($search !== '') {
    $where[] = '(title LIKE ? OR content LIKE ?)';
    $types .= 'ss';
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
}

if ($date !== '') {
    $where[] = 'DATE(created_at) = ?';
    $types .= 's';
    $params[] = $date;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM posts" . $whereSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = (int) $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalRows / $limit));

$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$limit, $offset]);
$stmt = $conn->prepare("SELECT * FROM posts" . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$posts = $stmt->get_result();

function blog_filter_url(?string $category, string $search = '', int $page = 1, string $date = ''): string
{
    $query = [];

    if ($category) {
        $query['category'] = $category;
    }

    if ($search !== '') {
        $query['search'] = $search;
    }

    if ($date !== '') {
        $query['date'] = $date;
    }

    if ($page > 1) {
        $query['page'] = $page;
    }

    return 'index.php' . ($query ? '?' . http_build_query($query) : '');
}

function render_blog_results(mysqli_result $posts, array $categories, array $categoryCounts, int $totalBlogCount, string $activeCategory, string $search, string $date, int $page, int $totalPages, int $totalRows): void
{
    ?>
    <section class="category-tabs" aria-label="Blog categories">
        <a href="<?php echo htmlspecialchars(blog_filter_url(null, $search, 1, $date)); ?>" class="<?php echo $activeCategory === '' ? 'active' : ''; ?>">
            <span>All</span>
        </a>
        <?php foreach ($categories as $value => $label): ?>
            <a href="<?php echo htmlspecialchars(blog_filter_url($value, $search, 1, $date)); ?>" class="<?php echo $activeCategory === $value ? 'active' : ''; ?>">
                <span><?php echo htmlspecialchars($label); ?></span>
                <span class="category-count"><?php echo number_format((int) ($categoryCounts[$value] ?? 0)); ?></span>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="public-post-grid">
        <?php while ($row = $posts->fetch_assoc()): ?>
            <article class="public-post-card">
                <?php if (!empty($row['image_path'])): ?>
                    <a href="blog.php?id=<?php echo (int) $row['id']; ?>" class="public-post-media">
                        <img src="<?php echo htmlspecialchars(normalize_post_image_path($row['image_path'])); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                    </a>
                <?php else: ?>
                    <a href="blog.php?id=<?php echo (int) $row['id']; ?>" class="public-post-media public-post-media-fallback" aria-label="<?php echo htmlspecialchars($row['title']); ?>">
                        <i class="fas fa-newspaper"></i>
                    </a>
                <?php endif; ?>

                <div class="public-post-body">
                    <span class="category-pill"><?php echo htmlspecialchars(blog_category_label($row['category'] ?? '')); ?></span>
                    <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p><?php echo htmlspecialchars(blog_excerpt($row['content'] ?? '')); ?></p>
                    <div class="public-post-footer">
                        <span><?php echo !empty($row['created_at']) ? date('M j, Y', strtotime($row['created_at'])) : 'New'; ?></span>
                        <a href="blog.php?id=<?php echo (int) $row['id']; ?>">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>

        <?php if ($totalRows === 0): ?>
            <div class="empty-state">
                <h2>No blogs found</h2>
                <p>Try another category, search term, or date.</p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="public-pagination" aria-label="Pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo htmlspecialchars(blog_filter_url($activeCategory ?: null, $search, $i, $date)); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
    <?php
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    render_blog_results($posts, $categories, $categoryCounts, $totalBlogCount, $activeCategory, $search, $date, $page, $totalPages, $totalRows);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Updates</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="public-blog-page">
<header class="site-topbar">
    <a href="index.php" class="site-logo">
        <i class="fas fa-newspaper"></i>
        Blog Updates
    </a>
    <nav class="site-nav">
        <?php if (isset($_SESSION['user']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="../backend/admin.php">Admin Panel</a>
        <?php else: ?>
            <a href="../backend/login.php">Admin Login</a>
        <?php endif; ?>
    </nav>
</header>

<main class="blog-shell">
    <section class="blog-hero">
        <div>
            <h1>Latest Updates</h1>
        </div>
        <form method="GET" class="public-search" id="blogFilterForm">
            <?php if ($activeCategory !== ''): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search updates..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" aria-label="Filter by date">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </section>

    <div id="blogResults">
        <?php render_blog_results($posts, $categories, $categoryCounts, $totalBlogCount, $activeCategory, $search, $date, $page, $totalPages, $totalRows); ?>
    </div>
</main>
<script>
$(function () {
    const $results = $('#blogResults');
    const $form = $('#blogFilterForm');

    function loadBlogs(url, updateHistory = true) {
        const ajaxUrl = new URL(url, window.location.href);
        ajaxUrl.searchParams.set('ajax', '1');

        $results.addClass('is-loading');

        $.ajax({
            url: ajaxUrl.toString(),
            method: 'GET',
            success: function (html) {
                $results.html(html);
                if (updateHistory) {
                    ajaxUrl.searchParams.delete('ajax');
                    window.history.pushState({}, '', ajaxUrl.toString());
                }
            },
            complete: function () {
                $results.removeClass('is-loading');
            }
        });
    }

    $form.on('submit', function (event) {
        event.preventDefault();
        loadBlogs('index.php?' + $form.serialize());
    });

    $form.find('input[name="date"]').on('change', function () {
        $form.trigger('submit');
    });

    $(document).on('click', '.category-tabs a, .public-pagination a', function (event) {
        event.preventDefault();

        const url = new URL(this.href, window.location.href);
        const category = url.searchParams.get('category') || '';
        const search = url.searchParams.get('search') || '';
        const date = url.searchParams.get('date') || '';

        $form.find('input[name="category"]').remove();
        if (category) {
            $form.prepend($('<input>', { type: 'hidden', name: 'category', value: category }));
        }

        $form.find('input[name="search"]').val(search);
        $form.find('input[name="date"]').val(date);
        loadBlogs(url.toString());
    });

    window.addEventListener('popstate', function () {
        loadBlogs(window.location.href, false);
    });
});
</script>
</body>
</html>
