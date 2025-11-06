<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkLogin();

$programme_id = $_GET['id'] ?? null;
if (!$programme_id) die("Programme not specified.");

// Get programme name if available
$programme_name = "Programme #$programme_id";
try {
    $stmt = $pdo->prepare("SELECT name FROM programmes WHERE id = ?");
    $stmt->execute([$programme_id]);
    $programme = $stmt->fetch();
    if ($programme && !empty($programme['name'])) {
        $programme_name = $programme['name'];
    }
} catch (PDOException $e) {
    // If 'name' column doesn't exist, use default name
    $programme_name = "Programme #$programme_id";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = uploadFile($_FILES['file']);
    if ($file) {
        $stmt = $pdo->prepare("INSERT INTO programme_media (programme_id, file_name, file_type) VALUES (?, ?, ?)");
        $stmt->execute([$programme_id, $file, $_FILES['file']['type']]);
        $_SESSION['success_message'] = "✅ File uploaded successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $error = "File upload failed. Please check file type and size.";
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM programme_media WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success_message'] = "🗑️ File deleted successfully!";
    header("Location: programme_media.php?id=$programme_id");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM programme_media WHERE programme_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$programme_id]);
$files = $stmt->fetchAll();

// Check for success message from redirect
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme Media | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --danger: #e53e3e;
            --danger-dark: #c53030;
            --success: #38a169;
            --warning: #d69e2e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --text-muted: #a0aec0;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-md: rgba(0, 0, 0, 0.12);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 0;
            min-height: 100vh;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .header-content h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-content h1 i {
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            margin: 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-dark);
            transform: translateX(-2px);
        }

        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 8px 25px var(--shadow-md);
            transform: translateY(-2px);
        }

        /* Form Styles */
        .upload-form {
            margin-bottom: 3rem;
        }

        .form-header {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }

        .form-header h3 {
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-size: 0.95rem;
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
            line-height: 1.4;
        }

        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 3rem 2rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            background: var(--bg);
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.03);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.08);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-dark);
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-block {
            width: 100%;
        }

        /* Media Grid */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .media-card {
            position: relative;
            overflow: hidden;
        }

        .media-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .media-card:hover .media-preview {
            transform: scale(1.05);
        }

        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }

        .media-card:hover .media-overlay {
            opacity: 1;
        }

        .media-actions {
            display: flex;
            gap: 0.5rem;
        }

        .media-info {
            padding: 1rem;
        }

        .media-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            word-break: break-word;
        }

        .media-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .file-icon {
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-size: 3rem;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #f0fff4;
            border-left-color: var(--success);
            color: #22543d;
        }

        .alert-error {
            background: #fed7d7;
            border-left-color: var(--danger);
            color: #742a2a;
        }

        /* File Type Badges */
        .file-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: var(--bg);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .media-grid {
                grid-template-columns: 1fr;
            }

            .media-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .form-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Back Navigation -->
        <a href="programmes.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Programmes
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-photo-video"></i>
                    Programme Media
                </h1>
                <p class="page-subtitle">Manage media files for <strong><?= htmlspecialchars($programme_name) ?></strong></p>
            </div>
            <div class="header-badge">
                <span class="file-type-badge">
                    <i class="fas fa-file"></i>
                    <?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if(isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="upload-form card">
            <div class="form-header">
                <h3><i class="fas fa-upload"></i> Upload New Media</h3>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <div class="file-upload-area" id="dropZone">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h4>Drag & Drop your file here</h4>
                        <p class="form-hint">or click to browse files</p>
                        <input type="file" name="file" class="file-input" id="fileInput" required>
                    </div>
                    <div class="form-hint">
                        <strong>Supported formats:</strong> Images (JPG, PNG, WebP), PDFs, Videos (MP4, MOV) • Max 10MB
                    </div>
                    <div id="filePreview" class="file-preview" style="display: none; margin-top: 1rem;">
                        <div id="previewContent" style="text-align: center;"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-upload"></i> Upload File
                    </button>
                </div>
            </div>
        </form>

        <!-- Media Files Section -->
        <section class="media-section">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; color: var(--text);">
                    <i class="fas fa-layer-group"></i>
                    Uploaded Files
                </h2>
            </div>

            <?php if (empty($files)): ?>
                <div class="empty-state card">
                    <i class="fas fa-photo-video"></i>
                    <h3>No Media Files Yet</h3>
                    <p>Upload your first file to get started. These files will be associated with <?= htmlspecialchars($programme_name) ?>.</p>
                </div>
            <?php else: ?>
                <div class="media-grid" id="mediaGrid">
                    <?php foreach($files as $file): ?>
                        <div class="media-card card" data-id="<?= $file['id'] ?>">
                            <?php if(str_starts_with($file['file_type'], 'image')): ?>
                                <img src="../uploads/<?= htmlspecialchars($file['file_name']) ?>" 
                                     alt="<?= htmlspecialchars($file['file_name']) ?>" 
                                     class="media-preview">
                            <?php else: ?>
                                <div class="file-icon">
                                    <?php if(str_starts_with($file['file_type'], 'video')): ?>
                                        <i class="fas fa-file-video"></i>
                                    <?php elseif($file['file_type'] === 'application/pdf'): ?>
                                        <i class="fas fa-file-pdf" style="color: var(--danger);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-file"></i>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="media-overlay">
                                <div class="media-actions">
                                    <a href="../uploads/<?= htmlspecialchars($file['file_name']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm" 
                                       style="background: rgba(255,255,255,0.9); color: var(--text);">
                                        <i class="fas fa-expand"></i> View
                                    </a>
                                    <a href="?delete=<?= $file['id'] ?>&id=<?= $programme_id ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this file? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                            
                            <div class="media-info">
                                <div class="media-name">
                                    <?= htmlspecialchars($file['file_name']) ?>
                                </div>
                                <div class="media-meta">
                                    <span><?= htmlspecialchars($file['file_type']) ?></span>
                                    <span><?= date('M j, Y', strtotime($file['uploaded_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Enhanced file upload with drag & drop
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('filePreview');
        const previewContent = document.getElementById('previewContent');

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', handleDrop, false);
        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', handleFileSelect);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFileSelect();
        }

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size exceeds 10MB limit. Please choose a smaller file.');
                    fileInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    let previewHTML = '';
                    
                    if (file.type.startsWith('image/')) {
                        previewHTML = `
                            <img src="${e.target.result}" alt="Preview" style="max-width: 200px; border-radius: 8px;">
                            <p style="margin-top: 0.5rem; font-weight: 600;">${file.name}</p>
                        `;
                    } else if (file.type.startsWith('video/')) {
                        previewHTML = `
                            <div style="background: var(--bg); padding: 2rem; border-radius: 8px; margin-bottom: 0.5rem;">
                                <i class="fas fa-file-video" style="font-size: 3rem; color: var(--primary);"></i>
                            </div>
                            <p style="font-weight: 600;">${file.name}</p>
                            <p style="color: var(--text-light); font-size: 0.9rem;">Video file</p>
                        `;
                    } else if (file.type === 'application/pdf') {
                        previewHTML = `
                            <div style="background: var(--bg); padding: 2rem; border-radius: 8px; margin-bottom: 0.5rem;">
                                <i class="fas fa-file-pdf" style="font-size: 3rem; color: var(--danger);"></i>
                            </div>
                            <p style="font-weight: 600;">${file.name}</p>
                            <p style="color: var(--text-light); font-size: 0.9rem;">PDF document</p>
                        `;
                    } else {
                        previewHTML = `
                            <div style="background: var(--bg); padding: 2rem; border-radius: 8px; margin-bottom: 0.5rem;">
                                <i class="fas fa-file" style="font-size: 3rem; color: var(--text-light);"></i>
                            </div>
                            <p style="font-weight: 600;">${file.name}</p>
                            <p style="color: var(--text-light); font-size: 0.9rem;">${file.type || 'Unknown file type'}</p>
                        `;
                    }
                    
                    previewContent.innerHTML = previewHTML;
                    previewContainer.style.display = 'block';
                    
                    dropZone.innerHTML = `
                        <i class="fas fa-check-circle upload-icon" style="color: var(--success);"></i>
                        <h4>${file.name}</h4>
                        <p class="form-hint">Click to choose a different file</p>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>