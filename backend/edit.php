<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'post_media.php';

ensure_posts_media_schema($conn);

$error = "";

// ✅ Get ID safely
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ✅ Fetch post using prepared statement
$stmt = $conn->prepare("SELECT * FROM posts WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: ../frontend/index.php");
    exit();
}

// ✅ Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $content = sanitize_blog_content($_POST['content'] ?? '');
    $category = normalize_blog_category($_POST['category'] ?? '');
    $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

    if (empty($title) || empty($content)) {
        $error = "❌ Title and Content cannot be empty!";
    } else {
        $imagePath = $row['image_path'] ?? null;

        if ($removeImage) {
            delete_post_image($imagePath);
            $imagePath = null;
        }

        if (isset($_FILES['reference_image']) && ($_FILES['reference_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            [$newImagePath, $uploadError] = save_uploaded_post_image($_FILES['reference_image'], $imagePath);
            if ($uploadError) {
                $error = $uploadError;
            } else {
                $imagePath = $newImagePath;
            }
        }

        if (!$error) {
            // 🔐 Secure update
            $update_stmt = $conn->prepare("UPDATE posts SET title=?, content=?, category=?, image_path=? WHERE id=?");
            $update_stmt->bind_param("ssssi", $title, $content, $category, $imagePath, $id);

            if ($update_stmt->execute()) {
                header("Location: admin.php?updated=1");
                exit();
            } else {
                $error = "❌ Error updating post!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Modern Blog</title>
    <link rel="icon" type="image/svg+xml" href="../frontend/favicon.svg">
    <link rel="stylesheet" href="../frontend/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="create-post-page">
<div class="page-container">
    <div class="bg-pattern"></div>

    <div class="content-wrapper">
        <div class="glass-card">

            <div class="card-header">
                <a href="../frontend/index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="header-content">
                    <div class="icon-wrapper">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h1 class="card-title">Edit Post</h1>
                    <p class="card-subtitle">Update your existing post</p>
                </div>
            </div>

            <!-- 🔴 ERROR -->
            <?php if (!empty($error)): ?>
                <p style="color:red; text-align:center;">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form method="POST" class="post-form" enctype="multipart/form-data">
                <div class="form-field">
                    <label class="field-label">
                        <i class="fas fa-folder"></i> Category
                    </label>
                    <select name="category" class="field-input" required>
                        <?php foreach (blog_categories() as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo normalize_blog_category($row['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label class="field-label">
                        <i class="fas fa-heading"></i> Title
                    </label>
                    <input type="text" name="title"
                        value="<?php echo htmlspecialchars($row['title']); ?>"
                        required maxlength="100" class="field-input">
                </div>

                <div class="form-field">
                    <label class="field-label">
                        <i class="fas fa-align-left"></i> Content
                    </label>
                    <div class="editor-toolbar" aria-label="Editor tools">
                        <select data-command="formatBlock" aria-label="Text format">
                            <option value="p">Paragraph</option>
                            <option value="h1">Heading 1</option>
                            <option value="h2">Heading 2</option>
                            <option value="h3">Heading 3</option>
                            <option value="h4">Heading 4</option>
                            <option value="pre">Preformatted</option>
                        </select>
                        <select data-command="fontSize" aria-label="Font size">
                            <option value="">Size</option>
                            <option value="2">Small</option>
                            <option value="3">Normal</option>
                            <option value="5">Large</option>
                            <option value="7">Huge</option>
                        </select>
                        <button type="button" data-command="undo" title="Undo"><i class="fas fa-undo"></i></button>
                        <button type="button" data-command="redo" title="Redo"><i class="fas fa-redo"></i></button>
                        <button type="button" data-command="bold"><i class="fas fa-bold"></i></button>
                        <button type="button" data-command="italic"><i class="fas fa-italic"></i></button>
                        <button type="button" data-command="underline"><i class="fas fa-underline"></i></button>
                        <button type="button" data-command="strikeThrough"><i class="fas fa-strikethrough"></i></button>
                        <button type="button" data-command="superscript" title="Superscript">x<sup>2</sup></button>
                        <button type="button" data-command="subscript" title="Subscript">x<sub>2</sub></button>
                        <button type="button" data-command="justifyLeft" title="Align left"><i class="fas fa-align-left"></i></button>
                        <button type="button" data-command="justifyCenter" title="Align center"><i class="fas fa-align-center"></i></button>
                        <button type="button" data-command="justifyRight" title="Align right"><i class="fas fa-align-right"></i></button>
                        <button type="button" data-command="justifyFull" title="Justify"><i class="fas fa-align-justify"></i></button>
                        <button type="button" data-command="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                        <button type="button" data-command="insertOrderedList"><i class="fas fa-list-ol"></i></button>
                        <button type="button" data-command="outdent" title="Outdent"><i class="fas fa-outdent"></i></button>
                        <button type="button" data-command="indent" title="Indent"><i class="fas fa-indent"></i></button>
                        <label class="editor-color" title="Text color">
                            <i class="fas fa-palette"></i>
                            <input type="color" data-command="foreColor" value="#111827">
                        </label>
                        <label class="editor-color" title="Background color">
                            <i class="fas fa-fill-drip"></i>
                            <input type="color" data-command="backColor" value="#fff3bf">
                        </label>
                        <button type="button" data-action="link"><i class="fas fa-link"></i></button>
                        <button type="button" data-action="quote"><i class="fas fa-quote-left"></i></button>
                        <button type="button" data-action="code"><i class="fas fa-code"></i></button>
                        <button type="button" data-command="insertHorizontalRule" title="Horizontal line"><i class="fas fa-minus"></i></button>
                        <button type="button" data-action="table"><i class="fas fa-table"></i></button>
                        <button type="button" data-action="image"><i class="fas fa-image"></i></button>
                        <button type="button" data-command="removeFormat" title="Clear formatting"><i class="fas fa-eraser"></i></button>
                    </div>
                    <div class="rich-editor" contenteditable="true" data-editor aria-label="Post content"><?php echo $row['content']; ?></div>
                    <textarea name="content" class="field-textarea editor-hidden" data-editor-input><?php echo htmlspecialchars($row['content']); ?></textarea>
                </div>

                <div class="form-field">
                    <label class="field-label">
                        <i class="fas fa-image"></i> Reference Photo
                    </label>

                    <?php if (!empty($row['image_path'])): ?>
                        <div class="current-image-card">
                            <img src="<?php echo htmlspecialchars(normalize_post_image_path($row['image_path'])); ?>" alt="Current reference photo" class="current-post-image">
                            <label class="remove-image-toggle">
                                <input type="checkbox" name="remove_image" value="1">
                                <span>Remove current image</span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <label class="upload-dropzone">
                        <input type="file" name="reference_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="upload-input" data-file-input>
                        <span class="upload-badge"><i class="fas fa-cloud-upload-alt"></i> Choose Image</span>
                        <span class="upload-title">Upload a new reference photo</span>
                        <span class="upload-subtitle">Leave empty to keep the current image</span>
                        <span class="upload-file-name" data-file-name>No new file selected</span>
                    </label>
                </div>

                <div class="form-actions">
                    <a href="../frontend/index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Post</button>
                </div>

            </form>

        </div>
    </div>
</div>
<script>
    document.querySelectorAll('[data-file-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const fileNameTarget = input.closest('.upload-dropzone')?.querySelector('[data-file-name]');
            const fileName = input.files && input.files[0] ? input.files[0].name : 'No new file selected';

            if (fileNameTarget) {
                fileNameTarget.textContent = fileName;
            }
        });
    });

    const editor = document.querySelector('[data-editor]');
    const editorInput = document.querySelector('[data-editor-input]');

    const syncEditor = () => {
        if (editor && editorInput) {
            editorInput.value = editor.innerHTML.trim();
        }
    };

    const runCommand = (command, value = null) => {
        editor?.focus();
        document.execCommand(command, false, value);
        syncEditor();
    };

    document.querySelectorAll('.editor-toolbar button[data-command]').forEach((button) => {
        button.addEventListener('click', () => runCommand(button.dataset.command, button.dataset.value || null));
    });

    document.querySelectorAll('.editor-toolbar select[data-command]').forEach((select) => {
        select.addEventListener('change', () => {
            if (select.value) {
                runCommand(select.dataset.command, select.value);
                select.selectedIndex = 0;
            }
        });
    });

    document.querySelectorAll('.editor-toolbar input[type="color"][data-command]').forEach((input) => {
        input.addEventListener('input', () => runCommand(input.dataset.command, input.value));
    });

    document.querySelector('[data-action="link"]')?.addEventListener('click', () => {
        const url = prompt('Paste link URL');
        if (url) {
            runCommand('createLink', url);
        }
    });

    document.querySelector('[data-action="quote"]')?.addEventListener('click', () => {
        runCommand('formatBlock', 'blockquote');
    });

    document.querySelector('[data-action="code"]')?.addEventListener('click', () => {
        const code = prompt('Paste code or text');
        if (code) {
            document.execCommand('insertHTML', false, '<pre><code>' + code.replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char])) + '</code></pre><p><br></p>');
            syncEditor();
        }
    });

    document.querySelector('[data-action="table"]')?.addEventListener('click', () => {
        document.execCommand('insertHTML', false, '<table><tbody><tr><th>Heading</th><th>Details</th></tr><tr><td>Item</td><td>Information</td></tr></tbody></table><p><br></p>');
        syncEditor();
    });

    document.querySelector('[data-action="image"]')?.addEventListener('click', () => {
        const url = prompt('Paste image URL');
        if (url) {
            document.execCommand('insertImage', false, url);
            syncEditor();
        }
    });

    editor?.addEventListener('input', syncEditor);
    document.querySelector('form.post-form')?.addEventListener('submit', syncEditor);
    syncEditor();
</script>
</body>
</html>
