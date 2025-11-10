<?php
// core/auth.php
// Must be included on every page that requires authentication.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the user is logged in as an ADMIN.
 * If not logged in, redirects to the main login page.
 */
function checkAdminLogin() {
    // Check if the specific admin session variable is set.
    if (!isset($_SESSION['admin_id'])) {
        // Redirect to the public homepage (assuming public/index.php is the fallback)
        header("Location: ../public/index.php"); 
        exit();
    }
}

/**
 * Ensures the user is logged in as an EDITOR.
 * If not logged in, redirects to the main login page.
 */
function checkEditorLogin() {
    // Check if the specific editor session variable is set.
    if (!isset($_SESSION['editor_id'])) {
        // Redirect to the public homepage (assuming public/index.php is the fallback)
        header("Location: ../public/index.php");
        exit();
    }
}

/**
 * Logs in a user by setting the appropriate session variables.
 * Call this after successful password verification.
 * @param array $user_data The user's data array fetched from the database.
 * @param string $role 'admin' or 'editor'.
 */
function setLoginSession($user_data, $role) {
    if ($role === 'admin') {
        $_SESSION['admin_id'] = $user_data['id'];
        $_SESSION['user_id'] = $user_data['id']; // Unified ID for analytics
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['role'] = 'admin';

    } elseif ($role === 'editor') {
        $_SESSION['editor_id'] = $user_data['id'];
        $_SESSION['user_id'] = $user_data['id']; // Unified ID for analytics
        $_SESSION['username'] = $user_data['username'];
        // Assuming full_name/email might be here too if fetched from editors table
        $_SESSION['role'] = 'editor';
    }
    // Note: We don't exit here, as the caller (login.php) handles the redirect.
}

/**
 * Updates the user's password in the database.
 * * CRITICAL FIX: Accepts $role to dynamically target 'admins' or 'editors' table.
 * * @param int $user_id The ID of the user whose password is to be changed.
 * @param string $new_password The new plaintext password.
 * @param string $role The user's role ('admin' or 'editor'). Defaults to 'editor'.
 * @return bool True on success, false on failure.
 */
function updatePassword($user_id, $new_password, $role = 'editor') {
    global $pdo; 
    
    if (!$pdo) {
        error_log("updatePassword: PDO connection not available.");
        return false;
    }

    // Determine the target table based on the role (CRITICAL CHANGE)
    $table = ($role === 'admin') ? 'admins' : 'editors';

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Use the determined table name in the query
    $sql = "UPDATE {$table} SET password = ? WHERE id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        // Add log for debugging what table is being updated
        error_log("Attempting to update password for ID {$user_id} in table {$table}"); 
        return $stmt->execute([$hashed_password, $user_id]);
    } catch (PDOException $e) {
        error_log("Password update error in table {$table}: " . $e->getMessage());
        return false;
    }
}


/**
 * Clears all relevant session data to log the user out.
 */
function logout() {
    session_unset();
    session_destroy();
}
?>