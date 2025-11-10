<?php
// admin/editor_form.php - Unified page for Adding (Create) and Editing (Update) Editors

// 1. Security & Configuration
require_once "../config/database.php"; 
require_once "../core/auth.php"; 
checkAdminLogin(); // Only Admins can access this page

$editor_id = (int)($_GET['id'] ?? 0);
$is_editing = $editor_id > 0;

$editor = [];
$error = '';
$success = '';

// Default values for form fields
$username = '';
$email = '';

// --- 2. Fetch Existing Data (If Editing) ---
if ($is_editing) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, email FROM editors WHERE id = ?");
        $stmt->execute([$editor_id]);
        $editor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$editor) {
            // Editor not found, redirect to list
            $_SESSION['error'] = "Editor not found.";
            header("Location: editors.php");
            exit();
        }

        $username = $editor['username'];
        $email = $editor['email'];

    } catch (PDOException $e) {
        $error = "Database Error during fetch: " . $e->getMessage();
    }
}

// --- 3. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (empty($username) || empty($email)) {
        $error = "Username and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } 
    
    // Password Validation (Required for Create, Optional for Edit)
    if (!$is_editing || (!empty($password) || !empty($confirm_password))) {
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        }
    }

    if (empty($error)) {
        try {
            // Check for duplicate username/email (excluding current editor if editing)
            $sql_check = "SELECT id FROM editors WHERE (username = ? OR email = ?) AND id != ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$username, $email, $editor_id]);
            
            if ($stmt_check->fetch()) {
                $error = "A user with that username or email already exists.";
            } else {
                
                $params = [];
                $sql = "";
                
                // --- UPDATE LOGIC ---
                if ($is_editing) {
                    $sql = "UPDATE editors SET username = ?, email = ?";
                    $params = [$username, $email];

                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql .= ", password_hash = ?";
                        $params[] = $hashed_password;
                    }

                    $sql .= " WHERE id = ?";
                    $params[] = $editor_id;
                    $message = "Editor **{$username}** updated successfully!";

                // --- CREATE LOGIC ---
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO editors (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())";
                    $params = [$username, $email, $hashed_password];
                    $message = "New editor **{$username}** created successfully!";
                }

                // Execute the query
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    $_SESSION['message'] = $message;
                    // Redirect to list after success
                    header("Location: editors.php");
                    exit();
                } else {
                    $error = "Failed to save editor due to a database error.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database Transaction Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_editing ? 'Edit Editor' : 'Add New Editor' ?> | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container" style="max-width: 600px; margin-top: 50px;">
        <h2 class="mb-4 text-primary">
            <i class="fas <?= $is_editing ? 'fa-user-cog' : 'fa-user-plus' ?> me-2"></i> 
            <?= $is_editing ? 'Edit Editor: ' . htmlspecialchars($username) : 'Add New Editor' ?>
        </h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm">
            <form method="POST">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" 
                           value="<?= htmlspecialchars($username) ?>" required <?= $is_editing ? 'readonly' : '' ?>>
                    <?php if($is_editing): ?>
                        <small class="form-text text-muted">Username cannot be changed after creation.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" 
                           placeholder="<?= $is_editing ? 'Leave blank to keep current password' : 'Enter new password (Min 8 characters)' ?>" 
                           <?= $is_editing ? '' : 'required' ?>>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-<?= $is_editing ? 'info' : 'success' ?> btn-lg text-white">
                        <i class="fas fa-save me-2"></i> <?= $is_editing ? 'Save Changes' : 'Create Editor' ?>
                    </button>
                    <a href="editors.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Cancel / Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>