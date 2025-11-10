<?php
require_once "../config/database.php"; 
require_once "../core/auth.php"; 
checkAdminLogin
// Ensure this script is accessed via POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect back to inbox if accessed incorrectly
    header("Location: inbox.php");
    exit();
}

// 1. Get and sanitize input
$message_id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null; // Expecting 'archive'

// Validate ID
if (!$message_id || !is_numeric($message_id)) {
    // Simple error handling: redirect back
    header("Location: inbox.php?error=invalid_id");
    exit();
}

// 2. Process the action
if ($action === 'archive') {
    try {
        // Update the status to 'archived'
        $stmt = $pdo->prepare("UPDATE inbox SET status = 'archived' WHERE id = :id");
        $stmt->execute([':id' => $message_id]);
        
        // Success message for logging/debugging (not displayed to user in a redirect)
        $message = "Message ID {$message_id} successfully archived.";

        // --- IMPORTANT: Notify Parent Frame to Update Count ---
        // Since the browser window/tab is redirecting, we can't use a direct JS call 
        // here easily. The best solution is to redirect the parent frame's iframe 
        // (the inbox drawer) to the updated list.
        
        // This JavaScript will execute *after* the redirect and simply tell the parent 
        // to close the drawer, which will force a reload of the inbox when the drawer 
        // is opened again, showing the updated counts.
        echo "<script>
            if (window.parent && window.parent.document.getElementById('inboxDrawer')) {
                window.parent.document.getElementById('inboxDrawer').classList.remove('open');
            }
        </script>";

    } catch (PDOException $e) {
        // Log the error and redirect back with an error flag
        error_log("Archive Error: " . $e->getMessage());
        header("Location: inbox_view.php?id={$message_id}&error=db_fail");
        exit();
    }
}

// 3. Redirect back to the inbox list (showing the new list of 'new' messages, or 'read' list)
// We redirect to 'inbox.php' to reload the list without the archived message.
header("Location: inbox.php?status=new&success=archived");
exit();
?>