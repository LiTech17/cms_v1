<?php
require_once "../config/database.php";
require_once "../core/analytics.php"; // <--- ADDED: Include analytics core
include "partials/header.php";

// Initialize session to use CSRF token
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate a CSRF token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// PHP logic for form submission handling
$message = null;
$error = null;

// Initialize variables to hold previous input (for sticky form fields)
// Note: We use raw POST data initially, and sanitize it before database insertion
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$location = $_POST['location'] ?? '';
$user_message = $_POST['message'] ?? '';
$honeypot = $_POST['website_url'] ?? ''; // Honeypot field

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CRITICAL: CSRF Token Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security error: Invalid form submission token. Please refresh the page and try again.";
    } 
    // 2. Honeypot Check (Simple bot defense)
    elseif (!empty($honeypot)) {
        // If a bot fills this hidden field, silently exit or set an error
        $error = "Submission blocked. Possible bot activity detected.";
    } 
    // 3. Validation (Remaining logic)
    elseif (empty($name) || empty($email) || empty($phone) || empty($location) || empty($user_message)) {
        $error = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } 
    else {
        
        // --- Input Sanitization (Before DB insertion) ---
        $sanitized_name = filter_var(trim($name), FILTER_SANITIZE_SPECIAL_CHARS);
        $sanitized_email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $sanitized_phone = filter_var(trim($phone), FILTER_SANITIZE_SPECIAL_CHARS);
        $sanitized_location = filter_var(trim($location), FILTER_SANITIZE_SPECIAL_CHARS);
        $sanitized_message = filter_var(trim($user_message), FILTER_SANITIZE_SPECIAL_CHARS);

        // --- Database Insertion Logic (Already secure via Prepared Statements) ---
        try {
            $stmt = $pdo->prepare("
                INSERT INTO inbox (sender_name, sender_email, sender_phone, sender_location, message_content)
                VALUES (:name, :email, :phone, :location, :message)
            ");

            $stmt->execute([
                ':name' => $sanitized_name,
                ':email' => $sanitized_email,
                ':phone' => $sanitized_phone,
                ':location' => $sanitized_location,
                ':message' => $sanitized_message
            ]);

            $message = "Thank you, " . htmlspecialchars($sanitized_name) . "! Your message has been sent successfully and saved to our inbox.";
            
            // --------------------------------------------------------
            // 4. ANALYTICS TRACKING: Log successful form submission
            // --------------------------------------------------------
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $current_url = $_SERVER['REQUEST_URI'] ?? '/contact.php';

            log_activity(
                'form_submission', // Event Type
                $current_url,     // Context/URL
                $ip_address,      // Using IP as the "user" identifier (for non-logged-in users)
                session_id(),     // Session ID
                'Contact Form from ' . $sanitized_email // Optional: Add details
            );
            // --------------------------------------------------------

            // Clear form inputs and reset token after successful send
            $name = $email = $phone = $location = $user_message = '';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
            
        } catch (PDOException $e) {
            // Log error for debugging: error_log($e->getMessage()); 
            $error = "Sorry, a system error occurred while submitting your message. Please try again later.";
        }
    }
}
?>

<style>
:root {
    /* Light Theme (Matches global) */
    --primary: #2b6cb0;
    --primary-dark: #2c5282;
    --accent: #38b2ac;
    --success: #38a169;
    --danger: #e53e3e;
    --warning: #d69e2e;

    /* Backgrounds */
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #f1f5f9;
    --bg-card: #ffffff;

    /* Text */
    --text-primary: #1a202c;
    --text-secondary: #4a5568;
    --text-muted: #718096;

    /* Borders & Shadows */
    --border-primary: #e2e8f0;
    --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

    --radius: 12px;
}

/* === DARK THEME === */
[data-theme="dark"] {
    --primary: #63b3ed;
    --primary-dark: #4299e1;
    --accent: #4fd1c7;
    --success: #68d391;
    --danger: #fc8181;
    --warning: #faf089;

    --bg-primary: #0f1419;
    --bg-secondary: #1a202c;
    --bg-tertiary: #2d3748;
    --bg-card: #1e2736;

    --text-primary: #f7fafc;
    --text-secondary: #e2e8f0;
    --text-muted: #a0aec0;

    --border-primary: #2d3748;
    --shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
}

/* === LAYOUT === */
.contact-container {
    padding: 5rem 0;
    max-width: 800px;
    margin: 0 auto;
}

.form-card {
    background: var(--bg-card);
    padding: 2.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-primary);
    transition: all 0.3s ease;
}

h2 {
    color: var(--text-primary);
}

p {
    color: var(--text-secondary);
}

/* === FORM === */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.form-group.full-width { grid-column: 1 / -1; }

.form-input, textarea.form-input {
    width: 100%;
    padding: 1rem;
    border: 2px solid var(--border-primary);
    border-radius: 8px;
    font-size: 1rem;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-family: inherit;
    transition: all 0.3s ease;
}
.form-input:focus, textarea.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.3);
}
textarea.form-input { resize: vertical; min-height: 150px; }

/* === BUTTON === */
.btn-submit {
    width: 100%;
    padding: 1rem;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}
.btn-submit:hover {
    background: var(--primary-dark);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* === ALERTS === */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    transition: background 0.3s ease, color 0.3s ease;
}
.alert.error {
    border-left: 4px solid var(--danger);
    background: #fed7d7;
    color: #742a2a;
}
.alert.success {
    border-left: 4px solid var(--success);
    background: #c6f6d5;
    color: #276749;
}

/* DARK THEME ALERTS */
[data-theme="dark"] .alert.error {
    background: rgba(229, 62, 62, 0.15);
    color: #feb2b2;
}
[data-theme="dark"] .alert.success {
    background: rgba(56, 161, 105, 0.15);
    color: #9ae6b4;
}

/* === HONEYPOT === */
.honeypot {
    position: absolute !important;
    left: -9999px !important;
    visibility: hidden !important;
}

/* === RESPONSIVE === */
@media (max-width: 600px) {
    .form-grid { grid-template-columns: 1fr; }
    .contact-container { padding: 3rem 1rem; }
}
</style>


<div class="container contact-container">
    <div class="form-card">
        <h2 style="margin-bottom: 1.5rem; font-size: 2rem; color: var(--primary-dark); text-align: center;">
            Contact Us
        </h2>
        <p style="margin-bottom: 2rem; color: #64748b; text-align: center;">
            Please fill out the form below and we'll get back to you as soon as possible.
        </p>

        <?php if(isset($error)): ?>
            <div class="alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if(isset($message)): ?>
            <div class="alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group honeypot">
                <label for="website_url">Website URL (Leave blank)</label>
                <input type="text" id="website_url" name="website_url">
            </div>

            <div class="form-grid">
                
                <div class="form-group">
                    <input type="text" name="name" class="form-input" placeholder="Your Full Name" required 
                           value="<?= htmlspecialchars($name) ?>">
                </div>

                <div class="form-group">
                    <input type="email" name="email" class="form-input" placeholder="Email Address" required
                           value="<?= htmlspecialchars($email) ?>">
                </div>
                
                <div class="form-group">
                    <input type="tel" name="phone" class="form-input" placeholder="Phone Number (e.g., +265...)" required
                           value="<?= htmlspecialchars($phone) ?>">
                </div>
                
                <div class="form-group">
                    <input type="text" name="location" class="form-input" placeholder="Your City/Location" required
                           value="<?= htmlspecialchars($location) ?>">
                </div>

                <div class="form-group full-width">
                    <textarea name="message" class="form-input" placeholder="Type your detailed message here..." required><?= htmlspecialchars($user_message) ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                Send Inquiry
            </button>
        </form>
    </div>
</div>

<?php 
include "partials/footer.php"; 
?>