<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkAdminLogin();

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $image = uploadFile($_FILES['image']);
    $caption = trim($_POST['caption'] ?? '');
    
    if ($image) {
        $stmt = $pdo->prepare("INSERT INTO home_hero (image, caption) VALUES (?, ?)");
        $stmt->execute([$image, $caption]);
        $_SESSION['success'] = "Hero image added successfully!";
        header("Location: home_hero.php");
        exit;
    } else {
        $error = "Upload failed. Please check image type and size.";
    }
}

// Delete image
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM home_hero WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success'] = "Hero image deleted successfully!";
    header("Location: home_hero.php");
    exit;
}

$heroes = $pdo->query("SELECT * FROM home_hero ORDER BY id DESC")->fetchAll();
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Hero | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --danger: #e53e3e;
            --success: #38a169;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --border: #e2e8f0;
            --radius: 8px;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .upload-form, .card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 10px 16px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #2c5282;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .hero-card {
            position: relative;
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: var(--radius);
        }

        .hero-caption {
            padding: 0.75rem 0;
            font-size: 14px;
        }

        .hero-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .hero-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1><i class="fas fa-images"></i> Homepage Hero Carousel</h1>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="form-group">
            <label>Upload Hero Image</label>
            <input type="file" name="image" class="form-input" accept="image/*" required>
            <small style="color: var(--text-light); display: block; margin-top: 0.25rem;">
                Recommended: 1200x600px or larger • JPG, PNG, WebP • Max 5MB
            </small>
        </div>

        <div class="form-group">
            <label>Caption (Optional)</label>
            <input type="text" name="caption" class="form-input" placeholder="Enter a short caption for this image">
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add to Carousel
        </button>
    </form>

    <!-- Existing Images -->
    <section>
        <h2 style="margin-bottom: 1rem;">Current Hero Images (<?= count($heroes) ?>)</h2>

        <?php if (empty($heroes)): ?>
            <div class="empty-state card">
                <i class="fas fa-images"></i>
                <h3>No Hero Images Yet</h3>
                <p>Upload your first image to get started</p>
            </div>
        <?php else: ?>
            <div class="hero-grid">
                <?php foreach($heroes as $hero): ?>
                    <div class="card hero-card">
                        <img src="../uploads/<?= htmlspecialchars($hero['image']) ?>" 
                             alt="Hero image" 
                             class="hero-image">
                        
                        <div class="hero-caption">
                            <?= !empty($hero['caption']) ? htmlspecialchars($hero['caption']) : '<em style="color: var(--text-light);">No caption</em>' ?>
                        </div>

                        <div class="hero-actions">
                            <a href="../uploads/<?= htmlspecialchars($hero['image']) ?>" 
                               target="_blank" 
                               class="btn" 
                               style="background: var(--bg); color: var(--text);">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="?delete=<?= $hero['id'] ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Delete this hero image?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <script>
        // Simple file preview
        const fileInput = document.querySelector('input[type="file"]');
        fileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                // Optional: Add file size validation
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit. Please choose a smaller image.');
                    this.value = '';
                }
            } else if (file) {
                alert('Please select a valid image file (JPG, PNG, WebP).');
                this.value = '';
            }
        });

        // Add loading state to images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.hero-image');
            images.forEach(img => {
                img.addEventListener('load', function() {
                    this.style.opacity = '1';
                });
                img.style.opacity = '0.8';
                img.style.transition = 'opacity 0.3s ease';
            });
        });
    </script>
</body>
</html>