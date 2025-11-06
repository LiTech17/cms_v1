<?php
require_once "../config/database.php";
require_once "../core/auth.php";
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['location_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $stmt = $pdo->prepare("INSERT INTO operational_bases (location_name, description) VALUES (?, ?)");
    $stmt->execute([$name, $desc]);
    $_SESSION['success_message'] = "✅ Base added successfully!";
    header("Location: operational_bases.php");
    exit();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM operational_bases WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success_message'] = "🗑️ Base deleted successfully!";
    header("Location: operational_bases.php");
    exit();
}

$bases = $pdo->query("SELECT * FROM operational_bases ORDER BY id DESC")->fetchAll();

// Check for success message from redirect
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operational Bases | NGO CMS</title>
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
            max-width: 1000px;
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
        .add-base-form {
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

        /* Bases Grid */
        .bases-section {
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

        .bases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .base-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px var(--shadow);
            border: 1px solid var(--border);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
        }

        .base-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-md);
        }

        .base-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .base-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
            flex: 1;
        }

        .base-actions {
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

        .base-description {
            color: var(--text-light);
            line-height: 1.5;
            margin: 0;
        }

        .base-description:empty::before {
            content: "No description provided";
            color: var(--text-muted);
            font-style: italic;
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

            .bases-grid {
                grid-template-columns: 1fr;
            }

            .form-content {
                padding: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .base-header {
                flex-direction: column;
                gap: 0.75rem;
            }

            .base-actions {
                align-self: flex-end;
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

        /* Character Counter */
        .char-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-container fade-in">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>
                    <i class="fas fa-map-marker-alt"></i>
                    Operational Bases
                </h1>
                <p class="page-subtitle">Manage your organization's operational locations and field offices</p>
            </div>
            <div class="stats-badge">
                <?= count($bases) ?> Location<?= count($bases) !== 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Success Message -->
        <?php if(isset($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Add Base Form -->
        <form method="POST" class="add-base-form">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Operational Base</h2>
            </div>
            <div class="form-content">
                <div class="form-group">
                    <label for="location_name" class="form-label">
                        <i class="fas fa-map-pin"></i> Location Name
                    </label>
                    <input type="text" id="location_name" name="location_name" class="form-input" 
                           placeholder="Enter location name (e.g., Regional Office, Field Base)" required
                           value="<?= htmlspecialchars($_POST['location_name'] ?? '') ?>">
                    <div class="form-hint">
                        Provide a clear, descriptive name for this operational base
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        <i class="fas fa-file-alt"></i> Description
                    </label>
                    <textarea id="description" name="description" class="form-textarea" 
                              placeholder="Describe this base's purpose, activities, and operational scope..."
                              maxlength="500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="char-count" id="descCount">0/500 characters</div>
                    <div class="form-hint">
                        Optional: Describe the base's role, key activities, and operational focus
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Operational Base
                    </button>
                </div>
            </div>
        </form>

        <!-- Existing Bases Section -->
        <section class="bases-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-list"></i>
                    Existing Operational Bases
                </h2>
            </div>

            <?php if (empty($bases)): ?>
                <div class="empty-state">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>No Operational Bases Yet</h3>
                    <p>Add your first operational base to get started. These represent your organization's physical locations and field offices.</p>
                </div>
            <?php else: ?>
                <div class="bases-grid">
                    <?php foreach($bases as $base): ?>
                        <div class="base-card">
                            <div class="base-header">
                                <h3 class="base-name">
                                    <i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                    <?= htmlspecialchars($base['location_name']) ?>
                                </h3>
                                <div class="base-actions">
                                    <a href="?delete=<?= $base['id'] ?>" 
                                       class="btn-icon btn-danger"
                                       onclick="return confirm('Are you sure you want to delete the base \"<?= addslashes($base['location_name']) ?>\"? This action cannot be undone.')"
                                       title="Delete Base">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <p class="base-description">
                                <?= !empty($base['description']) ? nl2br(htmlspecialchars($base['description'])) : '' ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Character counter for description
        const descTextarea = document.getElementById('description');
        const descCounter = document.getElementById('descCount');

        function updateCharacterCount() {
            const length = descTextarea.value.length;
            descCounter.textContent = `${length}/500 characters`;
            
            // Update color based on usage
            descCounter.className = 'char-count';
            if (length > 400) {
                descCounter.style.color = '#dd6b20';
            }
            if (length > 480) {
                descCounter.style.color = '#e53e3e';
            }
        }

        descTextarea.addEventListener('input', updateCharacterCount);
        updateCharacterCount(); // Initialize counter

        // Add some interactive feedback
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to form inputs
            const inputs = document.querySelectorAll('.form-input, .form-textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = 'var(--primary)';
                    this.style.boxShadow = '0 0 0 3px rgba(43, 108, 176, 0.1)';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = 'var(--border)';
                    this.style.boxShadow = 'none';
                });
            });

            // Add confirmation for form submission
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const locationName = document.getElementById('location_name').value.trim();
                if (!locationName) {
                    e.preventDefault();
                    alert('Please enter a location name before adding the base.');
                    document.getElementById('location_name').focus();
                    return false;
                }
            });
        });
    </script>
</body>
</html>