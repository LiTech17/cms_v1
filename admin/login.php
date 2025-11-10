<?php
// admin/login.php

require_once "../config/database.php"; 
require_once "../core/auth.php"; // Include the updated auth file for setLoginSession()

// --- SECURITY CONSTANTS ---
const MAX_ATTEMPTS = 5;       // Max failed attempts allowed
const LOCKOUT_TIME = 15;      // Time window in minutes for checking attempts
const COOLDOWN_MINUTES = 30;  // Cooldown period in minutes if MAX_ATTEMPTS is reached
const CLEANUP_HOURS = 2;      // How old records must be before cleanup
// --------------------------

// --- DEBUG FLAG (Set to true to enable logging) ---
$debug_mode = true;
// ----------------------------------------------------

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

// Function to check and return the number of failed attempts
function getFailedAttempts($pdo, $ip_address) {
    $lockout_time = LOCKOUT_TIME;
    
    // Find failed attempts in the last LOCKOUT_TIME minutes
    $stmt = $pdo->prepare("
        SELECT COUNT(id) AS count 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_timestamp > DATE_SUB(NOW(), INTERVAL $lockout_time MINUTE)
    ");
    $stmt->execute([$ip_address]);
    return (int)$stmt->fetchColumn();
}

// Function to record a failed attempt
function recordFailedAttempt($pdo, $ip_address) {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_timestamp) VALUES (?, NOW())");
    $stmt->execute([$ip_address]);
}

// Function to check for an active lockout
function isLockedOut($pdo, $ip_address) {
    return getFailedAttempts($pdo, $ip_address) >= MAX_ATTEMPTS;
}

/**
 * NEW: Cleans up very old, unnecessary login attempt records to keep the database fast.
 */
function cleanupOldAttempts($pdo) {
    $cleanup_hours = CLEANUP_HOURS;
    $stmt = $pdo->prepare("
        DELETE FROM login_attempts 
        WHERE attempt_timestamp < DATE_SUB(NOW(), INTERVAL $cleanup_hours HOUR)
    ");
    $stmt->execute();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ALWAYS PERFORM CLEANUP ON POST
    cleanupOldAttempts($pdo);

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? ''; 
    $error = null;

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    }

    // --- BRUTE FORCE PREVENTION CHECK ---
    if (isLockedOut($pdo, $ip_address)) {
        // Block further processing and show a generic error
        $error = "Too many failed login attempts. Please try again after " . COOLDOWN_MINUTES . " minutes.";
        if ($debug_mode) error_log("Login Blocked: IP {$ip_address} is locked out.");
    }
    // ------------------------------------

    if (!isset($error)) {
        if ($debug_mode) {
            error_log("--- Dual Role Login Attempt ---");
            error_log("Input Username: " . $username);
        }
        
        $login_successful = false;
        $user_data = null;
        $role = null;

        // ------------------------------------------------
        // 2. ATTEMPT TO LOG IN AS ADMIN
        // ------------------------------------------------
        $stmt_admin = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt_admin->execute([$username]);
        $admin_data = $stmt_admin->fetch();

        if ($admin_data && password_verify($password, $admin_data['password'])) {
            $login_successful = true;
            $user_data = $admin_data;
            $role = 'admin';
            $redirect_path = 'index.php';
            if ($debug_mode) error_log("Login Success: Authenticated as **ADMIN**.");
        }

        // ------------------------------------------------
        // 3. ATTEMPT TO LOG IN AS EDITOR (Only if Admin failed)
        // ------------------------------------------------
        if (!$login_successful) {
            if ($debug_mode) error_log("Admin check failed. Checking Editor table...");

            // Note: The editors table uses `password_hash`, so we alias it to `password`
            $stmt_editor = $pdo->prepare("SELECT id, username, password_hash AS password FROM editors WHERE username = ?");
            $stmt_editor->execute([$username]);
            $editor_data = $stmt_editor->fetch();

            if ($editor_data && password_verify($password, $editor_data['password'])) {
                $login_successful = true;
                $user_data = $editor_data;
                $role = 'editor';
                $redirect_path = '../editor/index.php';
                if ($debug_mode) error_log("Login Success: Authenticated as **EDITOR**.");
            }
        }

        // ------------------------------------------------
        // 4. FINAL RESULT AND REDIRECT/FAILURE HANDLING
        // ------------------------------------------------
        if ($login_successful) {
            // Success: Set session and redirect
            setLoginSession($user_data, $role);
            header("Location: " . $redirect_path);
            exit();
            
        } else {
            // Failure: Record the attempt and show error
            recordFailedAttempt($pdo, $ip_address);
            if ($debug_mode) error_log("Login Failure: Invalid credentials recorded for {$ip_address}.");
            
            // Re-check lockout status after recording failure to update error message if necessary
            if (isLockedOut($pdo, $ip_address)) {
                $error = "Too many failed login attempts. Please try again after " . COOLDOWN_MINUTES . " minutes.";
            } else {
                // Generic error for dictionary attack protection (don't reveal if user exists)
                $error = "Invalid username or password";
            }
        }
        
        if ($debug_mode) {
            error_log("--- Dual Role Login Finished ---");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | NGO CMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS block remains the same */
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --danger: #e53e3e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary), #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .logo h1 {
            color: var(--text);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid var(--danger);
            background: #fed7d7;
            color: #742a2a;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-light);
            font-size: 0.875rem;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .logo i {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-hands-helping"></i>
                <h1>NGO CMS</h1>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-input" placeholder="Username" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-input" placeholder="Password" required>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="footer">
                <p>NGO Content Management System</p>
            </div>
        </div>
    </div>

    <script>
        // Add focus effects
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = 'var(--primary)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('i').style.color = 'var(--text-light)';
            });
        });

        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>