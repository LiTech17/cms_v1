<?php
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

// Security: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Verify editor is logged in
checkEditorLogin();

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

// Validate inputs
$message_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if (!$message_id || $action !== 'archive') {
    http_response_code(400);
    exit('Invalid request');
}

try {
    // Archive the message
    $stmt = $pdo->prepare("UPDATE inbox SET status = 'archived' WHERE id = ? AND status != 'archived'");
    $stmt->execute([$message_id]);
    
    $affected_rows = $stmt->rowCount();
    
    if ($affected_rows > 0) {
        // Get updated count for real-time update
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM inbox WHERE status = 'new'");
        $new_count = $count_stmt->fetchColumn();
        
        // Return JSON for AJAX handling
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Message archived successfully',
            'new_count' => $new_count
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Message not found or already archived'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Archive Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>