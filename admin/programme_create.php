<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkAdminLogin();

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_programme'])) {
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

        // Insert programme
        $stmt = $pdo->prepare("INSERT INTO programmes (title, introduction, objectives, key_achievements, target_beneficiaries, duration, status, budget, location, partner_organizations, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $introduction, $objectives, $key_achievements, $target_beneficiaries, $duration, $status, $budget, $location, $partner_organizations, $is_featured]);
        
        $programme_id = $pdo->lastInsertId();

        // Handle statistics if provided
        if (isset($_POST['statistics']) && is_array($_POST['statistics'])) {
            foreach ($_POST['statistics'] as $stat) {
                if (!empty($stat['name']) && !empty($stat['value'])) {
                    $stmt = $pdo->prepare("INSERT INTO programme_statistics (programme_id, statistic_name, statistic_value, statistic_icon, display_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$programme_id, trim($stat['name']), trim($stat['value']), $stat['icon'] ?? 'fas fa-chart-line', $stat['order'] ?? 0]);
                }
            }
        }

        $_SESSION['success_message'] = "✅ Programme created successfully!";
        header("Location: programme_edit.php?id=$programme_id");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check for success message
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Programme | NGO CMS</title>
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
            max-width: 1000px;
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

        /* Form Styles */
        .form-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Statistics Section */
        .statistics-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .statistics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .statistic-item {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bg);
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .statistic-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
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

            .form-content {
                padding: 1.5rem;
            }

            .statistic-item {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-plus-circle"></i>
                    Create New Programme
                </h1>
                <p class="page-subtitle">Add a new programme to showcase your organization's work</p>
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

        <!-- Programme Form -->
        <form method="POST" class="form-card">
            <div class="form-header">
                <h3><i class="fas fa-project-diagram"></i> Programme Information</h3>
            </div>
            <div class="form-content">
                <!-- Basic Information -->
                <div class="form-group">
                    <label for="title" class="form-label">Programme Title *</label>
                    <input type="text" id="title" name="title" class="form-input" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                           placeholder="Enter programme title" required>
                    <div class="form-hint">A clear, descriptive title for the programme</div>
                </div>

                <div class="form-group">
                    <label for="introduction" class="form-label">Programme Introduction *</label>
                    <textarea id="introduction" name="introduction" class="form-textarea" 
                              placeholder="Describe the programme, its purpose, and overall impact..."
                              required><?= htmlspecialchars($_POST['introduction'] ?? '') ?></textarea>
                    <div class="form-hint">Provide a comprehensive overview of the programme</div>
                </div>

                <div class="form-group">
                    <label for="objectives" class="form-label">Key Objectives</label>
                    <textarea id="objectives" name="objectives" class="form-textarea" 
                              placeholder="List the main objectives and goals of the programme..."><?= htmlspecialchars($_POST['objectives'] ?? '') ?></textarea>
                    <div class="form-hint">Separate each objective with a new line</div>
                </div>

                <div class="form-group">
                    <label for="key_achievements" class="form-label">Key Achievements</label>
                    <textarea id="key_achievements" name="key_achievements" class="form-textarea" 
                              placeholder="Highlight major accomplishments and successes..."><?= htmlspecialchars($_POST['key_achievements'] ?? '') ?></textarea>
                    <div class="form-hint">Notable results and impact achieved so far</div>
                </div>

                <!-- Programme Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="target_beneficiaries" class="form-label">Target Beneficiaries</label>
                        <input type="text" id="target_beneficiaries" name="target_beneficiaries" class="form-input" 
                               value="<?= htmlspecialchars($_POST['target_beneficiaries'] ?? '') ?>" 
                               placeholder="e.g., Women, Youth, Rural Communities">
                    </div>

                    <div class="form-group">
                        <label for="duration" class="form-label">Duration</label>
                        <input type="text" id="duration" name="duration" class="form-input" 
                               value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>" 
                               placeholder="e.g., 2 years, Ongoing, 6 months">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= ($_POST['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="upcoming" <?= ($_POST['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="budget" class="form-label">Budget ($)</label>
                        <input type="number" id="budget" name="budget" class="form-input" 
                               value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>" 
                               placeholder="Enter budget amount" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" id="location" name="location" class="form-input" 
                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                           placeholder="e.g., Urban areas, Rural communities, National">
                </div>

                <div class="form-group">
                    <label for="partner_organizations" class="form-label">Partner Organizations</label>
                    <textarea id="partner_organizations" name="partner_organizations" class="form-textarea" 
                              placeholder="List partner organizations, separated by commas"><?= htmlspecialchars($_POST['partner_organizations'] ?? '') ?></textarea>
                </div>

                <!-- Programme Settings -->
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" 
                               class="checkbox" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                        <label for="is_featured" class="form-label">Feature this programme on the homepage</label>
                    </div>
                    <div class="form-hint">Featured programmes are displayed prominently on the website</div>
                </div>

                <!-- Statistics Section -->
                <div class="statistics-section">
                    <div class="statistics-header">
                        <h4 style="color: var(--text);">Impact Statistics</h4>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addStatistic()">
                            <i class="fas fa-plus"></i> Add Statistic
                        </button>
                    </div>
                    
                    <div id="statistics-container">
                        <!-- Statistics will be added here dynamically -->
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="programmes.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="create_programme" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Programme
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let statisticCount = 0;
        const iconOptions = [
            'fas fa-users', 'fas fa-map-marker-alt', 'fas fa-chart-line', 'fas fa-tasks',
            'fas fa-dollar-sign', 'fas fa-graduation-cap', 'fas fa-heart', 'fas fa-home',
            'fas fa-school', 'fas fa-hand-holding-heart', 'fas fa-briefcase', 'fas fa-seedling'
        ];

        function addStatistic(name = '', value = '', icon = 'fas fa-chart-line', order = 0) {
            const container = document.getElementById('statistics-container');
            const id = statisticCount++;
            
            const statisticHTML = `
                <div class="statistic-item" data-id="${id}">
                    <div>
                        <input type="text" name="statistics[${id}][name]" 
                               class="form-input" placeholder="Statistic name" 
                               value="${name}" required>
                    </div>
                    <div>
                        <input type="text" name="statistics[${id}][value]" 
                               class="form-input" placeholder="Statistic value" 
                               value="${value}" required>
                    </div>
                    <div class="statistic-actions">
                        <select name="statistics[${id}][icon]" class="form-select" style="min-width: 120px;">
                            ${iconOptions.map(iconOption => `
                                <option value="${iconOption}" ${icon === iconOption ? 'selected' : ''}>
                                    ${iconOption.replace('fas fa-', '').replace(/-/g, ' ')}
                                </option>
                            `).join('')}
                        </select>
                        <input type="hidden" name="statistics[${id}][order]" value="${order}">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeStatistic(${id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', statisticHTML);
        }

        function removeStatistic(id) {
            const element = document.querySelector(`.statistic-item[data-id="${id}"]`);
            if (element) {
                element.remove();
            }
        }

        // Add some default statistics when page loads
        document.addEventListener('DOMContentLoaded', function() {
            addStatistic('Lives Impacted', '1500+', 'fas fa-users', 1);
            addStatistic('Communities Served', '12+', 'fas fa-map-marker-alt', 2);
            addStatistic('Success Rate', '95%', 'fas fa-chart-line', 3);
        });

        // Character counter for textareas
        document.addEventListener('input', function(e) {
            if (e.target.matches('.form-textarea')) {
                const maxLength = 2000;
                const currentLength = e.target.value.length;
                const counter = e.target.parentNode.querySelector('.char-counter') || 
                               (function() {
                                   const counter = document.createElement('div');
                                   counter.className = 'form-hint char-counter';
                                   e.target.parentNode.appendChild(counter);
                                   return counter;
                               })();
                
                counter.textContent = `${currentLength}/${maxLength} characters`;
                counter.style.color = currentLength > maxLength ? 'var(--danger)' : 'var(--text-light)';
            }
        });
    </script>
</body>
</html>