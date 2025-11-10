<?php
// admin/profile.php - Profile management and password change for Administrator

// 1. Security & Configuration
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

// Ensure the user is logged in as an Admin (CRITICAL CHANGE)
checkAdminLogin(); 

// Use admin-specific session key (CRITICAL CHANGE)
$admin_id = $_SESSION['admin_id'] ?? null;
$error = null;
$success = null;

if (!$admin_id) {
    $error = "Session error: Admin ID not found.";
}

// -----------------------------------------------------------
// 2. Form Submission Handlers
// -----------------------------------------------------------

// A. Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Sanitization is important for all user input
    $current_password = filter_input(INPUT_POST, 'current_password', FILTER_SANITIZE_STRING);
    $new_password = filter_input(INPUT_POST, 'new_password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        try {
            // 1. Verify current password (CRITICAL CHANGE: Use 'admins' table)
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // 2. Update password using the enhanced function (CRITICAL CHANGE: Pass 'admin' role)
                if (updatePassword($admin_id, $new_password, 'admin')) {
                    $success = "Password successfully updated.";
                } else {
                    $error = "Failed to update password due to a database error.";
                }
            } else {
                $error = "The current password entered is incorrect.";
            }

        } catch (Exception $e) {
            error_log("Admin Password change fatal error: " . $e->getMessage());
            $error = "An unexpected error occurred during password change.";
        }
    }
}

// B. Handle Username Update (CRITICAL CHANGE: Only update username, other fields not in 'admins' table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);

    if (empty($username)) {
        $error = "Username is required.";
    } else {
        try {
            // Check for duplicate username (excluding current user) (CRITICAL CHANGE: Use 'admins' table)
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmt->execute([$username, $admin_id]);
            
            if ($stmt->fetch()) {
                 $error = "The provided username is already in use by another account.";
            } else {
                // Update the profile settings (CRITICAL CHANGE: Only update username)
                $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                
                if ($stmt->execute([$username, $admin_id])) {
                    // Update session variable
                    $_SESSION['username'] = $username; 
                    $success = "Username updated successfully!";
                } else {
                    $error = "Failed to update username due to a database error.";
                }
            }
        } catch (Exception $e) {
            error_log("Admin Profile update fatal error: " . $e->getMessage());
            $error = "An unexpected error occurred during username update.";
        }
    }
}


// -----------------------------------------------------------
// 3. Fetch Current Profile Data (Only Username)
// -----------------------------------------------------------

$current_data = ['username' => ''];
if ($admin_id) {
    try {
        // Fetch only available columns from 'admins' table
        $stmt = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $current_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: $current_data;
        
        if (!$current_data) {
            $error = "Could not load user data.";
            $current_data = ['username' => ''];
        }
    } catch (Exception $e) {
        $error = "Error fetching current profile data.";
    }
}

// Re-set username from POST if there was an error
if ($error && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $current_data['username'] = $username;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
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

        .profile-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-header {
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .profile-header p {
            color: var(--text-light);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #f0fdf4;
            border-left-color: var(--success);
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border-left-color: var(--danger);
            color: #991b1b;
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
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--white);
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
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
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.2s;
            background: var(--white);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-text {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-weak { color: #ef4444; }
        .strength-medium { color: #f59e0b; }
        .strength-strong { color: #10b981; }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            .card-body { padding: 1.25rem; }
            .profile-header h1 { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            .card-body { padding: 1rem; }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>
                <i class="fas fa-user-shield"></i>
                Admin Profile Settings
            </h1>
            <p>Manage your administrative username and security credentials.</p>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-edit"></i> Admin Username</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-input" id="username" name="username" 
                               value="<?= htmlspecialchars($current_data['username']) ?>" required>
                        <div class="form-text">This is the unique name you use to log in.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Username
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" class="form-input" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" class="form-input" id="new_password" name="new_password" required>
                        <div class="form-text">Must be at least 8 characters long</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Simple password strength indicator
        const passwordInput = document.getElementById('new_password');
        const strengthText = document.createElement('div');
        strengthText.className = 'password-strength';
        passwordInput.parentNode.appendChild(strengthText);

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = '';
            let className = '';

            if (password.length === 0) {
                strength = '';
            } else if (password.length < 8) {
                strength = 'Weak';
                className = 'strength-weak';
            } else if (password.length < 12) {
                strength = 'Medium';
                className = 'strength-medium';
            } else {
                strength = 'Strong';
                className = 'strength-strong';
            }

            strengthText.textContent = strength;
            strengthText.className = `password-strength ${className}`;
        });
    </script>
</body>
</html>