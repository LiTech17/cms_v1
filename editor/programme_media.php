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
        /* === MOBILE-FIRST CSS VARIABLES === */
        :root {
            /* Colors */
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
            --shadow-lg: rgba(0, 0, 0, 0.16);
            
            /* Spacing - Mobile First */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            
            /* Typography - Mobile First */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            
            /* Border radius */
            --radius-sm: 6px;
            --radius: 10px;
            --radius-lg: 14px;
            
            /* Transitions */
            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Layout */
            --container-padding: var(--space-md);
            --header-height: 60px;
        }

        /* === BASE STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        /* === CONTAINER & LAYOUT === */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--container-padding);
            min-height: 100vh;
        }

        /* === HEADER STYLES === */
        .page-header {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-lg);
            border-bottom: 2px solid var(--border);
        }

        .header-content h1 {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--text);
            margin-bottom: var(--space-xs);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            line-height: 1.2;
        }

        .header-content h1 i {
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            flex-shrink: 0;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: var(--text-base);
            margin: 0;
            line-height: 1.4;
        }

        .header-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-md);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            padding: var(--space-sm) 0;
            transition: var(--transition);
            width: fit-content;
        }

        .back-link:hover,
        .back-link:focus {
            color: var(--primary-dark);
            transform: translateX(-2px);
        }

        /* === CARD STYLES === */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition-slow);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 8px 25px var(--shadow-md);
        }

        /* === UPLOAD FORM === */
        .upload-form {
            margin-bottom: var(--space-2xl);
        }

        .form-header {
            padding: var(--space-lg) var(--space-xl);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }

        .form-header h3 {
            font-size: var(--text-lg);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .form-content {
            padding: var(--space-xl);
        }

        .form-group {
            margin-bottom: var(--space-lg);
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: var(--space-sm);
            color: var(--text);
            font-size: var(--text-sm);
        }

        .form-hint {
            font-size: var(--text-xs);
            color: var(--text-light);
            margin-top: var(--space-xs);
            line-height: 1.4;
        }

        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: var(--space-2xl) var(--space-lg);
            text-align: center;
            transition: var(--transition-slow);
            cursor: pointer;
            background: var(--bg);
            position: relative;
        }

        .file-upload-area:hover,
        .file-upload-area:focus-within {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.03);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.08);
            transform: scale(1.01);
        }

        .upload-icon {
            font-size: 2.5rem;
            color: var(--text-light);
            margin-bottom: var(--space-md);
            transition: var(--transition);
        }

        .file-upload-area:hover .upload-icon {
            transform: translateY(-2px);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        /* === BUTTONS === */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-lg);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: var(--text-sm);
            cursor: pointer;
            transition: var(--transition-slow);
            text-decoration: none;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background: var(--danger-dark);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: var(--space-sm) var(--space-md);
            font-size: var(--text-xs);
        }

        .btn-block {
            width: 100%;
        }

        /* === MEDIA GRID === */
        .media-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-lg);
            margin-top: var(--space-lg);
        }

        .media-card {
            position: relative;
            overflow: hidden;
        }

        .media-preview {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            transition: var(--transition-slow);
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
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition-slow);
            border-radius: var(--radius-sm);
            padding: var(--space-md);
        }

        .media-card:hover .media-overlay {
            opacity: 1;
        }

        .media-actions {
            display: flex;
            gap: var(--space-sm);
            width: 100%;
            max-width: 200px;
        }

        .media-info {
            padding: var(--space-lg);
        }

        .media-name {
            font-weight: 600;
            margin-bottom: var(--space-sm);
            word-break: break-word;
            font-size: var(--text-sm);
            line-height: 1.4;
        }

        .media-meta {
            display: flex;
            justify-content: space-between;
            font-size: var(--text-xs);
            color: var(--text-light);
            flex-wrap: wrap;
            gap: var(--space-sm);
        }

        .file-icon {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: var(--space-md);
            font-size: 2.5rem;
            color: var(--primary);
        }

        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: var(--space-2xl) var(--space-lg);
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: var(--space-lg);
            opacity: 0.5;
        }

        /* === ALERT STYLES === */
        .alert {
            padding: var(--space-lg);
            border-radius: var(--radius-sm);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: flex-start;
            gap: var(--space-md);
            border-left: 4px solid transparent;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* === FILE TYPE BADGES === */
        .file-type-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: var(--space-xs) var(--space-md);
            background: var(--bg);
            border-radius: 20px;
            font-size: var(--text-xs);
            font-weight: 500;
            color: var(--text-light);
            border: 1px solid var(--border);
        }

        /* === LOADING STATES === */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* === ACCESSIBILITY === */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus styles for better accessibility */
        button:focus-visible,
        a:focus-visible,
        .file-upload-area:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* === RESPONSIVE DESIGN === */
        /* Tablet */
        @media (min-width: 768px) {
            :root {
                --container-padding: var(--space-lg);
                --text-2xl: 1.75rem;
            }

            .page-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-end;
            }

            .header-content h1 {
                font-size: var(--text-3xl);
            }

            .media-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: var(--space-xl);
            }

            .media-actions {
                flex-direction: row;
                max-width: none;
            }

            .form-content {
                padding: var(--space-2xl);
            }
        }

        /* Desktop */
        @media (min-width: 1024px) {
            :root {
                --container-padding: var(--space-xl);
            }

            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }

            .file-upload-area {
                padding: var(--space-2xl);
            }
        }

        /* Large Desktop */
        @media (min-width: 1200px) {
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }

        /* Small mobile adjustments */
        @media (max-width: 360px) {
            :root {
                --container-padding: var(--space-sm);
            }

            .form-content {
                padding: var(--space-lg);
            }

            .file-upload-area {
                padding: var(--space-xl) var(--space-md);
            }

            .media-info {
                padding: var(--space-md);
            }
        }

        /* === TOUCH DEVICE OPTIMIZATIONS === */
        @media (hover: none) {
            .media-overlay {
                opacity: 1;
                background: rgba(0, 0, 0, 0.6);
                padding: var(--space-sm);
            }

            .media-actions {
                flex-direction: column;
            }

            .btn:hover {
                transform: none;
            }

            .card:hover {
                transform: none;
                box-shadow: 0 2px 4px var(--shadow);
            }
        }

        /* === DARK MODE SUPPORT === */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #1a202c;
                --card-bg: #2d3748;
                --text: #f7fafc;
                --text-light: #cbd5e0;
                --text-muted: #a0aec0;
                --border: #4a5568;
                --shadow: rgba(0, 0, 0, 0.2);
                --shadow-md: rgba(0, 0, 0, 0.3);
            }

            .file-upload-area {
                background: #2d3748;
            }

            .form-hint {
                color: var(--text-muted);
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
            <div class="header-meta">
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
                <div>
                    <strong>Success!</strong> <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error!</strong> <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" class="upload-form card" id="uploadForm">
            <div class="form-header">
                <h3><i class="fas fa-upload"></i> Upload New Media</h3>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <div class="file-upload-area" id="dropZone" tabindex="0">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h4>Drag & Drop your file here</h4>
                        <p class="form-hint">or click to browse files</p>
                        <input type="file" name="file" class="file-input" id="fileInput" required 
                               accept="image/*,video/*,.pdf" aria-describedby="fileHelp">
                    </div>
                    <div id="fileHelp" class="form-hint">
                        <strong>Supported formats:</strong> Images (JPG, PNG, WebP), PDFs, Videos (MP4, MOV) • Max 10MB
                    </div>
                    <div id="filePreview" class="file-preview" style="display: none; margin-top: var(--space-lg);">
                        <div id="previewContent" style="text-align: center;"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                        <i class="fas fa-upload"></i> Upload File
                    </button>
                </div>
            </div>
        </form>

        <!-- Media Files Section -->
        <section class="media-section" aria-labelledby="media-heading">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
                <h2 id="media-heading" style="font-size: var(--text-xl); color: var(--text);">
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
                                     class="media-preview"
                                     loading="lazy">
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
                                       style="background: rgba(255,255,255,0.9); color: var(--text);"
                                       aria-label="View <?= htmlspecialchars($file['file_name']) ?>">
                                        <i class="fas fa-expand"></i> View
                                    </a>
                                    <a href="?delete=<?= $file['id'] ?>&id=<?= $programme_id ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this file? This action cannot be undone.')"
                                       aria-label="Delete <?= htmlspecialchars($file['file_name']) ?>">
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
        // Enhanced file upload with better mobile support
        class FileUploadManager {
            constructor() {
                this.dropZone = document.getElementById('dropZone');
                this.fileInput = document.getElementById('fileInput');
                this.previewContainer = document.getElementById('filePreview');
                this.previewContent = document.getElementById('previewContent');
                this.submitBtn = document.getElementById('submitBtn');
                this.uploadForm = document.getElementById('uploadForm');
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.setupAccessibility();
            }
            
            setupEventListeners() {
                // Drag and drop functionality
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, this.preventDefaults.bind(this), false);
                });
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, () => this.setDragState(true), false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, () => this.setDragState(false), false);
                });
                
                this.dropZone.addEventListener('drop', this.handleDrop.bind(this), false);
                this.dropZone.addEventListener('click', () => this.fileInput.click());
                this.dropZone.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.fileInput.click();
                    }
                });
                
                this.fileInput.addEventListener('change', this.handleFileSelect.bind(this));
                this.uploadForm.addEventListener('submit', this.handleFormSubmit.bind(this));
            }
            
            setupAccessibility() {
                this.dropZone.setAttribute('role', 'button');
                this.dropZone.setAttribute('aria-label', 'File upload area. Drag and drop files or click to browse.');
            }
            
            preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            setDragState(isDragging) {
                this.dropZone.classList.toggle('dragover', isDragging);
            }
            
            handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    this.fileInput.files = files;
                    this.handleFileSelect();
                }
            }
            
            handleFileSelect() {
                const file = this.fileInput.files[0];
                if (file) {
                    if (!this.validateFile(file)) {
                        return;
                    }
                    
                    this.showPreview(file);
                    this.updateDropZone(file);
                }
            }
            
            validateFile(file) {
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    this.showError('File size exceeds 10MB limit. Please choose a smaller file.');
                    this.fileInput.value = '';
                    return false;
                }
                
                // Check file type
                const allowedTypes = ['image/', 'video/', 'application/pdf'];
                const isValidType = allowedTypes.some(type => file.type.startsWith(type));
                
                if (!isValidType) {
                    this.showError('File type not supported. Please upload images, videos, or PDF files.');
                    this.fileInput.value = '';
                    return false;
                }
                
                return true;
            }
            
            showError(message) {
                // You could enhance this to show a proper toast notification
                alert(message);
            }
            
            showPreview(file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    let previewHTML = '';
                    
                    if (file.type.startsWith('image/')) {
                        previewHTML = `
                            <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: var(--radius-sm); object-fit: contain;">
                            <p style="margin-top: var(--space-sm); font-weight: 600; word-break: break-word;">${this.escapeHtml(file.name)}</p>
                            <p style="color: var(--text-light); font-size: var(--text-sm);">${this.formatFileSize(file.size)}</p>
                        `;
                    } else if (file.type.startsWith('video/')) {
                        previewHTML = `
                            <div style="background: var(--bg); padding: var(--space-xl); border-radius: var(--radius-sm); margin-bottom: var(--space-sm);">
                                <i class="fas fa-file-video" style="font-size: 2.5rem; color: var(--primary);"></i>
                            </div>
                            <p style="font-weight: 600; word-break: break-word;">${this.escapeHtml(file.name)}</p>
                            <p style="color: var(--text-light); font-size: var(--text-sm);">Video file • ${this.formatFileSize(file.size)}</p>
                        `;
                    } else if (file.type === 'application/pdf') {
                        previewHTML = `
                            <div style="background: var(--bg); padding: var(--space-xl); border-radius: var(--radius-sm); margin-bottom: var(--space-sm);">
                                <i class="fas fa-file-pdf" style="font-size: 2.5rem; color: var(--danger);"></i>
                            </div>
                            <p style="font-weight: 600; word-break: break-word;">${this.escapeHtml(file.name)}</p>
                            <p style="color: var(--text-light); font-size: var(--text-sm);">PDF document • ${this.formatFileSize(file.size)}</p>
                        `;
                    } else {
                        previewHTML = `
                            <div style="background: var(--bg); padding: var(--space-xl); border-radius: var(--radius-sm); margin-bottom: var(--space-sm);">
                                <i class="fas fa-file" style="font-size: 2.5rem; color: var(--text-light);"></i>
                            </div>
                            <p style="font-weight: 600; word-break: break-word;">${this.escapeHtml(file.name)}</p>
                            <p style="color: var(--text-light); font-size: var(--text-sm);">${file.type || 'Unknown file type'} • ${this.formatFileSize(file.size)}</p>
                        `;
                    }
                    
                    this.previewContent.innerHTML = previewHTML;
                    this.previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
            
            updateDropZone(file) {
                this.dropZone.innerHTML = `
                    <i class="fas fa-check-circle upload-icon" style="color: var(--success);"></i>
                    <h4>${this.escapeHtml(file.name)}</h4>
                    <p class="form-hint">${this.formatFileSize(file.size)} • Click to choose a different file</p>
                    <input type="file" name="file" class="file-input" id="fileInput" required 
                           accept="image/*,video/*,.pdf" aria-describedby="fileHelp">
                `;
                
                // Re-attach event listeners to the new file input
                const newFileInput = this.dropZone.querySelector('.file-input');
                newFileInput.addEventListener('change', this.handleFileSelect.bind(this));
                this.fileInput = newFileInput;
            }
            
            async handleFormSubmit(e) {
                const file = this.fileInput.files[0];
                if (!file) {
                    e.preventDefault();
                    this.showError('Please select a file to upload.');
                    return;
                }
                
                if (!this.validateFile(file)) {
                    e.preventDefault();
                    return;
                }
                
                // Show loading state
                this.submitBtn.classList.add('loading');
                this.submitBtn.disabled = true;
                this.submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Uploading...';
                
                // For better UX, you could add a progress bar here
            }
            
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new FileUploadManager();
        });
        
        // Add touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>