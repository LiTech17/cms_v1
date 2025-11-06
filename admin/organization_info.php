<?php
require_once "../config/database.php";
require_once "../core/auth.php";
checkLogin();

// Check if table exists, if not create it
try {
    $stmt = $pdo->query("SELECT * FROM organization_info LIMIT 1");
    $data = $stmt->fetch() ?: [];
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $pdo->exec("CREATE TABLE organization_info (
        id INT PRIMARY KEY AUTO_INCREMENT,
        goals TEXT,
        vision TEXT,
        mission TEXT,
        geographical_location TEXT,
        objectives TEXT,
        core_values TEXT,
        history TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insert initial record
    $pdo->exec("INSERT INTO organization_info (id) VALUES (1)");
    $data = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $goals = trim($_POST['goals'] ?? '');
        $vision = trim($_POST['vision'] ?? '');
        $mission = trim($_POST['mission'] ?? '');
        $geo = trim($_POST['geographical_location'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $core_values = trim($_POST['core_values'] ?? '');
        $history = trim($_POST['history'] ?? '');
        
        // Validate required fields
        if (empty($vision) || empty($mission)) {
            throw new Exception("Vision and Mission are required fields.");
        }
        
        $stmt = $pdo->prepare("INSERT INTO organization_info (id, goals, vision, mission, geographical_location, objectives, core_values, history) 
                              VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE 
                              goals=VALUES(goals), vision=VALUES(vision), mission=VALUES(mission), 
                              geographical_location=VALUES(geographical_location), objectives=VALUES(objectives),
                              core_values=VALUES(core_values), history=VALUES(history)");
        
        $stmt->execute([$goals, $vision, $mission, $geo, $objectives, $core_values, $history]);
        
        $_SESSION['success_message'] = "✅ Organization information updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check for success message from redirect
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Information | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
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

        /* Header Styles */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--border);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .page-title i {
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Form Styles */
        .org-form {
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

        .form-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }

        .form-content {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section.full-width {
            grid-column: 1 / -1;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .section-header i {
            color: var(--primary);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .section-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
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

        .form-label.required::after {
            content: " *";
            color: #e53e3e;
        }

        .form-hint {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
            line-height: 1.4;
        }

        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: white;
            font-size: 0.95rem;
            transition: var(--transition);
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
            font-family: inherit;
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }

        .form-textarea.small {
            min-height: 80px;
        }

        .form-textarea.large {
            min-height: 150px;
        }

        .char-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.25rem;
        }

        .char-count.warning {
            color: #dd6b20;
        }

        .char-count.error {
            color: #e53e3e;
        }

        /* Button Styles */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border);
            background: var(--bg);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: var(--radius-sm);
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
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        .btn-secondary {
            background: var(--bg);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #f7fafc;
            border-color: var(--text-light);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--radius-sm);
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
            border-left-color: #e53e3e;
            color: #742a2a;
        }

        /* Preview Section */
        .preview-section {
            margin-top: 3rem;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .preview-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .preview-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px var(--shadow);
            border: 1px solid var(--border);
            padding: 1.5rem;
            transition: var(--transition);
        }

        .preview-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-md);
        }

        .preview-card h4 {
            color: var(--primary);
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-content {
            color: var(--text);
            line-height: 1.6;
        }

        .preview-content:empty::before {
            content: "Not specified";
            color: var(--text-muted);
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-content {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .preview-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-header {
                padding: 1rem 1.5rem;
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
            <h1 class="page-title">
                <i class="fas fa-building"></i>
                Organization Information
            </h1>
            <p class="page-subtitle">Define your organization's mission, vision, goals, and operational details</p>
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

        <form method="POST" class="org-form">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Edit Organization Details</h2>
            </div>
            
            <div class="form-content">
                <div class="form-grid">
                    <!-- Core Identity Section -->
                    <div class="form-section full-width">
                        <div class="section-header">
                            <i class="fas fa-bullseye"></i>
                            <h3>Core Identity</h3>
                        </div>
                        
                        <div class="form-group">
                            <label for="vision" class="form-label required">
                                <i class="fas fa-eye"></i> Vision Statement
                            </label>
                            <textarea id="vision" name="vision" class="form-textarea large" 
                                      placeholder="Describe the future you want to create... What is the ultimate impact you seek to achieve?"
                                      maxlength="500"><?= htmlspecialchars($data['vision'] ?? '') ?></textarea>
                            <div class="char-count" id="visionCount">0/500 characters</div>
                            <div class="form-hint">
                                Your vision statement should be inspirational and describe the desired future state
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="mission" class="form-label required">
                                <i class="fas fa-flag"></i> Mission Statement
                            </label>
                            <textarea id="mission" name="mission" class="form-textarea large" 
                                      placeholder="What is your organization's purpose? What do you do, for whom, and how?"
                                      maxlength="500"><?= htmlspecialchars($data['mission'] ?? '') ?></textarea>
                            <div class="char-count" id="missionCount">0/500 characters</div>
                            <div class="form-hint">
                                Your mission statement should be clear, concise, and explain your organization's purpose
                            </div>
                        </div>
                    </div>

                    <!-- Strategic Objectives -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-tasks"></i>
                            <h3>Strategic Framework</h3>
                        </div>

                        <div class="form-group">
                            <label for="goals" class="form-label">
                                <i class="fas fa-bullseye"></i> Long-term Goals
                            </label>
                            <textarea id="goals" name="goals" class="form-textarea" 
                                      placeholder="What are your main long-term goals and aspirations?"
                                      maxlength="1000"><?= htmlspecialchars($data['goals'] ?? '') ?></textarea>
                            <div class="char-count" id="goalsCount">0/1000 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="objectives" class="form-label">
                                <i class="fas fa-list-check"></i> Key Objectives
                            </label>
                            <textarea id="objectives" name="objectives" class="form-textarea" 
                                      placeholder="List your main objectives (one per line for better formatting)"
                                      maxlength="1000"><?= htmlspecialchars($data['objectives'] ?? '') ?></textarea>
                            <div class="char-count" id="objectivesCount">0/1000 characters</div>
                            <div class="form-hint">
                                Enter one objective per line. They will be displayed as a bulleted list.
                            </div>
                        </div>
                    </div>

                    <!-- Organizational Details -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Operational Details</h3>
                        </div>

                        <div class="form-group">
                            <label for="geographical_location" class="form-label">
                                <i class="fas fa-globe"></i> Geographical Focus
                            </label>
                            <textarea id="geographical_location" name="geographical_location" class="form-textarea" 
                                      placeholder="Which regions, countries, or communities do you serve?"
                                      maxlength="500"><?= htmlspecialchars($data['geographical_location'] ?? '') ?></textarea>
                            <div class="char-count" id="geoCount">0/500 characters</div>
                        </div>

                        <div class="form-group">
                            <label for="core_values" class="form-label">
                                <i class="fas fa-heart"></i> Core Values
                            </label>
                            <textarea id="core_values" name="core_values" class="form-textarea" 
                                      placeholder="What principles guide your organization's work and culture?"
                                      maxlength="1000"><?= htmlspecialchars($data['core_values'] ?? '') ?></textarea>
                            <div class="char-count" id="valuesCount">0/1000 characters</div>
                            <div class="form-hint">
                                List your organization's core values (one per line for better formatting)
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="history" class="form-label">
                                <i class="fas fa-history"></i> Our History
                            </label>
                            <textarea id="history" name="history" class="form-textarea" 
                                      placeholder="Brief history of your organization's founding and key milestones"
                                      maxlength="1500"><?= htmlspecialchars($data['history'] ?? '') ?></textarea>
                            <div class="char-count" id="historyCount">0/1500 characters</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="previewContent()">
                    <i class="fas fa-eye"></i> Preview
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Organization Information
                </button>
            </div>
        </form>

        <!-- Live Preview Section -->
        <section class="preview-section" id="previewSection" style="display: none;">
            <div class="preview-header">
                <h2 style="font-size: 1.5rem; color: var(--text);">
                    <i class="fas fa-desktop"></i> Live Preview
                </h2>
                <small style="color: var(--text-light);">How your information will appear to visitors</small>
            </div>
            <div class="preview-cards">
                <div class="preview-card">
                    <h4><i class="fas fa-eye"></i> Our Vision</h4>
                    <div class="preview-content" id="previewVision"></div>
                </div>
                <div class="preview-card">
                    <h4><i class="fas fa-flag"></i> Our Mission</h4>
                    <div class="preview-content" id="previewMission"></div>
                </div>
                <div class="preview-card">
                    <h4><i class="fas fa-bullseye"></i> Our Goals</h4>
                    <div class="preview-content" id="previewGoals"></div>
                </div>
                <div class="preview-card">
                    <h4><i class="fas fa-list-check"></i> Our Objectives</h4>
                    <div class="preview-content" id="previewObjectives"></div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Character counter functionality
        function setupCharacterCounter(textareaId, counterId, maxLength) {
            const textarea = document.getElementById(textareaId);
            const counter = document.getElementById(counterId);
            
            function updateCounter() {
                const length = textarea.value.length;
                counter.textContent = `${length}/${maxLength} characters`;
                
                // Update color based on usage
                counter.className = 'char-count';
                if (length > maxLength * 0.8) {
                    counter.classList.add('warning');
                }
                if (length > maxLength) {
                    counter.classList.add('error');
                }
            }
            
            textarea.addEventListener('input', updateCounter);
            updateCounter(); // Initialize
        }

        // Initialize character counters
        setupCharacterCounter('vision', 'visionCount', 500);
        setupCharacterCounter('mission', 'missionCount', 500);
        setupCharacterCounter('goals', 'goalsCount', 1000);
        setupCharacterCounter('objectives', 'objectivesCount', 1000);
        setupCharacterCounter('geographical_location', 'geoCount', 500);
        setupCharacterCounter('core_values', 'valuesCount', 1000);
        setupCharacterCounter('history', 'historyCount', 1500);

        // Preview functionality
        function previewContent() {
            // Update preview content
            document.getElementById('previewVision').textContent = 
                document.getElementById('vision').value || 'Not specified';
            document.getElementById('previewMission').textContent = 
                document.getElementById('mission').value || 'Not specified';
            document.getElementById('previewGoals').textContent = 
                document.getElementById('goals').value || 'Not specified';
            
            // Format objectives as bullet points
            const objectives = document.getElementById('objectives').value;
            const objectivesPreview = document.getElementById('previewObjectives');
            if (objectives) {
                const lines = objectives.split('\n').filter(line => line.trim());
                objectivesPreview.innerHTML = lines.map(line => 
                    `<div style="display: flex; align-items: flex-start; margin-bottom: 0.5rem;">
                        <span style="color: var(--primary); margin-right: 0.5rem;">•</span>
                        <span>${line.trim()}</span>
                    </div>`
                ).join('');
            } else {
                objectivesPreview.textContent = 'Not specified';
            }
            
            // Show preview section
            document.getElementById('previewSection').style.display = 'block';
            
            // Smooth scroll to preview
            document.getElementById('previewSection').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }

        // Auto-save draft (optional enhancement)
        let autoSaveTimer;
        document.querySelectorAll('.form-textarea').forEach(textarea => {
            textarea.addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Could implement auto-save to localStorage here
                    console.log('Content changed - ready for auto-save');
                }, 2000);
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const vision = document.getElementById('vision').value.trim();
            const mission = document.getElementById('mission').value.trim();
            
            if (!vision || !mission) {
                e.preventDefault();
                alert('Please fill in both Vision and Mission statements before saving.');
                if (!vision) document.getElementById('vision').focus();
                else document.getElementById('mission').focus();
                return false;
            }
        });
    </script>
</body>
</html>