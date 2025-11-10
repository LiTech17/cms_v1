<?php
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

checkEditorLogin(); 

$editor_id = $_SESSION['editor_id'] ?? null;
$error = null;
$success = null;

if (!$editor_id) {
    $error = "Session error: Editor ID not found.";
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM editors WHERE id = ?");
            $stmt->execute([$editor_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                if (updatePassword($editor_id, $new_password)) {
                    $success = "Password successfully updated.";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            $error = "An unexpected error occurred.";
        }
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';

    if (empty($full_name) || empty($email) || empty($username)) {
        $error = "Full Name, Username, and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM editors WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $editor_id]);
            
            if ($stmt->fetch()) {
                 $error = "Username or email already in use.";
            } else {
                $stmt = $pdo->prepare("UPDATE editors SET full_name = ?, email = ?, username = ? WHERE id = ?");
                
                if ($stmt->execute([$full_name, $email, $username, $editor_id])) {
                    $_SESSION['username'] = $username; 
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile.";
                }
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = "An unexpected error occurred.";
        }
    }
}

// Fetch current profile data
$current_data = ['full_name' => '', 'email' => '', 'username' => ''];
if ($editor_id) {
    try {
        $stmt = $pdo->prepare("SELECT username, email, full_name FROM editors WHERE id = ?");
        $stmt->execute([$editor_id]);
        $current_data = $stmt->fetch(PDO::FETCH_ASSOC) ?: $current_data;
    } catch (Exception $e) {
        $error = "Error loading profile data.";
    }
}

// Preserve form data on error
if ($error && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $current_data['full_name'] = $full_name;
    $current_data['email'] = $email;
    $current_data['username'] = $username;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
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
            body {
                padding: 1rem;
            }

            .card-body {
                padding: 1.25rem;
            }

            .profile-header h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .card-body {
                padding: 1rem;
            }

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
                <i class="fas fa-user-cog"></i>
                Profile Settings
            </h1>
            <p>Manage your account information and security</p>
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
                <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" class="form-input" id="full_name" name="full_name" 
                               value="<?= htmlspecialchars($current_data['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-input" id="username" name="username" 
                               value="<?= htmlspecialchars($current_data['username']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" class="form-input" id="email" name="email" 
                               value="<?= htmlspecialchars($current_data['email']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
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