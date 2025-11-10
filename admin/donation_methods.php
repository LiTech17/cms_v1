<?php
require_once "../config/database.php";
require_once "../core/auth.php";
require_once "../core/upload.php";
checkAdminLogin();

// CREATE or UPDATE donation methods
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['method_name'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $merchant_code = trim($_POST['merchant_code'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $qr_image = isset($_FILES['qr_image']['name']) && $_FILES['qr_image']['name'] !== '' ? uploadFile($_FILES['qr_image']) : null;

        // Validate required fields
        if (empty($name)) {
            throw new Exception("Method name is required.");
        }

        $stmt = $pdo->prepare("INSERT INTO donation_methods (method_name, account_name, account_number, merchant_code, qr_image, instructions)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $account_name, $account_number, $merchant_code, $qr_image, $instructions]);
        
        $_SESSION['success_message'] = "✅ Donation method added successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// DELETE method
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM donation_methods WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success_message'] = "🗑️ Donation method deleted successfully!";
    header("Location: donation_methods.php");
    exit();
}

// FETCH donation methods
$methods = $pdo->query("SELECT * FROM donation_methods ORDER BY id DESC")->fetchAll();

// Check for success message from redirect
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Methods Manager | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --success: #38a169;
            --danger: #e53e3e;
            --danger-dark: #c53030;
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
            max-width: 1200px;
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
        .donation-form {
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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
            line-height: 1.4;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
        }

        .file-input {
            display: none;
        }

        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            background: var(--bg);
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.03);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(43, 108, 176, 0.08);
        }

        .upload-icon {
            font-size: 2rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
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

        /* Methods Grid */
        .methods-section {
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

        .methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .method-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px var(--shadow);
            border: 1px solid var(--border);
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
        }

        .method-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-md);
        }

        .method-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .method-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
            flex: 1;
        }

        .method-actions {
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

        .method-content {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .qr-container {
            text-align: center;
        }

        .qr-image {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: var(--radius-sm);
            border: 2px solid var(--border);
            padding: 0.5rem;
            background: white;
        }

        .qr-placeholder {
            width: 150px;
            height: 150px;
            background: var(--bg);
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-align: center;
            padding: 1rem;
        }

        .method-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .detail-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text);
            min-width: 120px;
            font-size: 0.9rem;
        }

        .detail-value {
            color: var(--text-light);
            flex: 1;
            font-size: 0.9rem;
        }

        .detail-value:empty::before {
            content: "Not provided";
            color: var(--text-muted);
            font-style: italic;
        }

        .instructions {
            background: var(--bg);
            padding: 1rem;
            border-radius: var(--radius-sm);
            border-left: 4px solid var(--primary);
            margin-top: 1rem;
            grid-column: 1 / -1;
        }

        .instructions p {
            margin: 0;
            color: var(--text);
            line-height: 1.5;
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

        .alert-error {
            background: #fed7d7;
            border-left-color: var(--danger);
            color: #742a2a;
        }

        /* Character Counter */
        .char-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.25rem;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .methods-grid {
                grid-template-columns: 1fr;
            }

            .form-content {
                padding: 1.25rem;
            }

            .method-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .qr-container {
                order: -1;
            }

            .detail-row {
                flex-direction: column;
                gap: 0.25rem;
                text-align: left;
            }

            .detail-label {
                min-width: auto;
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
            <div class="header-content">
                <h1>
                    <i class="fas fa-donate"></i>
                    Donation Methods
                </h1>
                <p class="page-subtitle">Manage payment options and donation channels for your supporters</p>
            </div>
            <div class="stats-badge">
                <?= count($methods) ?> Method<?= count($methods) !== 1 ? 's' : '' ?>
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

        <!-- Add Method Form -->
        <form method="POST" enctype="multipart/form-data" class="donation-form">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Donation Method</h2>
            </div>
            <div class="form-content">
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-group">
                        <label for="method_name" class="form-label required">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </label>
                        <input type="text" id="method_name" name="method_name" class="form-input" 
                               placeholder="e.g., Airtel Money, Bank Transfer, PayPal" required
                               value="<?= htmlspecialchars($_POST['method_name'] ?? '') ?>">
                        <div class="form-hint">
                            Name of the payment method or service
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="account_name" class="form-label">
                            <i class="fas fa-user"></i> Account Name
                        </label>
                        <input type="text" id="account_name" name="account_name" class="form-input" 
                               placeholder="Account holder name"
                               value="<?= htmlspecialchars($_POST['account_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="account_number" class="form-label">
                            <i class="fas fa-hashtag"></i> Account Number
                        </label>
                        <input type="text" id="account_number" name="account_number" class="form-input" 
                               placeholder="Phone number, account number, or ID"
                               value="<?= htmlspecialchars($_POST['account_number'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="merchant_code" class="form-label">
                            <i class="fas fa-store"></i> Merchant Code
                        </label>
                        <input type="text" id="merchant_code" name="merchant_code" class="form-input" 
                               placeholder="Merchant number or code"
                               value="<?= htmlspecialchars($_POST['merchant_code'] ?? '') ?>">
                    </div>

                    <!-- QR Code Upload -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-qrcode"></i> QR Code Image
                        </label>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-area" id="dropZone">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem;">Upload QR Code</h4>
                                <p class="form-hint" style="margin: 0;">Drag & drop or click to browse</p>
                                <input type="file" name="qr_image" class="file-input" id="qrInput" accept="image/*">
                            </div>
                        </div>
                        <div class="form-hint">
                            <strong>Optional:</strong> Upload a QR code for quick mobile payments
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="form-group full-width">
                        <label for="instructions" class="form-label">
                            <i class="fas fa-list-ol"></i> Payment Instructions
                        </label>
                        <textarea id="instructions" name="instructions" class="form-textarea" 
                                  placeholder="Step-by-step instructions for donors (e.g., Dial *211# then select Send Money to Merchant...)"
                                  maxlength="500"><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                        <div class="char-count" id="instructionsCount">0/500 characters</div>
                        <div class="form-hint">
                            Provide clear instructions to help donors complete their payment
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions" style="text-align: right; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Donation Method
                    </button>
                </div>
            </div>
        </form>

        <!-- Existing Methods Section -->
        <section class="methods-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-list"></i>
                    Available Donation Methods
                </h2>
            </div>

            <?php if (empty($methods)): ?>
                <div class="empty-state">
                    <i class="fas fa-donate"></i>
                    <h3>No Donation Methods Yet</h3>
                    <p>Add your first donation method to start accepting payments from supporters.</p>
                </div>
            <?php else: ?>
                <div class="methods-grid">
                    <?php foreach($methods as $method): ?>
                        <div class="method-card">
                            <div class="method-header">
                                <h3 class="method-name">
                                    <i class="fas fa-credit-card"></i>
                                    <?= htmlspecialchars($method['method_name']) ?>
                                </h3>
                                <div class="method-actions">
                                    <a href="?delete=<?= $method['id'] ?>" 
                                       class="btn-icon btn-danger"
                                       onclick="return confirm('Are you sure you want to delete the <?= addslashes($method['method_name']) ?> donation method? This action cannot be undone.')"
                                       title="Delete Method">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="method-content">
                                <div class="qr-container">
                                    <?php if($method['qr_image']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($method['qr_image']) ?>" 
                                             alt="QR Code for <?= htmlspecialchars($method['method_name']) ?>" 
                                             class="qr-image">
                                    <?php else: ?>
                                        <div class="qr-placeholder">
                                            <i class="fas fa-qrcode"></i><br>
                                            No QR Code
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="method-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Account Name:</span>
                                        <span class="detail-value"><?= htmlspecialchars($method['account_name']) ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label">Account Number:</span>
                                        <span class="detail-value"><?= htmlspecialchars($method['account_number']) ?></span>
                                    </div>

                                    <div class="detail-row">
                                        <span class="detail-label">Merchant Code:</span>
                                        <span class="detail-value"><?= htmlspecialchars($method['merchant_code']) ?></span>
                                    </div>
                                </div>

                                <?php if(!empty($method['instructions'])): ?>
                                    <div class="instructions">
                                        <p><strong>Instructions:</strong><br><?= nl2br(htmlspecialchars($method['instructions'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Enhanced file upload with drag & drop
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('qrInput');

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', handleDrop, false);
        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', handleFileSelect);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFileSelect();
        }

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file && file.type.startsWith('image/')) {
                dropZone.innerHTML = `
                    <i class="fas fa-check-circle upload-icon" style="color: #38a169;"></i>
                    <h4 style="margin-bottom: 0.5rem; font-size: 1.1rem;">${file.name}</h4>
                    <p class="form-hint" style="margin: 0;">Click to choose a different file</p>
                `;
            }
        }

        // Character counter for instructions
        const instructionsTextarea = document.getElementById('instructions');
        const instructionsCounter = document.getElementById('instructionsCount');

        function updateCharacterCount() {
            const length = instructionsTextarea.value.length;
            instructionsCounter.textContent = `${length}/500 characters`;
            
            // Update color based on usage
            instructionsCounter.className = 'char-count';
            if (length > 400) {
                instructionsCounter.style.color = '#dd6b20';
            }
            if (length > 480) {
                instructionsCounter.style.color = '#e53e3e';
            }
        }

        instructionsTextarea.addEventListener('input', updateCharacterCount);
        updateCharacterCount(); // Initialize counter

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const methodName = document.getElementById('method_name').value.trim();
            if (!methodName) {
                e.preventDefault();
                alert('Please enter a payment method name before saving.');
                document.getElementById('method_name').focus();
                return false;
            }
        });
    </script>
</body>
</html>