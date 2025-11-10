<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkAdminLogin();

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
        $_SESSION['success_message'] = "✅ Programme created successfully!";
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
            max-width: 1400px;
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

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        /* Cards Grid */
        .programmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .programme-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .programme-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--shadow);
        }

        .programme-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
        }

        .programme-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .programme-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
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

        .status-badge {
            background: var(--success);
            color: white;
        }

        .programme-content {
            padding: 1.5rem;
            flex: 1;
        }

        .programme-description {
            color: var(--text-light);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .programme-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .detail-item i {
            width: 16px;
            color: var(--primary);
        }

        .programme-footer {
            padding: 1rem 1.5rem;
            background: var(--bg);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .programme-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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

            .programmes-grid {
                grid-template-columns: 1fr;
            }

            .programme-details {
                grid-template-columns: 1fr;
            }

            .programme-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .programme-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-project-diagram"></i>
                    Programmes Management
                </h1>
                <p class="page-subtitle">Manage all programmes and their impact metrics</p>
            </div>
            <div>
                <a href="programme_create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Programme
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

        <!-- Quick Actions -->
        <div class="quick-actions">
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

        <!-- Programmes Grid -->
        <div class="programmes-grid">
            <?php foreach($programmes as $programme): ?>
                <div class="programme-card">
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
                            <span class="meta-badge status-badge">
                                <i class="fas fa-circle"></i> <?= ucfirst($programme['status']) ?>
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-images"></i> <?= $programme['media_count'] ?> media
                            </span>
                            <span class="meta-badge">
                                <i class="fas fa-chart-bar"></i> <?= $programme['stats_count'] ?> stats
                            </span>
                        </div>
                    </div>

                    <div class="programme-content">
                        <div class="programme-description">
                            <?= htmlspecialchars(substr($programme['introduction'] ?? 'No description available.', 0, 150)) ?>...
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
                            <small style="color: var(--text-light);">
                                Created: <?= date('M j, Y', strtotime($programme['created_at'])) ?>
                            </small>
                        </div>
                        <div class="programme-actions">
                            <a href="../public/programme_detail.php?id=<?= $programme['id'] ?>" 
                               class="btn btn-outline btn-sm" target="_blank">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="programme_edit.php?id=<?= $programme['id'] ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($programmes)): ?>
                <div class="empty-state">
                    <i class="fas fa-project-diagram"></i>
                    <h3>No Programmes Yet</h3>
                    <p>Create your first programme to get started with showcasing your organization's work.</p>
                    <a href="programme_create.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Create First Programme
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add loading animations
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>