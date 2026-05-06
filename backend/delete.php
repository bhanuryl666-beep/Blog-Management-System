<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'post_media.php';

ensure_posts_media_schema($conn);

// ✅ Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../frontend/index.php");
    exit();
}

$id = (int)$_GET['id'];

// 🔐 Prepared statement
$selectStmt = $conn->prepare("SELECT image_path FROM posts WHERE id=?");
$selectStmt->bind_param("i", $id);
$selectStmt->execute();
$post = $selectStmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("DELETE FROM posts WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    delete_post_image($post['image_path'] ?? null);
    header("Location: admin.php?deleted=1");
    exit();
} else {
    echo "❌ Error deleting post!";
}
?>
