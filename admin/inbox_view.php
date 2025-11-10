<?php
require_once "../config/database.php"; 
require_once "../core/auth.php"; 
checkAdminLogin(); // Ensure admin is logged in
$message_id = $_GET['id'] ?? null;
$message = null;
$error = null;

// --- 1. Validate ID and Fetch Message ---
if (!$message_id || !is_numeric($message_id)) {
    $error = "Error: Invalid message ID specified.";
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id, sender_name, sender_email, sender_phone, sender_location, message_content, status, received_at 
            FROM inbox 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $error = "Error: Message not found.";
        }

        // --- 2. Automatically mark as 'read' if it's 'new' ---
        if ($message && $message['status'] === 'new') {
            $update_stmt = $pdo->prepare("UPDATE inbox SET status = 'read' WHERE id = :id");
            $update_stmt->execute([':id' => $message_id]);
            $message['status'] = 'read'; // Update local status for display
            
            // Notification script (Simplified: tell parent to reload/update count)
            echo "<script>
                if (window.parent && window.parent.document.getElementById('inboxToggle')) {
                    // This is a common way to trigger an event or function in the parent window.
                    // For simplicity here, we'll suggest a manual reload if needed.
                    console.log('Message marked as read. Parent frame should update count.');
                }
            </script>";
        }

    } catch (PDOException $e) {
        $error = "Database Error: Could not load message.";
    }
}

// Helper functions for displaying data/badges
function displayDetail($value, $default = 'N/A') {
    return htmlspecialchars($value ?? $default);
}
function getStatusBadge($status) {
    $class = '';
    if ($status === 'new') $class = 'status-new';
    elseif ($status === 'read') $class = 'status-read';
    elseif ($status === 'archived') $class = 'status-archived';
    return "<span class='status-badge {$class}'>" . ucfirst($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Message</title>
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        /* CSS Variables */
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --accent: #06b6d4;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #38a169;
            --danger: #e53e3e;
        }

        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; /* Remove padding from body */
            background: var(--bg); 
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .view-wrapper {
            flex-grow: 1;
            padding: 1.5rem;
            overflow-y: auto;
        }
        .view-nav {
            padding: 0.75rem 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
            font-size: 1.1rem;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .nav-button:hover {
            background: #e2e8f0;
            color: var(--text);
        }
        
        /* Message Card and Details (Adjusted slightly) */
        .message-card {
            background: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .message-header h1 {
            font-size: 1.25rem;
            color: var(--primary-dark);
            margin: 0;
            font-weight: 700;
        }
        .message-header p {
            color: var(--text-light);
            font-size: 0.85rem;
            margin: 0;
        }
        .contact-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 0;
            border-top: 1px dashed var(--border);
            border-bottom: 1px dashed var(--border);
        }
        .contact-item {
            background: #f1f5f9;
            padding: 0.75rem;
            border-radius: 6px;
            border-left: 3px solid var(--primary); /* Changed to primary color */
        }
        .contact-item strong {
            display: block;
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }
        .contact-item span {
            font-size: 0.95rem;
            color: var(--text);
            font-weight: 600;
        }
        .message-content-box {
            background: #f1f5f9; /* Light background for message */
            padding: 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            line-height: 1.6;
            color: #4b5563;
            white-space: pre-wrap;
            border: 1px solid var(--border);
        }
        .action-bar {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end; /* Align actions to the right */
        }
        .btn-action {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-archive { background: var(--danger); color: white; }
        .btn-archive:hover { background: #c53030; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: capitalize; }
        .status-new { background: #fee2e2; color: #b91c1c; }
        .status-read { background: #dbeafe; color: #1e40af; }
        .status-archived { background: #e5e7eb; color: #4b5563; }
        .alert-error { background: #fed7d7; color: #742a2a; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid var(--danger); }
    </style>
</head>
<body>

<div class="view-nav">
    <button class="nav-button" onclick="history.back();" title="Back to Inbox List">
        <i class="fas fa-arrow-left"></i>
    </button>
    
    <h2 style="font-size: 1.1rem; margin: 0; color: var(--text-light);">
        <?= $message ? 'Message from ' . displayDetail($message['sender_name']) : 'Message View' ?>
    </h2>
    
    <button class="nav-button" onclick="if(window.parent.document.getElementById('inboxDrawer')) window.parent.document.getElementById('inboxDrawer').classList.remove('open');" title="Exit Message Viewer">
        <i class="fas fa-times"></i>
    </button>
</div>

<div class="view-wrapper">
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <div class="action-bar" style="justify-content: flex-start;">
            <button class="btn-action btn-back" onclick="history.back();">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>

    <?php elseif ($message): ?>
        <div class="message-card">
            
            <div class="message-header">
                <div>
                    <p>Received: **<?= date('M d, Y \a\t H:i', strtotime($message['received_at'])) ?>**</p>
                </div>
                <div>
                    <?= getStatusBadge($message['status']) ?>
                </div>
            </div>

            <div class="contact-details-grid">
                <div class="contact-item">
                    <strong>Email Address</strong>
                    <span><i class="fas fa-envelope"></i> <?= displayDetail($message['sender_email']) ?></span>
                </div>
                <div class="contact-item">
                    <strong>Phone Number</strong>
                    <span><i class="fas fa-phone-alt"></i> <?= displayDetail($message['sender_phone']) ?></span>
                </div>
                <div class="contact-item" style="grid-column: 1 / -1;">
                    <strong>Location/City</strong>
                    <span><i class="fas fa-map-marker-alt"></i> <?= displayDetail($message['sender_location']) ?></span>
                </div>
            </div>

            <h3 style="font-size: 1.1rem; margin-top: 0; color: var(--text);">Message Content:</h3>
            <div class="message-content-box">
                <?= nl2br(displayDetail($message['message_content'])) ?>
            </div>

            <div class="action-bar">
                <?php if ($message['status'] !== 'archived'): ?>
                    <form action="inbox_update.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="id" value="<?= $message['id'] ?>">
                        <input type="hidden" name="action" value="archive">
                        <button type="submit" class="btn-action btn-archive">
                            <i class="fas fa-archive"></i> Archive Message
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>