<?php
// Assuming this file is located at /admin/login.php and database.php is at /config/database.php
require_once "../config/database.php"; 
session_start();

// --- DEBUG FLAG (Set to true to enable logging) ---
$debug_mode = true;
// ----------------------------------------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? ''; 

    if ($debug_mode) {
        error_log("--- Admin Login Attempt ---");
        error_log("Input Username: " . $username);
        // DO NOT log the raw password in production! Only for temporary debugging.
        error_log("Input Password: " . $password); 
    }

    // 1. Prepare and execute the query to fetch the admin record
    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($debug_mode) {
        if ($admin) {
            error_log("User found in DB: " . $admin['username']);
            error_log("Stored Hash: " . $admin['password']);
        } else {
            error_log("User **NOT** found in DB for username: " . $username);
        }
    }

    // 2. CRITICAL FIX: Use password_verify() to check the raw password against the stored hash
    if ($admin && password_verify($password, $admin['password'])) {
        
        if ($debug_mode) {
            error_log("PASSWORD VERIFICATION: **SUCCESS**");
        }
        
        // Login successful
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        header("Location: index.php");
        exit();
    } else {
        // Login failed
        
        if ($debug_mode) {
            error_log("PASSWORD VERIFICATION: **FAILED**");
        }

        $error = "Invalid username or password";
    }
    
    if ($debug_mode) {
        error_log("--- Admin Login Finished ---");
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