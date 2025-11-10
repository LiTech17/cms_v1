<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkEditorLogin();

// Handle programme creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_programme'])) {
    $title = trim($_POST['title']);
    $introduction = trim($_POST['introduction']);
    $objectives = trim($_POST['objectives']);
    $key_achievements = trim($_POST['key_achievements']);
    $target_beneficiaries = trim($_POST['target_beneficiaries']);
    $duration = trim($_POST['duration']);
    $status = $_POST['status'];
    $budget = $_POST['budget'] ?: null;
    $location = trim($_POST['location']);
    $partner_organizations = trim($_POST['partner_organizations']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO programmes (title, introduction, objectives, key_achievements, target_beneficiaries, duration, status, budget, location, partner_organizations, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $introduction, $objectives, $key_achievements, $target_beneficiaries, $duration, $status, $budget, $location, $partner_organizations, $is_featured]);
        
        $programme_id = $pdo->lastInsertId();
        $_SESSION['success_message'] = "Programme created successfully!";
        header("Location: programme_edit.php?id=$programme_id");
        exit();
    } else {
        $error = "Title is required.";
    }
}

// Fetch all programmes
$programmes = $pdo->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM programme_media pm WHERE pm.programme_id = p.id) as media_count,
           (SELECT COUNT(*) FROM programme_statistics ps WHERE ps.programme_id = p.id) as stats_count
    FROM programmes p 
    ORDER BY p.is_featured DESC, p.created_at DESC
")->fetchAll();

// Check for success message
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes Management | NGO CMS</title>
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

        /* === FILTERS === */
        .filters {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            padding: 0.5rem 0;
        }

        .filter-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* === BUTTONS === */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
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
            color: white;
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
            color: white;
            border-color: var(--primary);
        }

        .btn-outline.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-success {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-height: 36px;
        }

        /* === PROGRAMMES GRID === */
        .programmes-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-top: 1rem;
        }

        .programme-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .programme-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .programme-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        .programme-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .programme-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            background: var(--bg);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-light);
        }

        .featured-badge {
            background: var(--warning);
            color: white;
        }

        .status-badge.active {
            background: var(--success);
            color: white;
        }

        .status-badge.completed {
            background: var(--primary);
            color: white;
        }

        .programme-content {
            padding: 1.25rem;
            flex: 1;
        }

        .programme-description {
            color: var(--text-light);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .programme-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .detail-item i {
            width: 16px;
            color: var(--primary);
            flex-shrink: 0;
        }

        .programme-footer {
            padding: 1rem 1.25rem;
            background: var(--bg);
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .programme-info {
            font-size: 0.8rem;
            color: var(--text-light);
            text-align: center;
        }

        .programme-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .programme-actions .btn {
            flex: 1;
            max-width: 120px;
        }

        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 2px dashed var(--border);
            margin: 2rem 0;
            grid-column: 1 / -1;
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
                max-width: 1200px;
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

            .programmes-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 1.5rem;
            }

            .programme-details {
                grid-template-columns: 1fr 1fr;
            }

            .programme-footer {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .programme-actions {
                justify-content: flex-end;
            }

            .programme-actions .btn {
                flex: none;
                max-width: none;
            }
        }

        /* === DESKTOP STYLES === */
        @media (min-width: 1024px) {
            .programmes-grid {
                grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            }

            .filters {
                margin-bottom: 2.5rem;
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
                <h1>
                    <i class="fas fa-project-diagram"></i>
                    Programmes Management
                </h1>
                <p class="page-subtitle">Manage all programmes and their impact metrics</p>
            </div>
            <div class="header-actions">
                <a href="programme_create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Programme
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

        <div class="filters">
            <div class="filter-group">
                <a href="programmes.php" class="btn btn-outline active">
                    <i class="fas fa-list"></i> All (<?= count($programmes) ?>)
                </a>
                
                <a href="?filter=featured" class="btn btn-outline">
                    <i class="fas fa-star"></i> Featured (<?= count(array_filter($programmes, fn($p) => $p['is_featured'])) ?>)
                </a>
                <a href="?filter=active" class="btn btn-outline">
                    <i class="fas fa-play-circle"></i> Active (<?= count(array_filter($programmes, fn($p) => $p['status'] === 'active')) ?>)
                </a>
                <a href="?filter=completed" class="btn btn-outline">
                    <i class="fas fa-check-circle"></i> Completed (<?= count(array_filter($programmes, fn($p) => $p['status'] === 'completed')) ?>)
                </a>
            </div>
        </div>

        <div class="programmes-grid">
            <?php if (empty($programmes)): ?>
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <h3>No Programmes Found</h3>
                    <p>Create your first programme to get started with showcasing your organization's work.</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="programme_create.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Programme
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($programmes as $programme): ?>
                    <div class="programme-card" data-programme-id="<?= $programme['id'] ?>">
                        <div class="programme-header">
                            <div class="programme-title">
                                <?= htmlspecialchars($programme['title']) ?>
                            </div>
                            <div class="programme-meta">
                                <?php if($programme['is_featured']): ?>
                                    <span class="meta-badge featured-badge">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                <?php endif; ?>
                                <span class="meta-badge status-badge <?= strtolower($programme['status']) ?>">
                                    <i class="fas fa-circle"></i> <?= ucfirst($programme['status']) ?>
                                </span>
                                <span class="meta-badge">
                                    <i class="fas fa-images"></i> <?= $programme['media_count'] ?>
                                </span>
                                <span class="meta-badge">
                                    <i class="fas fa-chart-bar"></i> <?= $programme['stats_count'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="programme-content">
                            <div class="programme-description">
                                <?= htmlspecialchars($programme['introduction'] ?? 'No description available.') ?>
                            </div>

                            <div class="programme-details">
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($programme['location'] ?? 'Not specified') ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= htmlspecialchars($programme['duration'] ?? 'Ongoing') ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?= htmlspecialchars($programme['target_beneficiaries'] ?? 'Various beneficiaries') ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span><?= $programme['budget'] ? '$' . number_format($programme['budget']) : 'Budget not set' ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="programme-footer">
                            <div class="programme-info">
                                <small>Created: <?= date('M j, Y', strtotime($programme['created_at'])) ?></small>
                            </div>
                            <div class="programme-actions">
                                <a href="../public/programme_detail.php?id=<?= $programme['id'] ?>" 
                                   class="btn btn-outline btn-sm" target="_blank" 
                                   aria-label="View programme <?= htmlspecialchars($programme['title']) ?>">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="programme_edit.php?id=<?= $programme['id'] ?>" 
                                   class="btn btn-primary btn-sm"
                                   aria-label="Edit programme <?= htmlspecialchars($programme['title']) ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced card animations
            const cards = document.querySelectorAll('.programme-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add loading states to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.href || this.type === 'submit') {
                        this.classList.add('loading');
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    }
                });
            });

            // Enhanced touch interactions for mobile
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                
                // Add touch feedback
                cards.forEach(card => {
                    card.addEventListener('touchstart', function() {
                        this.style.transform = 'scale(0.98)';
                    });
                    
                    card.addEventListener('touchend', function() {
                        this.style.transform = '';
                    });
                });
            }

            // Filter active state management
            const filterButtons = document.querySelectorAll('.filters .btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });

        // Error handling for broken links
        window.addEventListener('error', function(e) {
            if (e.target.tagName === 'A') {
                console.error('Link error:', e.target.href);
            }
        });
    </script>
</body>
</html>
