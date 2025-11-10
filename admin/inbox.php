<?php
// Note: This file runs inside an iframe, so it only needs partial HTML and relies 
// on the main index.php for the <head> and overall structure.

require_once "../config/database.php"; // Database connection
require_once "../core/auth.php"; 
checkAdminLogin(); // Ensure admin is logged in

// --- 1. Get current filter status (Default is 'new' for the drawer) ---
$current_status = $_GET['status'] ?? 'new';

// Sanitize and validate the status value
if (!in_array($current_status, ['new', 'read', 'archived', 'all'])) {
    $current_status = 'new';
}

// --- 2. Build the SQL Query ---
$query_parts = [];
$query_parts[] = "SELECT id, sender_name, sender_email, message_content, status, received_at FROM inbox";

$bind_params = [];
$where_clause = '';

if ($current_status !== 'all') {
    $where_clause = " WHERE status = :status";
    $bind_params[':status'] = $current_status;
}

$query_parts[] = $where_clause;
$query_parts[] = "ORDER BY received_at DESC";

$final_query = implode(' ', $query_parts);

// --- 3. Execute the Query ---
try {
    $stmt = $pdo->prepare($final_query);
    $stmt->execute($bind_params);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: Could not load inbox. " . $e->getMessage());
}

// Function to truncate message preview
function truncateMessage($text, $length = 40) {
    // Only truncate if the message is longer than the desired length
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

// Function to display status badge
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
    <title>Inbox</title>
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        /* Define color variables if they aren't globally inherited */
        :root {
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --primary-dark: #1e40af;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --alert-new: #fef2f2; /* Very light red */
            --alert-new-border: #ef4444; /* Strong red */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 1.5rem;
            background: #f8fafc;
            color: var(--text);
        }
        .inbox-container {
            width: 100%;
            background: white; /* Container background is white */
            border-radius: 8px;
            overflow: hidden; /* Contains the list borders */
        }
        .inbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.5rem 0.5rem 0; /* Adjust padding for list context */
            border-bottom: 1px solid var(--border);
            margin-bottom: 0.5rem;
            background: #fff;
            position: sticky; /* Keep filters visible */
            top: 0;
            z-index: 10;
        }
        .inbox-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: var(--primary-dark);
        }
        .status-filters a {
            padding: 0.3rem 0.8rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-light);
            border: 1px solid var(--border);
            transition: all 0.2s;
            margin-left: 0.25rem;
        }
        .status-filters a:hover {
            border-color: var(--primary);
        }
        .status-filters a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .message-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        /* Message Item Styles */
        .message-item {
            display: flex;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background-color 0.2s, border-left 0.2s;
            cursor: pointer;
            align-items: flex-start;
            border-left: 5px solid transparent; /* Default border */
        }
        
        /* Highlight for NEW messages */
        .message-item.status-new {
            background: var(--alert-new); 
            border-left-color: var(--alert-new-border);
            font-weight: 600;
        }
        
        .message-item:hover {
            background: var(--primary-light);
        }
        
        .message-details {
            flex-grow: 1;
        }
        .sender-line {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .sender-name {
            font-size: 1rem;
            color: var(--text);
        }
        .message-item.status-new .sender-name {
             color: var(--alert-new-border); /* Highlight sender name in red */
        }
        
        .received-at {
            font-size: 0.75rem;
            color: var(--text-light);
            flex-shrink: 0;
            margin-left: 1rem;
        }
        .message-preview {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
        
        .message-item.status-new .message-preview {
             color: var(--text); /* Make preview text darker for new messages */
        }
        
        .status-badge {
            /* Existing badge styles */
            display: inline-block;
            padding: 2px 6px;
            border-radius: 50px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-new { background: #fee2e2; color: #b91c1c; }
        .status-read { background: #dbeafe; color: #1e40af; }
        .status-archived { background: #e5e7eb; color: #4b5563; }
        
        .no-messages {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
            background: white;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="inbox-container">
    <div class="inbox-header">
        <h2><i class="fas fa-inbox"></i> <?= ucfirst($current_status) ?> Messages</h2>
        <div class="status-filters">
            <a href="inbox.php?status=new" class="<?= $current_status == 'new' ? 'active' : '' ?>" title="New Messages">New</a>
            <a href="inbox.php?status=read" class="<?= $current_status == 'read' ? 'active' : '' ?>" title="Read Messages">Read</a>
            <a href="inbox.php?status=all" class="<?= $current_status == 'all' ? 'active' : '' ?>" title="All Messages">All</a>
        </div>
    </div>

    <?php if (empty($messages)): ?>
        <div class="no-messages">
            <i class="fas fa-info-circle"></i> No <?= htmlspecialchars($current_status) ?> messages found.
        </div>
    <?php else: ?>
        <ul class="message-list">
            <?php foreach ($messages as $msg): ?>
            <li class="message-item status-<?= htmlspecialchars($msg['status']) ?>" 
                onclick="window.location.href='inbox_view.php?id=<?= $msg['id'] ?>';">
                <div class="message-details">
                    <div class="sender-line">
                        <span class="sender-name"><?= htmlspecialchars($msg['sender_name']) ?></span>
                        <span class="received-at"><?= date('M d, H:i', strtotime($msg['received_at'])) ?></span>
                    </div>
                    <p class="message-preview">
                        <?= htmlspecialchars(truncateMessage($msg['message_content'], 60)) ?>
                    </p>
                    <small style="color: var(--primary);"><?= htmlspecialchars($msg['sender_email']) ?></small>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>

</body>
</html>