<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $background = trim($_POST['background'] ?? '');
        
        // Validate required fields
        if (empty($name) || empty($position)) {
            throw new Exception("Name and Position are required fields.");
        }
        
        $image = uploadFile($_FILES['image']);
        if (!$image) {
            throw new Exception("Please upload a valid image file.");
        }
        
        $stmt = $pdo->prepare("INSERT INTO leadership_team (name, position, qualification, background, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $position, $qualification, $background, $image]);
        
        $_SESSION['success_message'] = "✅ Leader added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM leadership_team WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success_message'] = "🗑️ Team member deleted successfully!";
    header("Location: leadership_team.php");
    exit();
}

$leaders = $pdo->query("SELECT * FROM leadership_team ORDER BY id DESC")->fetchAll();

// Check for success message from redirect
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership Team Manager | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --danger: #e53e3e;
            --danger-dark: #c53030;
            --success: #38a169;
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
        }

        .header-content h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-content h1 i {
            color: var(--primary);
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            margin: 0;
        }

        .stats-badge {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Form Styles */
        .add-leader-form {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .form-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }

        .form-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }

        .form-content {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-size: 0.95rem;
        }

        .form-label.required::after {
            content: " *";
            color: #e53e3e;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: white;
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
        }

        .file-input {
            display: none;
        }

        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
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
        }

        .upload-icon {
            font-size: 2rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.9rem;
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
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        /* Team Grid */
        .team-section {
            margin-top: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .leader-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px var(--shadow);
            border: 1px solid var(--border);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
        }

        .leader-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-md);
        }

        .leader-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .leader-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border);
            flex-shrink: 0;
        }

        .leader-info {
            flex: 1;
        }

        .leader-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 0.25rem 0;
        }

        .leader-position {
            color: var(--primary);
            font-weight: 500;
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
        }

        .leader-qualification {
            color: var(--text-light);
            font-size: 0.9rem;
            margin: 0;
        }

        .leader-background {
            color: var(--text);
            line-height: 1.5;
            font-size: 0.9rem;
            margin: 0;
        }

        .leader-background:empty::before {
            content: "No background information provided";
            color: var(--text-muted);
            font-style: italic;
        }

        .leader-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            padding: 0.5rem;
            border-radius: var(--radius-sm);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-danger {
            color: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
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

        /* Character Counter */
        .char-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.25rem;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }

            .form-content {
                padding: 1.25rem;
            }

            .leader-header {
                flex-direction: column;
                text-align: center;
            }

            .leader-image {
                align-self: center;
            }
        }

        /* Animation */
        .fade-in {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="admin-container fade-in">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-users"></i>
                    Leadership Team
                </h1>
                <p class="page-subtitle">Manage your organization's leadership team members and their profiles</p>
            </div>
            <div class="stats-badge">
                <?= count($leaders) ?> Member<?= count($leaders) !== 1 ? 's' : '' ?>
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

        <!-- Add Leader Form -->
        <form method="POST" enctype="multipart/form-data" class="add-leader-form">
            <div class="form-header">
                <h2><i class="fas fa-user-plus"></i> Add New Team Member</h2>
            </div>
            <div class="form-content">
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="name" class="form-label required">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" class="form-input" 
                               placeholder="Enter full name" required
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="position" class="form-label required">
                            <i class="fas fa-briefcase"></i> Position
                        </label>
                        <input type="text" id="position" name="position" class="form-input" 
                               placeholder="Enter position title" required
                               value="<?= htmlspecialchars($_POST['position'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="qualification" class="form-label">
                            <i class="fas fa-graduation-cap"></i> Qualifications
                        </label>
                        <input type="text" id="qualification" name="qualification" class="form-input" 
                               placeholder="Enter qualifications, degrees, certifications"
                               value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
                    </div>

                    <!-- Image Upload -->
                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-camera"></i> Profile Photo
                        </label>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-area" id="dropZone">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem;">Upload Profile Photo</h4>
                                <p class="form-hint" style="margin: 0;">Drag & drop or click to browse</p>
                                <input type="file" name="image" class="file-input" id="imageInput" accept="image/*" required>
                            </div>
                        </div>
                        <div class="form-hint">
                            <strong>Recommended:</strong> Square image • JPG, PNG, or WebP • Max 2MB
                        </div>
                    </div>

                    <!-- Background -->
                    <div class="form-group full-width">
                        <label for="background" class="form-label">
                            <i class="fas fa-history"></i> Professional Background
                        </label>
                        <textarea id="background" name="background" class="form-textarea" 
                                  placeholder="Describe professional experience, achievements, and background..."
                                  maxlength="1000"><?= htmlspecialchars($_POST['background'] ?? '') ?></textarea>
                        <div class="char-count" id="backgroundCount">0/1000 characters</div>
                        <div class="form-hint">
                            Optional: Share professional experience, key achievements, and career background
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions" style="text-align: right; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Team Member
                    </button>
                </div>
            </div>
        </form>

        <!-- Team Members Section -->
        <section class="team-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-users"></i>
                    Team Members
                </h2>
            </div>

            <?php if (empty($leaders)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No Team Members Yet</h3>
                    <p>Add your first leadership team member to showcase your organization's leadership.</p>
                </div>
            <?php else: ?>
                <div class="team-grid">
                    <?php foreach($leaders as $leader): ?>
                        <div class="leader-card">
                            <div class="leader-actions">
                                <a href="?delete=<?= $leader['id'] ?>" 
                                   class="btn-icon btn-danger"
                                   onclick="return confirm('Are you sure you want to delete <?= addslashes($leader['name']) ?>? This action cannot be undone.')"
                                   title="Delete Member">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>

                            <div class="leader-header">
                                <img src="../uploads/<?= htmlspecialchars($leader['image']) ?>" 
                                     alt="<?= htmlspecialchars($leader['name']) ?>" 
                                     class="leader-image">
                                <div class="leader-info">
                                    <h3 class="leader-name"><?= htmlspecialchars($leader['name']) ?></h3>
                                    <p class="leader-position"><?= htmlspecialchars($leader['position']) ?></p>
                                    <?php if(!empty($leader['qualification'])): ?>
                                        <p class="leader-qualification"><?= htmlspecialchars($leader['qualification']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if(!empty($leader['background'])): ?>
                                <p class="leader-background"><?= nl2br(htmlspecialchars($leader['background'])) ?></p>
                            <?php else: ?>
                                <p class="leader-background"></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Enhanced file upload with drag & drop
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('imageInput');

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
            if (file && file.type.startsWith('image/')) {
                dropZone.innerHTML = `
                    <i class="fas fa-check-circle upload-icon" style="color: #38a169;"></i>
                    <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem;">${file.name}</h4>
                    <p class="form-hint" style="margin: 0;">Click to choose a different photo</p>
                `;
            }
        }

        // Character counter for background
        const backgroundTextarea = document.getElementById('background');
        const backgroundCounter = document.getElementById('backgroundCount');

        function updateCharacterCount() {
            const length = backgroundTextarea.value.length;
            backgroundCounter.textContent = `${length}/1000 characters`;
            
            // Update color based on usage
            backgroundCounter.className = 'char-count';
            if (length > 800) {
                backgroundCounter.style.color = '#dd6b20';
            }
            if (length > 950) {
                backgroundCounter.style.color = '#e53e3e';
            }
        }

        backgroundTextarea.addEventListener('input', updateCharacterCount);
        updateCharacterCount(); // Initialize counter

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const position = document.getElementById('position').value.trim();
            const image = document.getElementById('imageInput').files[0];
            
            if (!name || !position) {
                e.preventDefault();
                alert('Please fill in all required fields (Name and Position).');
                if (!name) document.getElementById('name').focus();
                else document.getElementById('position').focus();
                return false;
            }
            
            if (!image) {
                e.preventDefault();
                alert('Please upload a profile photo.');
                document.getElementById('imageInput').focus();
                return false;
            }
        });
    </script>
</body>
</html>