<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkLogin();

// Fetch existing settings
$stmt = $pdo->query("SELECT * FROM site_settings LIMIT 1");
$settings = $stmt->fetch() ?: [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $site_title = trim($_POST['site_title'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $physical_address = trim($_POST['physical_address'] ?? '');
        $postal_address = trim($_POST['postal_address'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        $twitter = trim($_POST['twitter'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $youtube = trim($_POST['youtube'] ?? '');

        // Validate required fields
        if (empty($site_title)) {
            throw new Exception("Site title is required");
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }

        $logo = !empty($_FILES['logo']['name']) ? uploadFile($_FILES['logo']) : null;
        $favicon = !empty($_FILES['favicon']['name']) ? uploadFile($_FILES['favicon']) : null;

        $sql = "INSERT INTO site_settings (id, site_title, tagline, phone, email, physical_address, postal_address, facebook, twitter, linkedin, youtube, logo, favicon) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                site_title=VALUES(site_title), tagline=VALUES(tagline), phone=VALUES(phone), 
                email=VALUES(email), physical_address=VALUES(physical_address), postal_address=VALUES(postal_address),
                facebook=VALUES(facebook), twitter=VALUES(twitter), linkedin=VALUES(linkedin), 
                youtube=VALUES(youtube), logo=COALESCE(VALUES(logo), logo), favicon=COALESCE(VALUES(favicon), favicon)";

        $params = [$site_title, $tagline, $phone, $email, $physical_address, $postal_address, $facebook, $twitter, $linkedin, $youtube, $logo, $favicon];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success_message'] = "✅ Settings updated successfully!";
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

<!-- SETTINGS CONTENT -->
<div class="dashboard-content">
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-cogs"></i>
                Site Configuration
            </h1>
            <p class="page-subtitle">Manage branding, contact information, and social media links</p>
        </div>
        <div class="header-actions">
            <button type="button" class="btn btn-secondary" onclick="previewSite()">
                <i class="fas fa-eye"></i> Preview Site
            </button>
        </div>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="alert-content">
                <?= htmlspecialchars($success) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <div class="alert-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="alert-content">
                <?= htmlspecialchars($error) ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="settings-form" id="settingsForm">
        <div class="form-sections">
            <!-- Branding Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div class="section-title">
                        <h3>Branding & Identity</h3>
                        <p>Set your organization's visual identity and branding elements</p>
                    </div>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="site_title" class="form-label required">
                                Site Title
                            </label>
                            <input type="text" id="site_title" name="site_title" 
                                   value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>" 
                                   class="form-input" required
                                   placeholder="Enter your organization name">
                            <div class="form-hint">This will appear in the browser tab and site header</div>
                        </div>

                        <div class="form-group">
                            <label for="tagline" class="form-label">Tagline</label>
                            <input type="text" id="tagline" name="tagline" 
                                   value="<?= htmlspecialchars($settings['tagline'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="Brief description of your organization">
                            <div class="form-hint">A short, memorable phrase that describes your mission</div>
                        </div>
                    </div>

                    <div class="form-grid two-column">
                        <div class="form-group">
                            <label class="form-label">Logo</label>
                            <div class="file-upload-card">
                                <?php if(!empty($settings['logo'])): ?>
                                    <div class="current-file">
                                        <div class="file-preview">
                                            <img src="../uploads/<?= htmlspecialchars($settings['logo']) ?>" 
                                                 alt="Current Logo" class="preview-image">
                                        </div>
                                        <div class="file-info">
                                            <span class="file-name"><?= htmlspecialchars($settings['logo']) ?></span>
                                            <a href="../uploads/<?= htmlspecialchars($settings['logo']) ?>" 
                                               target="_blank" class="file-action">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="upload-area" id="logoUploadArea">
                                    <input type="file" id="logo" name="logo" accept="image/*" class="file-input">
                                    <div class="upload-content">
                                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                        <div class="upload-text">
                                            <h4>Upload Logo</h4>
                                            <p>Drag & drop or click to browse</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="upload-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Recommended: PNG, SVG or JPG • Max 2MB • Optimal size: 200x60px
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Favicon</label>
                            <div class="file-upload-card">
                                <?php if(!empty($settings['favicon'])): ?>
                                    <div class="current-file">
                                        <div class="file-preview">
                                            <img src="../uploads/<?= htmlspecialchars($settings['favicon']) ?>" 
                                                 alt="Current Favicon" class="preview-image favicon">
                                        </div>
                                        <div class="file-info">
                                            <span class="file-name"><?= htmlspecialchars($settings['favicon']) ?></span>
                                            <a href="../uploads/<?= htmlspecialchars($settings['favicon']) ?>" 
                                               target="_blank" class="file-action">
                                                <i class="fas fa-external-link-alt"></i> View
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="upload-area" id="faviconUploadArea">
                                    <input type="file" id="favicon" name="favicon" accept="image/*" class="file-input">
                                    <div class="upload-content">
                                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                        <div class="upload-text">
                                            <h4>Upload Favicon</h4>
                                            <p>Drag & drop or click to browse</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="upload-hint">
                                    <i class="fas fa-info-circle"></i>
                                    Recommended: ICO or PNG • 32x32 pixels • Max 500KB
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Contact Information Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <div class="section-title">
                        <h3>Contact Information</h3>
                        <p>How people can get in touch with your organization</p>
                    </div>
                </div>
                <div class="section-content">
                    <div class="form-grid two-column">
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="+1 (555) 123-4567">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($settings['email'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="contact@example.com">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="physical_address" class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Physical Address
                            </label>
                            <textarea id="physical_address" name="physical_address" 
                                      class="form-textarea" rows="3"
                                      placeholder="Enter your physical office address"><?= htmlspecialchars($settings['physical_address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="postal_address" class="form-label">
                                <i class="fas fa-envelope-open-text"></i> Postal Address
                            </label>
                            <textarea id="postal_address" name="postal_address" 
                                      class="form-textarea" rows="3"
                                      placeholder="Enter your mailing address"><?= htmlspecialchars($settings['postal_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Social Media Section -->
            <section class="form-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <div class="section-title">
                        <h3>Social Media Links</h3>
                        <p>Connect your organization's social media profiles</p>
                    </div>
                </div>
                <div class="section-content">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="facebook" class="form-label">
                                <i class="fab fa-facebook"></i> Facebook URL
                            </label>
                            <input type="url" id="facebook" name="facebook" 
                                   value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="https://facebook.com/yourpage">
                        </div>

                        <div class="form-group">
                            <label for="twitter" class="form-label">
                                <i class="fab fa-twitter"></i> Twitter URL
                            </label>
                            <input type="url" id="twitter" name="twitter" 
                                   value="<?= htmlspecialchars($settings['twitter'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="https://twitter.com/yourprofile">
                        </div>

                        <div class="form-group">
                            <label for="linkedin" class="form-label">
                                <i class="fab fa-linkedin"></i> LinkedIn URL
                            </label>
                            <input type="url" id="linkedin" name="linkedin" 
                                   value="<?= htmlspecialchars($settings['linkedin'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="https://linkedin.com/company/yourcompany">
                        </div>

                        <div class="form-group">
                            <label for="youtube" class="form-label">
                                <i class="fab fa-youtube"></i> YouTube URL
                            </label>
                            <input type="url" id="youtube" name="youtube" 
                                   value="<?= htmlspecialchars($settings['youtube'] ?? '') ?>" 
                                   class="form-input"
                                   placeholder="https://youtube.com/yourchannel">
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">
                <i class="fas fa-undo"></i> Reset Changes
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<style>
/* Enhanced Styles */
.dashboard-content {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 3rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title i {
    color: #2b6cb0;
}

.page-subtitle {
    color: #718096;
    margin: 0;
    font-size: 1.1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

/* Alerts */
.alert {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border-left: 4px solid;
}

.alert-success {
    background: #f0fff4;
    border-left-color: #38a169;
    color: #22543d;
}

.alert-error {
    background: #fed7d7;
    border-left-color: #e53e3e;
    color: #742a2a;
}

.alert-icon {
    font-size: 1.25rem;
    margin-top: 0.1rem;
}

.alert-content {
    flex: 1;
}

/* Form Sections */
.form-sections {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    background: white;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 2rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.section-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #2b6cb0, #38b2ac);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.section-title h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #2d3748;
}

.section-title p {
    margin: 0;
    color: #718096;
    font-size: 0.95rem;
}

.section-content {
    padding: 2rem;
}

/* Form Grid */
.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-grid.two-column {
    grid-template-columns: 1fr 1fr;
}

/* Form Elements */
.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label.required::after {
    content: " *";
    color: #e53e3e;
}

.form-input,
.form-textarea {
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #2b6cb0;
    box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
    font-family: inherit;
}

.form-hint {
    font-size: 0.875rem;
    color: #718096;
    margin-top: 0.5rem;
}

/* File Upload */
.file-upload-card {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.current-file {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.file-preview img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.file-preview img.favicon {
    width: 32px;
    height: 32px;
}

.file-info {
    flex: 1;
}

.file-name {
    display: block;
    font-weight: 500;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.file-action {
    font-size: 0.875rem;
    color: #2b6cb0;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.file-action:hover {
    text-decoration: underline;
}

.upload-area {
    position: relative;
    border: 2px dashed #cbd5e0;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
    background: #fafafa;
}

.upload-area:hover {
    border-color: #2b6cb0;
    background: #f0f9ff;
}

.upload-area.dragover {
    border-color: #2b6cb0;
    background: #ebf8ff;
}

.file-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.upload-icon {
    font-size: 2.5rem;
    color: #a0aec0;
}

.upload-text h4 {
    margin: 0 0 0.5rem 0;
    color: #2d3748;
    font-size: 1.1rem;
}

.upload-text p {
    margin: 0;
    color: #718096;
    font-size: 0.9rem;
}

.upload-hint {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #718096;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 6px;
}

.upload-hint i {
    color: #2b6cb0;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary {
    background: #2b6cb0;
    color: white;
}

.btn-primary:hover {
    background: #2c5282;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
}

.btn-secondary {
    background: white;
    color: #4a5568;
    border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f7fafc;
    border-color: #cbd5e0;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 2rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-content {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-end;
    }

    .form-grid.two-column {
        grid-template-columns: 1fr;
    }

    .section-header,
    .section-content {
        padding: 1.5rem;
    }

    .form-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .current-file {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .section-header {
        flex-direction: column;
        text-align: center;
    }

    .section-icon {
        align-self: center;
    }
}
</style>

<script>
// Enhanced form functionality
function previewSite() {
    window.open('../public/index.php', '_blank');
}

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? Any unsaved changes will be lost.')) {
        document.getElementById('settingsForm').reset();
    }
}

// Enhanced file upload functionality
document.addEventListener('DOMContentLoaded', function() {
    // Setup drag and drop for both upload areas
    const uploadAreas = [
        { area: document.getElementById('logoUploadArea'), input: document.getElementById('logo') },
        { area: document.getElementById('faviconUploadArea'), input: document.getElementById('favicon') }
    ];

    uploadAreas.forEach(({ area, input }) => {
        if (!area || !input) return;

        const events = ['dragenter', 'dragover', 'dragleave', 'drop'];
        events.forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.remove('dragover'), false);
        });

        area.addEventListener('drop', handleDrop, false);
        area.addEventListener('click', () => input.click());

        input.addEventListener('change', handleFileSelect);
    });

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        const input = e.target.querySelector('.file-input') || e.target.closest('.upload-area').querySelector('.file-input');
        input.files = files;
        handleFileSelect.call(input);
    }

    function handleFileSelect(e) {
        const input = this;
        const file = input.files[0];
        const area = input.closest('.upload-area');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                area.innerHTML = `
                    <div class="upload-content">
                        <i class="fas fa-check-circle upload-icon" style="color: #38a169;"></i>
                        <div class="upload-text">
                            <h4>${file.name}</h4>
                            <p>File selected - click to choose a different file</p>
                        </div>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    }

    // Form validation
    const form = document.getElementById('settingsForm');
    form.addEventListener('submit', function(e) {
        const siteTitle = document.getElementById('site_title').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!siteTitle) {
            e.preventDefault();
            showError('Please enter a site title', 'site_title');
            return false;
        }
        
        if (email && !isValidEmail(email)) {
            e.preventDefault();
            showError('Please enter a valid email address', 'email');
            return false;
        }
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function showError(message, fieldId) {
        alert(message);
        const field = document.getElementById(fieldId);
        if (field) {
            field.focus();
            field.style.borderColor = '#e53e3e';
        }
    }

    // Add real-time validation for email
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            if (this.value.trim() && !isValidEmail(this.value.trim())) {
                this.style.borderColor = '#e53e3e';
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    }
});
</script>