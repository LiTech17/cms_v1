<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkLogin();

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
        if (empty($title)) {
            throw new Exception("Programme title is required.");
        }

        if (empty($introduction)) {
            throw new Exception("Programme introduction is required.");
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

        $success = "✅ Programme updated successfully!";
        
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
            $success = "✅ Image uploaded successfully!";
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
        
        $success = "🗑️ Image deleted successfully!";
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
        /* Reuse the same styles from create.php with some additions */
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --success: #38a169;
            --warning: #d69e2e;
            --danger: #e53e3e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
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
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .header-content h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-content h1 i {
            color: var(--primary);
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-top: 0.5rem;
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

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border);
        }

        .tab {
            padding: 1rem 2rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
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
        }

        .tab-content.active {
            display: block;
        }

        /* Form Styles (reused from create.php) */
        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 2rem;
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

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .checkbox {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border);
            border-radius: 4px;
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Media Grid */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg);
        }

        .media-item img {
            width: 100%;
            height: 150px;
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

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-block {
            width: 100%;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
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

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .tabs {
                flex-direction: column;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .form-actions {
                flex-direction: column;
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
                    <i class="fas fa-edit"></i>
                    Edit <?= htmlspecialchars($programme['title']) ?>
                </h1>
                <p class="page-subtitle">Update programme details, statistics, and media</p>
            </div>
            <div>
                <a href="../public/programme_detail.php?id=<?= $programme_id ?>" class="btn btn-outline" target="_blank">
                    <i class="fas fa-eye"></i> View Public Page
                </a>
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

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('details')">Programme Details</button>
            <button class="tab" onclick="showTab('statistics')">Impact Statistics</button>
            <button class="tab" onclick="showTab('media')">Media Gallery</button>
        </div>

        <!-- Details Tab -->
        <div id="details-tab" class="tab-content active">
            <form method="POST" class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                <div class="form-content">
                    <div class="form-group">
                        <label for="title" class="form-label">Programme Title *</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= htmlspecialchars($programme['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="introduction" class="form-label">Programme Introduction *</label>
                        <textarea id="introduction" name="introduction" class="form-textarea" required><?= htmlspecialchars($programme['introduction']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="objectives" class="form-label">Key Objectives</label>
                        <textarea id="objectives" name="objectives" class="form-textarea"><?= htmlspecialchars($programme['objectives'] ?? '') ?></textarea>
                        <div class="form-hint">Separate each objective with a new line</div>
                    </div>

                    <div class="form-group">
                        <label for="key_achievements" class="form-label">Key Achievements</label>
                        <textarea id="key_achievements" name="key_achievements" class="form-textarea"><?= htmlspecialchars($programme['key_achievements'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
                            <input type="text" id="target_beneficiaries" name="target_beneficiaries" class="form-input" 
                                   value="<?= htmlspecialchars($programme['target_beneficiaries'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="duration" class="form-label">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-input" 
                                   value="<?= htmlspecialchars($programme['duration'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" <?= $programme['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $programme['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="upcoming" <?= $programme['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="budget" class="form-label">Budget ($)</label>
                            <input type="number" id="budget" name="budget" class="form-input" 
                                   value="<?= $programme['budget'] ?>" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" id="location" name="location" class="form-input" 
                               value="<?= htmlspecialchars($programme['location'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="partner_organizations" class="form-label">Partner Organizations</label>
                        <textarea id="partner_organizations" name="partner_organizations" class="form-textarea"><?= htmlspecialchars($programme['partner_organizations'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" 
                                   class="checkbox" <?= $programme['is_featured'] ? 'checked' : '' ?>>
                            <label for="is_featured" class="form-label">Feature this programme on the homepage</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_programme" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Programme
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics Tab -->
        <div id="statistics-tab" class="tab-content">
            <form method="POST" class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-chart-bar"></i> Impact Statistics</h3>
                </div>
                <div class="form-content">
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <label class="form-label">Programme Statistics</label>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addStatistic()">
                                <i class="fas fa-plus"></i> Add Statistic
                            </button>
                        </div>
                        
                        <div id="statistics-container">
                            <?php foreach($statistics as $index => $stat): ?>
                                <div class="form-row" style="margin-bottom: 1rem; align-items: end;">
                                    <div class="form-group">
                                        <input type="text" name="statistics[<?= $index ?>][name]" 
                                               class="form-input" placeholder="Statistic name" 
                                               value="<?= htmlspecialchars($stat['statistic_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="text" name="statistics[<?= $index ?>][value]" 
                                               class="form-input" placeholder="Statistic value" 
                                               value="<?= htmlspecialchars($stat['statistic_value']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <select name="statistics[<?= $index ?>][icon]" class="form-select">
                                            <?php
                                            $iconOptions = [
                                                'fas fa-users', 'fas fa-map-marker-alt', 'fas fa-chart-line', 'fas fa-tasks',
                                                'fas fa-dollar-sign', 'fas fa-graduation-cap', 'fas fa-heart', 'fas fa-home',
                                                'fas fa-school', 'fas fa-hand-holding-heart', 'fas fa-briefcase', 'fas fa-seedling'
                                            ];
                                            foreach ($iconOptions as $iconOption): ?>
                                                <option value="<?= $iconOption ?>" <?= $stat['statistic_icon'] === $iconOption ? 'selected' : '' ?>>
                                                    <?= str_replace('fas fa-', '', str_replace('-', ' ', $iconOption)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <input type="hidden" name="statistics[<?= $index ?>][order]" value="<?= $stat['display_order'] ?>">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.form-row').remove()">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_programme" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Statistics
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Media Tab -->
        <div id="media-tab" class="tab-content">
            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="form-card" style="margin-bottom: 2rem;">
                <div class="form-header">
                    <h3><i class="fas fa-upload"></i> Upload New Image</h3>
                </div>
                <div class="form-content">
                    <div class="form-group">
                        <label class="form-label">Select Image</label>
                        <input type="file" name="new_image" class="form-input" accept="image/*" required>
                        <div class="form-hint">Supported formats: JPG, PNG, WebP • Max 5MB</div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>
                    </div>
                </div>
            </form>

            <!-- Media Gallery -->
            <div class="form-card">
                <div class="form-header">
                    <h3><i class="fas fa-images"></i> Programme Images (<?= count($programme_media) ?>)</h3>
                </div>
                <div class="form-content">
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
                                               style="background: rgba(255,255,255,0.9); color: var(--text);">
                                                <i class="fas fa-expand"></i>
                                            </a>
                                            <a href="?delete_image=<?= $media['id'] ?>&id=<?= $programme_id ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this image?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-light);">
                            <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No images uploaded yet</p>
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
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Statistics functionality
        let statisticCount = <?= count($statistics) ?>;
        const iconOptions = [
            'fas fa-users', 'fas fa-map-marker-alt', 'fas fa-chart-line', 'fas fa-tasks',
            'fas fa-dollar-sign', 'fas fa-graduation-cap', 'fas fa-heart', 'fas fa-home',
            'fas fa-school', 'fas fa-hand-holding-heart', 'fas fa-briefcase', 'fas fa-seedling'
        ];

        function addStatistic() {
            const container = document.getElementById('statistics-container');
            const id = statisticCount++;
            
            const statisticHTML = `
                <div class="form-row" style="margin-bottom: 1rem; align-items: end;">
                    <div class="form-group">
                        <input type="text" name="statistics[${id}][name]" 
                               class="form-input" placeholder="Statistic name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="statistics[${id}][value]" 
                               class="form-input" placeholder="Statistic value" required>
                    </div>
                    <div class="form-group">
                        <select name="statistics[${id}][icon]" class="form-select">
                            ${iconOptions.map(iconOption => `
                                <option value="${iconOption}">
                                    ${iconOption.replace('fas fa-', '').replace(/-/g, ' ')}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div>
                        <input type="hidden" name="statistics[${id}][order]" value="0">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.form-row').remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', statisticHTML);
        }
    </script>
</body>
</html>