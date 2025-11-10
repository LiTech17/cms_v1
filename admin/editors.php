<?php
// admin/editors.php - Editor Management Panel

// 1. Security & Configuration
require_once "../config/database.php"; 
require_once "../core/auth.php"; 
checkAdminLogin(); // Only Admins can manage other editors

// 2. Handle Delete Action (Now fully functional)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $editor_id = (int)$_GET['id'];
    
    // In this dual-table system, we only need to ensure the ID is valid and not zero.
    if ($editor_id <= 0) {
        $_SESSION['error'] = "Invalid editor ID for deletion.";
        header("Location: editors.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM editors WHERE id = ?");
        
        if ($stmt->execute([$editor_id])) {
            // Check if any rows were affected
            if ($stmt->rowCount() > 0) {
                 $_SESSION['message'] = "Editor deleted successfully!";
            } else {
                 $_SESSION['error'] = "Editor not found or already deleted.";
            }
        } else {
            $_SESSION['error'] = "Failed to delete editor due to database issue.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }

    // Redirect to prevent re-submission of the delete query
    header("Location: editors.php");
    exit();
}

// 3. Data Retrieval
try {
    // Fetch all editors from the 'editors' table
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM editors ORDER BY id DESC");
    $editors = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database errors gracefully
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2b6cb0">
    <title>Manage Editors | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === MOBILE-FIRST CSS VARIABLES === */
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --danger: #e53e3e;
            --success: #38a169;
            --warning: #d69e2e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --text-muted: #a0aec0;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-md: rgba(0, 0, 0, 0.15);
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
        }

        /* === BASE STYLES === */
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
            -webkit-tap-highlight-color: transparent;
            padding-bottom: var(--space-xl);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-md);
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

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .page-title i {
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-md);
        }

        /* === ALERT STYLES === */
        .alert {
            border: none;
            border-radius: var(--radius);
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: flex-start;
            gap: var(--space-md);
            animation: slideIn 0.3s ease-out;
            border-left: 4px solid;
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

        .alert-danger {
            background: #fed7d7;
            border-left-color: var(--danger);
            color: #742a2a;
        }

        .alert i {
            font-size: 1.25rem;
            margin-top: 0.1rem;
        }

        /* === BUTTON STYLES === */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md) var(--space-lg);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.95rem;
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

        .btn-secondary {
            background: white;
            color: var(--text);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover,
        .btn-secondary:focus {
            background: var(--bg);
            border-color: var(--text-light);
            transform: translateY(-2px);
        }

        .btn-info {
            background: var(--accent);
            color: white;
            border: none;
        }

        .btn-info:hover,
        .btn-info:focus {
            background: #319795;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background: #c53030;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: var(--space-sm) var(--space-md);
            font-size: 0.875rem;
        }

        /* === TABLE STYLES === */
        .table-responsive {
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 3px var(--shadow);
            background: var(--card-bg);
            overflow: hidden;
            margin-bottom: var(--space-xl);
        }

        .table {
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background: var(--primary);
            color: white;
            border: none;
            padding: var(--space-lg);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            transition: var(--transition);
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(43, 108, 176, 0.03);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px var(--shadow);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .action-btns {
            display: flex;
            gap: var(--space-sm);
            justify-content: center;
            flex-wrap: wrap;
        }

        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: var(--space-lg);
            opacity: 0.5;
        }

        /* === CARD STYLES FOR MOBILE === */
        .editor-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 3px var(--shadow);
            padding: var(--space-lg);
            margin-bottom: var(--space-md);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .editor-card:hover {
            box-shadow: 0 4px 12px var(--shadow-md);
            transform: translateY(-2px);
        }

        .editor-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: var(--space-md);
            padding-bottom: var(--space-md);
            border-bottom: 1px solid var(--border);
        }

        .editor-info {
            flex: 1;
        }

        .editor-name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: var(--space-xs);
            font-size: 1.1rem;
        }

        .editor-email {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: var(--space-xs);
        }

        .editor-meta {
            display: flex;
            gap: var(--space-md);
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .card-actions {
            display: flex;
            gap: var(--space-sm);
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

        /* Focus styles */
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* === RESPONSIVE DESIGN === */
        /* Hide table on mobile, show cards */
        @media (max-width: 768px) {
            .table-desktop {
                display: none;
            }
            
            .cards-mobile {
                display: block;
            }
            
            .page-header {
                text-align: center;
            }
            
            .header-actions {
                justify-content: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Show table on desktop, hide cards */
        @media (min-width: 769px) {
            .table-desktop {
                display: table;
            }
            
            .cards-mobile {
                display: none;
            }
        }

        /* Tablet adjustments */
        @media (min-width: 768px) and (max-width: 1024px) {
            .container {
                padding: var(--space-lg);
            }
            
            .action-btns {
                flex-direction: column;
            }
        }

        /* Desktop enhancements */
        @media (min-width: 1024px) {
            .container {
                padding: var(--space-xl);
            }
            
            .page-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-end;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }

        /* Small mobile adjustments */
        @media (max-width: 360px) {
            .container {
                padding: var(--space-sm);
            }
            
            .editor-header {
                flex-direction: column;
                gap: var(--space-md);
            }
            
            .card-actions {
                width: 100%;
                justify-content: center;
            }
        }

        /* === TOUCH DEVICE OPTIMIZATIONS === */
        @media (hover: none) {
            .btn:hover,
            .table tbody tr:hover,
            .editor-card:hover {
                transform: none;
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
                --shadow: rgba(0, 0, 0, 0.3);
                --shadow-md: rgba(0, 0, 0, 0.4);
            }

            .btn-secondary {
                background: #2d3748;
                color: #f7fafc;
                border-color: #4a5568;
            }

            .btn-secondary:hover {
                background: #4a5568;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-user-edit"></i>
                    Manage Editors
                </h1>
            </div>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="editor_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Editor
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong> <?= $_SESSION['message']; ?>
                </div>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error!</strong> <?= $_SESSION['error']; ?>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Desktop Table View -->
        <div class="table-responsive table-desktop">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Created On</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($editors) > 0): ?>
                        <?php foreach ($editors as $editor): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($editor['id']) ?></strong></td>
                                <td><?= htmlspecialchars($editor['username']) ?></td>
                                <td><?= htmlspecialchars($editor['email']) ?></td>
                                <td><?= date('M j, Y', strtotime($editor['created_at'])) ?></td>
                                <td class="action-btns">
                                    <a href="editor_form.php?id=<?= $editor['id'] ?>" 
                                       class="btn btn-info btn-sm" 
                                       title="Edit Editor"
                                       aria-label="Edit editor <?= htmlspecialchars($editor['username']) ?>">
                                        <i class="fas fa-pencil-alt"></i> Edit
                                    </a>
                                    <a href="editors.php?action=delete&id=<?= $editor['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete editor <?= htmlspecialchars($editor['username']) ?>? This action cannot be undone.')"
                                       title="Delete Editor"
                                       aria-label="Delete editor <?= htmlspecialchars($editor['username']) ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="empty-state">
                                    <i class="fas fa-user-edit"></i>
                                    <h4>No Editors Found</h4>
                                    <p>Get started by adding your first editor</p>
                                    <a href="editor_form.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-plus"></i> Add Editor
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View -->
        <div class="cards-mobile">
            <?php if (count($editors) > 0): ?>
                <?php foreach ($editors as $editor): ?>
                    <div class="editor-card">
                        <div class="editor-header">
                            <div class="editor-info">
                                <div class="editor-name"><?= htmlspecialchars($editor['username']) ?></div>
                                <div class="editor-email"><?= htmlspecialchars($editor['email']) ?></div>
                                <div class="editor-meta">
                                    <span>ID: #<?= htmlspecialchars($editor['id']) ?></span>
                                    <span>Created: <?= date('M j, Y', strtotime($editor['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="editor_form.php?id=<?= $editor['id'] ?>" 
                               class="btn btn-info btn-sm" 
                               style="flex: 1;"
                               aria-label="Edit editor <?= htmlspecialchars($editor['username']) ?>">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                            <a href="editors.php?action=delete&id=<?= $editor['id'] ?>" 
                               class="btn btn-danger btn-sm" 
                               style="flex: 1;"
                               onclick="return confirm('Are you sure you want to delete editor <?= htmlspecialchars($editor['username']) ?>? This action cannot be undone.')"
                               aria-label="Delete editor <?= htmlspecialchars($editor['username']) ?>">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-edit"></i>
                    <h4>No Editors Found</h4>
                    <p>Get started by adding your first editor</p>
                    <a href="editor_form.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus"></i> Add Editor
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced user experience scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.href && !this.classList.contains('btn-secondary')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }, 2000);
                    }
                });
            });

            // Enhanced delete confirmation
            const deleteButtons = document.querySelectorAll('a[href*="action=delete"]');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm(this.getAttribute('aria-label').replace('Delete editor ', 'Are you sure you want to delete editor '))) {
                        e.preventDefault();
                    }
                });
            });

            // Touch device optimizations
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }

            // Reduced motion support
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.style.setProperty('--transition', 'none');
                document.documentElement.style.setProperty('--transition-slow', 'none');
            }
        });
    </script>
</body>
</html>