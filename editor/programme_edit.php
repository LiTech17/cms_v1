<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkEditorLogin(); // Editor-specific authentication

$programme_id = $_GET['id'] ?? null;
if (!$programme_id) {
    header("Location: programmes.php");
    exit();
}

// Fetch programme details
$stmt = $pdo->prepare("SELECT * FROM programmes WHERE id = ?");
$stmt->execute([$programme_id]);
$programme = $stmt->fetch();

if (!$programme) {
    die("Programme not found.");
}

// Fetch programme statistics
$stats_stmt = $pdo->prepare("SELECT * FROM programme_statistics WHERE programme_id = ? ORDER BY display_order");
$stats_stmt->execute([$programme_id]);
$statistics = $stats_stmt->fetchAll();

// Fetch programme media
$media_stmt = $pdo->prepare("SELECT * FROM programme_media WHERE programme_id = ? ORDER BY uploaded_at DESC");
$media_stmt->execute([$programme_id]);
$programme_media = $media_stmt->fetchAll();

$error = null;
$success = null;

// Handle programme update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_programme'])) {
    try {
        $title = trim($_POST['title']);
        $introduction = trim($_POST['introduction']);
        $objectives = trim($_POST['objectives']);
        $key_achievements = trim($_POST['key_achievements']);
        $target_beneficiaries = trim($_POST['target_beneficiaries']);
        $duration = trim($_POST['duration']);
        $status = $_POST['status'];
        $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : null;
        $location = trim($_POST['location']);
        $partner_organizations = trim($_POST['partner_organizations']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Validate required fields
        if (empty($title) || empty($introduction)) {
            throw new Exception("Title and introduction are required.");
        }

        // Update programme
        $stmt = $pdo->prepare("UPDATE programmes SET title = ?, introduction = ?, objectives = ?, key_achievements = ?, target_beneficiaries = ?, duration = ?, status = ?, budget = ?, location = ?, partner_organizations = ?, is_featured = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $introduction, $objectives, $key_achievements, $target_beneficiaries, $duration, $status, $budget, $location, $partner_organizations, $is_featured, $programme_id]);

        // Handle statistics
        if (isset($_POST['statistics']) && is_array($_POST['statistics'])) {
            // First, delete existing statistics
            $pdo->prepare("DELETE FROM programme_statistics WHERE programme_id = ?")->execute([$programme_id]);
            
            // Insert updated statistics
            foreach ($_POST['statistics'] as $stat) {
                if (!empty($stat['name']) && !empty($stat['value'])) {
                    $stmt = $pdo->prepare("INSERT INTO programme_statistics (programme_id, statistic_name, statistic_value, statistic_icon, display_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$programme_id, trim($stat['name']), trim($stat['value']), $stat['icon'] ?? 'fas fa-chart-line', $stat['order'] ?? 0]);
                }
            }
        }

        $success = "Programme updated successfully!";
        
        // Refresh programme data
        $stmt = $pdo->prepare("SELECT * FROM programmes WHERE id = ?");
        $stmt->execute([$programme_id]);
        $programme = $stmt->fetch();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_image'])) {
    try {
        $file = uploadFile($_FILES['new_image']);
        if ($file) {
            $stmt = $pdo->prepare("INSERT INTO programme_media (programme_id, file_name, file_type) VALUES (?, ?, ?)");
            $stmt->execute([$programme_id, $file, $_FILES['new_image']['type']]);
            $success = "Image uploaded successfully!";
            header("Location: programme_edit.php?id=$programme_id");
            exit();
        } else {
            $error = "Image upload failed. Please check file type and size.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle image deletion
if (isset($_GET['delete_image'])) {
    $image_id = $_GET['delete_image'];
    
    // Get file info before deletion
    $stmt = $pdo->prepare("SELECT file_name FROM programme_media WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    
    if ($image) {
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM programme_media WHERE id = ?");
        $stmt->execute([$image_id]);
        
        // Delete physical file
        $file_path = "../uploads/" . $image['file_name'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $success = "Image deleted successfully!";
        header("Location: programme_edit.php?id=$programme_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= htmlspecialchars($programme['title']) ?> | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === MOBILE-FIRST VARIABLES === */
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #dbeafe;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --radius-sm: 6px;
            --radius: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --transition: all 0.2s ease-in-out;
        }

        /* === BASE STYLES === */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            line-height: 1.6;
            padding: 1rem;
            min-height: 100vh;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
        }

        /* === HEADER STYLES === */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
        }

        .header-content h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .header-content h1 i {
            color: var(--primary);
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* === ALERTS === */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
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

        /* === TABS === */
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            flex-shrink: 0;
            min-height: 48px;
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab:hover {
            color: var(--primary);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* === CARDS === */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem;
            background: var(--primary);
            color: var(--white);
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* === FORM ELEMENTS === */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-required::after {
            content: " *";
            color: var(--danger);
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
            -webkit-appearance: none;
            min-height: 48px;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .checkbox {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        /* === STATISTICS === */
        .statistic-item {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1.25rem;
            background: var(--bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .statistic-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
        }

        /* === MEDIA GALLERY === */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .media-item {
            position: relative;
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--bg);
            aspect-ratio: 1;
        }

        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        }

        .media-item:hover .media-overlay {
            opacity: 1;
        }

        .media-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* === BUTTONS === */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid transparent;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            min-height: 44px;
            -webkit-tap-highlight-color: transparent;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border-color: var(--border);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
            border-color: var(--success);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
            border-color: var(--danger);
            padding: 0.5rem;
            min-width: 44px;
            min-height: 44px;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-height: 36px;
        }

        /* === FORM ACTIONS === */
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--border);
        }

        .form-actions .btn {
            width: 100%;
        }

        /* === EMPTY STATES === */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 2px dashed var(--border);
            margin: 2rem 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        /* === LOADING STATES === */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* === TABLET STYLES === */
        @media (min-width: 768px) {
            body {
                padding: 1.5rem;
            }

            .container {
                max-width: 1000px;
            }

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
            }

            .header-actions {
                flex-direction: row;
                align-items: center;
            }

            .form-row {
                grid-template-columns: 1fr 1fr;
            }

            .statistic-item {
                grid-template-columns: 1fr 1fr auto;
                gap: 1rem;
            }

            .statistic-actions {
                justify-content: flex-end;
            }

            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }

            .form-actions {
                flex-direction: row;
                justify-content: flex-end;
            }

            .form-actions .btn {
                width: auto;
            }
        }

        /* === DESKTOP STYLES === */
        @media (min-width: 1024px) {
            .container {
                max-width: 1200px;
            }

            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }

        /* === ACCESSIBILITY === */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="header-content">
                <a href="programmes.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Programmes
                </a>
                <h1>
                    <i class="fas fa-edit"></i>
                    Edit Programme
                </h1>
                <p class="page-subtitle">Update programme details, statistics, and media</p>
            </div>
            <div class="header-actions">
                <a href="../public/programme_detail.php?id=<?= $programme_id ?>" class="btn btn-outline" target="_blank">
                    <i class="fas fa-eye"></i> View Public Page
                </a>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong><br>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-error" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Attention Needed</strong><br>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('details')" aria-selected="true">
                <i class="fas fa-info-circle"></i> Details
            </button>
            <button class="tab" onclick="showTab('statistics')" aria-selected="false">
                <i class="fas fa-chart-bar"></i> Statistics
            </button>
            <button class="tab" onclick="showTab('media')" aria-selected="false">
                <i class="fas fa-images"></i> Media
            </button>
        </div>

        <!-- Details Tab -->
        <div id="details-tab" class="tab-content active">
            <form method="POST" class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Programme Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label form-required" for="title">Programme Title</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= htmlspecialchars($programme['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-required" for="introduction">Introduction</label>
                        <textarea id="introduction" name="introduction" class="form-textarea" required><?= htmlspecialchars($programme['introduction']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="objectives">Objectives</label>
                        <textarea id="objectives" name="objectives" class="form-textarea"><?= htmlspecialchars($programme['objectives'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="key_achievements">Key Achievements</label>
                        <textarea id="key_achievements" name="key_achievements" class="form-textarea"><?= htmlspecialchars($programme['key_achievements'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="target_beneficiaries">Target Beneficiaries</label>
                            <input type="text" id="target_beneficiaries" name="target_beneficiaries" class="form-input" 
                                   value="<?= htmlspecialchars($programme['target_beneficiaries'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-input" 
                                   value="<?= htmlspecialchars($programme['duration'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-required" for="status">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" <?= $programme['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $programme['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="upcoming" <?= $programme['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="budget">Budget ($)</label>
                            <input type="number" id="budget" name="budget" class="form-input" 
                                   value="<?= $programme['budget'] ?>" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-input" 
                               value="<?= htmlspecialchars($programme['location'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="partner_organizations">Partner Organizations</label>
                        <textarea id="partner_organizations" name="partner_organizations" class="form-textarea"><?= htmlspecialchars($programme['partner_organizations'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" 
                                   class="checkbox" <?= $programme['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured" class="form-label">Feature on homepage</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_programme" class="btn btn-primary" id="saveDetails">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Tab -->
        <div id="statistics-tab" class="tab-content">
            <form method="POST" class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Impact Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <label class="form-label">Programme Statistics</label>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addStatistic()">
                                <i class="fas fa-plus"></i> Add Statistic
                            </button>
                        </div>
                        
                        <div id="statistics-container" aria-live="polite">
                            <?php foreach($statistics as $index => $stat): ?>
                                <div class="statistic-item" data-id="<?= $index ?>">
                                    <input type="text" name="statistics[<?= $index ?>][name]" 
                                           class="form-input" placeholder="Statistic name" 
                                           value="<?= htmlspecialchars($stat['statistic_name']) ?>" required>
                                    <input type="text" name="statistics[<?= $index ?>][value]" 
                                           class="form-input" placeholder="Statistic value" 
                                           value="<?= htmlspecialchars($stat['statistic_value']) ?>" required>
                                    <div class="statistic-actions">
                                        <select name="statistics[<?= $index ?>][icon]" class="form-select">
                                            <?php
                                            $iconOptions = [
                                                'fas fa-users', 'fas fa-map-marker-alt', 'fas fa-chart-line', 
                                                'fas fa-graduation-cap', 'fas fa-heart', 'fas fa-home'
                                            ];
                                            foreach ($iconOptions as $iconOption): ?>
                                                <option value="<?= $iconOption ?>" <?= $stat['statistic_icon'] === $iconOption ? 'selected' : '' ?>>
                                                    <?= str_replace('fas fa-', '', $iconOption) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="statistics[<?= $index ?>][order]" value="<?= $stat['display_order'] ?>">
                                        <button type="button" class="btn btn-danger" onclick="removeStatistic(<?= $index ?>)" aria-label="Remove statistic">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_programme" class="btn btn-primary" id="saveStatistics">
                            <i class="fas fa-save"></i> Update Statistics
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Media Tab -->
        <div id="media-tab" class="tab-content">
            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3><i class="fas fa-upload"></i> Upload Image</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Select Image</label>
                        <input type="file" name="new_image" class="form-input" accept="image/*" required>
                        <div class="form-hint">JPG, PNG, WebP • Max 5MB</div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>
                    </div>
                </div>
            </form>

            <!-- Media Gallery -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-images"></i> Programme Images (<?= count($programme_media) ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($programme_media)): ?>
                        <div class="media-grid">
                            <?php foreach($programme_media as $media): ?>
                                <div class="media-item">
                                    <img src="../uploads/<?= htmlspecialchars($media['file_name']) ?>" 
                                         alt="Programme image">
                                    <div class="media-overlay">
                                        <div class="media-actions">
                                            <a href="../uploads/<?= htmlspecialchars($media['file_name']) ?>" 
                                               target="_blank" class="btn btn-sm" 
                                               style="background: rgba(255,255,255,0.9); color: var(--text);"
                                               aria-label="View full size">
                                                <i class="fas fa-expand"></i>
                                            </a>
                                            <a href="?delete_image=<?= $media['id'] ?>&id=<?= $programme_id ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this image?')"
                                               aria-label="Delete image">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-images"></i>
                            <h3>No Images Yet</h3>
                            <p>Upload images to showcase your programme</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
                tab.setAttribute('aria-selected', 'false');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            event.target.setAttribute('aria-selected', 'true');
        }

        // Statistics functionality
        let statisticCount = <?= count($statistics) ?>;
        const iconOptions = [
            'fas fa-users', 'fas fa-map-marker-alt', 'fas fa-chart-line', 
            'fas fa-graduation-cap', 'fas fa-heart', 'fas fa-home'
        ];

        function addStatistic(name = '', value = '', icon = 'fas fa-chart-line') {
            const container = document.getElementById('statistics-container');
            const id = statisticCount++;
            
            const statisticHTML = `
                <div class="statistic-item" data-id="${id}">
                    <input type="text" name="statistics[${id}][name]" 
                           class="form-input" placeholder="Statistic name" 
                           value="${name}" required>
                    <input type="text" name="statistics[${id}][value]" 
                           class="form-input" placeholder="Statistic value" 
                           value="${value}" required>
                    <div class="statistic-actions">
                        <select name="statistics[${id}][icon]" class="form-select">
                            ${iconOptions.map(iconOption => `
                                <option value="${iconOption}" ${icon === iconOption ? 'selected' : ''}>
                                    ${iconOption.replace('fas fa-', '')}
                                </option>
                            `).join('')}
                        </select>
                        <input type="hidden" name="statistics[${id}][order]" value="${id}">
                        <button type="button" class="btn btn-danger" onclick="removeStatistic(${id})" aria-label="Remove statistic">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', statisticHTML);
            
            // Announce to screen readers
            const announcement = document.createElement('div');
            announcement.className = 'sr-only';
            announcement.textContent = 'New statistic added';
            container.appendChild(announcement);
            setTimeout(() => announcement.remove(), 1000);
        }

        function removeStatistic(id) {
            const element = document.querySelector(`.statistic-item[data-id="${id}"]`);
            if (element) {
                element.style.opacity = '0';
                element.style.transform = 'translateX(-10px)';
                setTimeout(() => element.remove(), 300);
            }
        }

        // Enhanced form handling
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            const buttons = document.querySelectorAll('button[type="submit"]');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    this.classList.add('loading');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                });
            });

            // Enhanced touch interactions for mobile
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }

            // Auto-add empty statistic if none exist
            <?php if(empty($statistics)): ?>
                addStatistic('Lives Impacted', '1500+', 'fas fa-users');
                addStatistic('Success Rate', '95%', 'fas fa-chart-line');
            <?php endif; ?>
        });

        // Keyboard navigation for tabs
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                const tabs = Array.from(document.querySelectorAll('.tab'));
                const currentTab = document.querySelector('.tab.active');
                const currentIndex = tabs.indexOf(currentTab);
                
                let nextIndex;
                if (e.key === 'ArrowRight') {
                    nextIndex = (currentIndex + 1) % tabs.length;
                } else {
                    nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                }
                
                tabs[nextIndex].click();
                tabs[nextIndex].focus();
            }
        });
    </script>
</body>
</html>