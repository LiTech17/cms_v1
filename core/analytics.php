<?php
// core/analytics.php

/**
 * Logs a user activity event into the analytics_log table.
 * * @param string $eventType The type of action (e.g., 'page_view').
 * @param string $urlPath The path of the page where the event occurred.
 * @param int|null $userId The ID of the logged-in user, or null.
 * @param string $sessionId The current PHP session ID.
 * @return bool True on successful log, false otherwise.
 */
function log_activity($eventType, $urlPath, $userId = null, $sessionId) {
    // Requires $pdo from database.php to be available globally
    global $pdo; 

    if (!isset($pdo)) {
        error_log("Analytics Error: PDO connection not available for logging.");
        return false;
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $sql = "INSERT INTO analytics_log 
            (user_id, session_id, event_type, url_path, ip_address) 
            VALUES (?, ?, ?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute([
            $userId,
            $sessionId,
            $eventType,
            $urlPath,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        error_log("Analytics Logging Failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Safely retrieves the current user ID from the session.
 * Assumes the logged-in user's ID is stored in $_SESSION['user_id'].
 * @return int|null
 */
function get_current_user_id() {
    // Check if a session variable for the user ID exists
    return $_SESSION['user_id'] ?? null;
}