<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkLogin();

$stmt = $pdo->query("SELECT * FROM about_page LIMIT 1");
$about = $stmt->fetch();

// Also fetch organization info for fallback
$org = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $story = $_POST['establishment_story'] ?? '';
    $mission = $_POST['mission'] ?? '';
    $vision = $_POST['vision'] ?? '';
    $core_values = $_POST['core_values'] ?? '';
    $year_established = $_POST['year_established'] ?? '';
    $lives_impacted = $_POST['lives_impacted'] ?? '';
    $communities_served = $_POST['communities_served'] ?? '';
    $active_programs = $_POST['active_programs'] ?? '';
    
    $image = $_FILES['hero_image']['name'] ? uploadFile($_FILES['hero_image']) : ($about['hero_image'] ?? '');
    
    // Check if record exists
    if ($about) {
        $stmt = $pdo->prepare("UPDATE about_page SET 
            hero_image=?, 
            establishment_story=?, 
            mission=?, 
            vision=?, 
            core_values=?,
            year_established=?,
            lives_impacted=?,
            communities_served=?,
            active_programs=?
            WHERE id=1");
        $stmt->execute([
            $image, $story, $mission, $vision, $core_values,
            $year_established, $lives_impacted, $communities_served, $active_programs
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO about_page 
            (hero_image, establishment_story, mission, vision, core_values, year_established, lives_impacted, communities_served, active_programs) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $image, $story, $mission, $vision, $core_values,
            $year_established, $lives_impacted, $communities_served, $active_programs
        ]);
    }
    
    $success = "About page updated successfully!";
    $stmt = $pdo->query("SELECT * FROM about_page LIMIT 1");
    $about = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Page Manager | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --accent: #06b6d4;
            --success: #10b981;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 16px;
            --radius-sm: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--border);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .page-title i {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid transparent;
            animation: slideInDown 0.4s ease-out;
        }

        .alert-success {
            background: #f0fdf4;
            border-left-color: var(--success);
            color: #166534;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .form-wrapper {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .section-divider {
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .section-divider i {
            font-size: 1.5rem;
        }

        .form-section {
            padding: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text);
            font-size: 0.95rem;
        }

        .form-label i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            background: white;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }

        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        .char-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.5rem;
        }

        .image-preview {
            margin-bottom: 1.5rem;
            text-align: center;
            padding: 1rem;
            background: var(--bg);
            border-radius: var(--radius-sm);
        }

        .current-image {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            border: 3px solid white;
        }

        .image-info {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--bg);
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.02);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
            transform: scale(1.01);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stat-input-group {
            display: flex;
            flex-direction: column;
        }

        .form-actions {
            padding: 2rem;
            background: var(--bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--text);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg);
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
                flex-direction: column;
            }

            .form-section {
                padding: 1.5rem;
            }

            .section-divider {
                padding: 1rem 1.5rem;
            }

            .form-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
                padding: 1.5rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="admin-container fade-in">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-info-circle"></i>
                About Page Manager
            </h1>
            <p class="page-subtitle">Manage your organization's complete about page content</p>
        </div>

        <!-- Success Message -->
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="form-wrapper">
                <!-- Hero Section -->
                <div class="section-divider">
                    <i class="fas fa-image"></i>
                    <span>Hero Section</span>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-camera"></i>
                            Hero Image
                        </label>
                        
                        <?php if(!empty($about['hero_image'])): ?>
                            <div class="image-preview">
                                <img src="../uploads/<?= htmlspecialchars($about['hero_image']) ?>" 
                                     alt="Current Hero Image" class="current-image">
                                <div class="image-info">
                                    📁 <?= htmlspecialchars($about['hero_image']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-upload-area" id="dropZone">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem; font-weight: 600;">
                                Drag & Drop your image here
                            </h4>
                            <p style="color: var(--text-light); margin: 0;">or click to browse</p>
                            <input type="file" name="hero_image" class="file-input" id="heroImageInput" accept="image/*">
                        </div>
                        <p class="form-hint">
                            <strong>Recommended:</strong> 1920x600px • JPG, PNG, or WebP • Max 5MB
                        </p>
                    </div>
                </div>

                <!-- Mission & Vision -->
                <div class="section-divider">
                    <i class="fas fa-bullseye"></i>
                    <span>Mission & Vision</span>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="mission" class="form-label">
                            <i class="fas fa-crosshairs"></i>
                            Mission Statement
                            <span class="required">*</span>
                        </label>
                        <textarea id="mission" name="mission" class="form-textarea" 
                                  placeholder="What is your organization's purpose and how do you achieve it?"
                                  maxlength="500" required><?= htmlspecialchars($about['mission'] ?? $org['mission'] ?? '') ?></textarea>
                        <div class="char-count" data-counter="mission">0/500 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="vision" class="form-label">
                            <i class="fas fa-eye"></i>
                            Vision Statement
                            <span class="required">*</span>
                        </label>
                        <textarea id="vision" name="vision" class="form-textarea" 
                                  placeholder="What future do you envision? What's your long-term aspiration?"
                                  maxlength="500" required><?= htmlspecialchars($about['vision'] ?? $org['vision'] ?? '') ?></textarea>
                        <div class="char-count" data-counter="vision">0/500 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="core_values" class="form-label">
                            <i class="fas fa-heart"></i>
                            Core Values
                        </label>
                        <textarea id="core_values" name="core_values" class="form-textarea" 
                                  placeholder="What principles guide your work? (e.g., Integrity, Compassion, Sustainability...)"
                                  maxlength="500"><?= htmlspecialchars($about['core_values'] ?? $org['core_values'] ?? '') ?></textarea>
                        <div class="char-count" data-counter="core_values">0/500 characters</div>
                        <p class="form-hint">List your organization's guiding principles and values</p>
                    </div>
                </div>

                <!-- Organization Story -->
                <div class="section-divider">
                    <i class="fas fa-book"></i>
                    <span>Our Story</span>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="establishment_story" class="form-label">
                            <i class="fas fa-book-open"></i>
                            Establishment & Journey
                        </label>
                        <textarea id="establishment_story" name="establishment_story" class="form-textarea" 
                                  style="min-height: 200px;"
                                  placeholder="Share your organization's founding story, journey, key milestones, and ongoing commitment..."
                                  maxlength="2000"><?= htmlspecialchars($about['establishment_story'] ?? $org['history'] ?? '') ?></textarea>
                        <div class="char-count" data-counter="establishment_story">0/2000 characters</div>
                        <p class="form-hint">
                            Tell your story: When you started, why you started, challenges overcome, and what drives your commitment today
                        </p>
                    </div>
                </div>

                <!-- Impact Statistics -->
                <div class="section-divider">
                    <i class="fas fa-chart-line"></i>
                    <span>Impact Statistics</span>
                </div>
                
                <div class="form-section">
                    <p class="form-hint" style="margin-bottom: 1.5rem;">
                        <i class="fas fa-info-circle"></i> These numbers will be displayed prominently on your About page
                    </p>
                    
                    <div class="stats-grid">
                        <div class="form-group">
                            <label for="year_established" class="form-label">
                                <i class="fas fa-calendar-alt"></i>
                                Year Established
                            </label>
                            <input type="text" id="year_established" name="year_established" 
                                   class="form-input" placeholder="e.g., 2018"
                                   value="<?= htmlspecialchars($about['year_established'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="lives_impacted" class="form-label">
                                <i class="fas fa-users"></i>
                                Lives Impacted
                            </label>
                            <input type="text" id="lives_impacted" name="lives_impacted" 
                                   class="form-input" placeholder="e.g., 1,500+"
                                   value="<?= htmlspecialchars($about['lives_impacted'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="communities_served" class="form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Communities Served
                            </label>
                            <input type="text" id="communities_served" name="communities_served" 
                                   class="form-input" placeholder="e.g., 12+"
                                   value="<?= htmlspecialchars($about['communities_served'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="active_programs" class="form-label">
                                <i class="fas fa-project-diagram"></i>
                                Active Programs
                            </label>
                            <input type="text" id="active_programs" name="active_programs" 
                                   class="form-input" placeholder="e.g., 8"
                                   value="<?= htmlspecialchars($about['active_programs'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="../admin/dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save All Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Character counters
        const textareas = ['mission', 'vision', 'core_values', 'establishment_story'];
        textareas.forEach(id => {
            const el = document.getElementById(id);
            const counter = document.querySelector(`[data-counter="${id}"]`);
            
            function updateCount() {
                const length = el.value.length;
                const max = el.getAttribute('maxlength');
                counter.textContent = `${length}/${max} characters`;
                
                const percent = (length / max) * 100;
                if (percent > 80) counter.style.color = '#ef4444';
                else if (percent > 60) counter.style.color = '#f59e0b';
                else counter.style.color = 'var(--text-muted)';
            }
            
            el.addEventListener('input', updateCount);
            updateCount();
        });

        // File upload with drag & drop
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('heroImageInput');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'));
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'));
        });

        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            fileInput.files = files;
            handleFileSelect();
        });

        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file && file.type.startsWith('image/')) {
                dropZone.innerHTML = `
                    <i class="fas fa-check-circle upload-icon" style="color: #10b981;"></i>
                    <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem; font-weight: 600; color: #10b981;">
                        ✓ ${file.name}
                    </h4>
                    <p style="color: var(--text-light); margin: 0;">Click to choose a different file</p>
                `;
            }
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const mission = document.getElementById('mission').value.trim();
            const vision = document.getElementById('vision').value.trim();
            
            if (!mission || !vision) {
                e.preventDefault();
                alert('Please fill in both Mission and Vision statements - they are required!');
                return false;
            }
        });
    </script>
</body>
</html>