<?php
require_once 'db.php';

function project_root_path(): string
{
    return dirname(__DIR__);
}

function ensure_posts_media_schema(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM posts LIKE 'image_path'");

    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE posts ADD COLUMN image_path VARCHAR(255) NULL AFTER content");
    }

    $categoryResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'category'");

    if ($categoryResult && $categoryResult->num_rows === 0) {
        $conn->query("ALTER TABLE posts ADD COLUMN category VARCHAR(80) NOT NULL DEFAULT 'latest-jobs' AFTER content");
    }
}

function blog_categories(): array
{
    return [
        'latest-jobs' => 'Latest Jobs',
        'admit-card' => 'Admit Card',
        'results' => 'Results',
        'answer-key' => 'Answer Key',
        'syllabus' => 'Syllabus',
        'admission' => 'Admission',
    ];
}

function normalize_blog_category(?string $category): string
{
    $categories = blog_categories();
    $category = strtolower(trim((string) $category));

    return array_key_exists($category, $categories) ? $category : 'latest-jobs';
}

function blog_category_label(?string $category): string
{
    $categories = blog_categories();
    $category = normalize_blog_category($category);

    return $categories[$category];
}

function sanitize_blog_content(string $content): string
{
    $allowedTags = '<p><br><div><span><font><strong><b><em><i><u><s><strike><sub><sup><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td><a><img><hr>';
    $content = strip_tags($content, $allowedTags);

    $content = preg_replace('/\s(on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content);
    $content = preg_replace('/javascript\s*:/i', '', $content);
    $content = preg_replace('/\sstyle\s*=\s*("|\')(?!(?:[^"\']*?(?:color|background-color|text-align|font-size)\s*:))[^"\']*\1/i', '', $content);
    $content = preg_replace('/\sclass\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $content);

    return trim($content);
}

function blog_excerpt(string $content, int $limit = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));

    if (strlen($text) <= $limit) {
        return $text;
    }

    return substr($text, 0, $limit) . '...';
}

function post_upload_directory(): string
{
    return project_root_path() . '/uploads';
}

function normalize_post_image_path(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');

    if (str_starts_with($normalized, 'uploads/')) {
        return '../' . $normalized;
    }

    if (str_starts_with($normalized, '../uploads/')) {
        return $normalized;
    }

    return '../uploads/' . basename($normalized);
}

function post_image_full_path(?string $relativePath): ?string
{
    $normalized = normalize_post_image_path($relativePath);

    if (!$normalized) {
        return null;
    }

    return project_root_path() . '/' . ltrim(substr($normalized, 3), '/');
}

function ensure_post_upload_directory(): bool
{
    $directory = post_upload_directory();

    if (is_dir($directory)) {
        return true;
    }

    return mkdir($directory, 0775, true);
}

function validate_uploaded_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return '❌ Image upload failed. Please try again.';
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return '❌ Reference photo must be smaller than 5MB.';
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!$imageInfo || !isset($allowedTypes[$imageInfo['mime']])) {
        return '❌ Please upload a valid JPG, PNG, WEBP, or GIF image.';
    }

    return null;
}

function save_uploaded_post_image(array $file, ?string $oldPath = null): array
{
    $validationError = validate_uploaded_image($file);
    if ($validationError) {
        return [null, $validationError];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [$oldPath, null];
    }

    if (!ensure_post_upload_directory()) {
        return [null, '❌ Upload folder could not be created.'];
    }

    $imageInfo = getimagesize($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $extension = $extensions[$imageInfo['mime']];
    $filename = 'post_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $relativePath = '../uploads/' . $filename;
    $absolutePath = post_upload_directory() . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        return [null, '❌ Unable to save the uploaded image.'];
    }

    if ($oldPath) {
        delete_post_image($oldPath);
    }

    return [$relativePath, null];
}

function delete_post_image(?string $relativePath): void
{
    $fullPath = post_image_full_path($relativePath);
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}
