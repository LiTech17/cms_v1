<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkEditorLogin(); // Only Editors can access this page

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
        if (empty($title) || empty($introduction)) {
            throw new Exception("Title and introduction are required.");
        }

        // Insert programme
        $stmt = $pdo->prepare("INSERT INTO programmes (title, introduction, objectives, key_achievements, target_beneficiaries, duration, status, budget, location, partner_organizations, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $introduction, $objectives, $key_achievements, $target_beneficiaries, $duration, $status, $budget, $location, $partner_organizations, $is_featured]);
        
        $programme_id = $pdo->lastInsertId();

        // Handle statistics
        if (isset($_POST['statistics']) && is_array($_POST['statistics'])) {
            foreach ($_POST['statistics'] as $stat) {
                if (!empty($stat['name']) && !empty($stat['value'])) {
                    $stmt = $pdo->prepare("INSERT INTO programme_statistics (programme_id, statistic_name, statistic_value, statistic_icon, display_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$programme_id, trim($stat['name']), trim($stat['value']), $stat['icon'] ?? 'fas fa-chart-line', $stat['order'] ?? 0]);
                }
            }
        }

        $_SESSION['success_message'] = "Programme created successfully!";
        header("Location: programme_edit.php?id=$programme_id");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Programme</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --success: #10b981;
            --danger: #ef4444;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            padding: 1.5rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-light);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem;
            background: var(--primary);
            color: var(--white);
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.2s;
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
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox {
            width: 18px;
            height: 18px;
        }

        .section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .section-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
        }

        .statistic-item {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .statistic-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
            padding: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
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
    <div class="container">
        <div class="header">
            <a href="programmes.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Programmes
            </a>
            <h1>
                <i class="fas fa-plus-circle"></i>
                Create Programme
            </h1>
            <p>Add a new programme to showcase your organization's work</p>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-project-diagram"></i> Programme Details</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="title">Programme Title *</label>
                        <input type="text" id="title" name="title" class="form-input" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="Enter programme title" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="introduction">Introduction *</label>
                        <textarea id="introduction" name="introduction" class="form-textarea" 
                                  placeholder="Describe the programme and its purpose..."
                                  required><?= htmlspecialchars($_POST['introduction'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="objectives">Objectives</label>
                        <textarea id="objectives" name="objectives" class="form-textarea" 
                                  placeholder="List the main objectives..."><?= htmlspecialchars($_POST['objectives'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="key_achievements">Key Achievements</label>
                        <textarea id="key_achievements" name="key_achievements" class="form-textarea" 
                                  placeholder="Highlight major accomplishments..."><?= htmlspecialchars($_POST['key_achievements'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="target_beneficiaries">Target Beneficiaries</label>
                            <input type="text" id="target_beneficiaries" name="target_beneficiaries" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['target_beneficiaries'] ?? '') ?>" 
                                   placeholder="e.g., Women, Youth">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>" 
                                   placeholder="e.g., 2 years">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= ($_POST['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="upcoming" <?= ($_POST['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="budget">Budget ($)</label>
                            <input type="number" id="budget" name="budget" class="form-input" 
                                   value="<?= htmlspecialchars($_POST['budget'] ?? '') ?>" 
                                   placeholder="Enter budget" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" name="location" class="form-input" 
                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                               placeholder="e.g., Urban areas">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="partner_organizations">Partner Organizations</label>
                        <textarea id="partner_organizations" name="partner_organizations" class="form-textarea" 
                                  placeholder="List partners separated by commas"><?= htmlspecialchars($_POST['partner_organizations'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" 
                                   class="checkbox" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                            <label for="is_featured" class="form-label">Feature on homepage</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="section">
                        <div class="section-header">
                            <h3>Impact Statistics</h3>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addStatistic()">
                                <i class="fas fa-plus"></i> Add Statistic
                            </button>
                        </div>
                        
                        <div id="statistics-container">
                            <!-- Statistics added here -->
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="programmes.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="create_programme" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Programme
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        let statisticCount = 0;
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
                        <button type="button" class="btn btn-danger" onclick="removeStatistic(${id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', statisticHTML);
        }

        function removeStatistic(id) {
            const element = document.querySelector(`.statistic-item[data-id="${id}"]`);
            if (element) element.remove();
        }

        // Add default statistics
        document.addEventListener('DOMContentLoaded', function() {
            addStatistic('Lives Impacted', '1500+', 'fas fa-users');
            addStatistic('Success Rate', '95%', 'fas fa-chart-line');
        });
    </script>
</body>
</html>