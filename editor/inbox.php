<?php
require_once "../config/database.php";
require_once "../core/auth.php";

// Get and validate status filter
$current_status = $_GET['status'] ?? 'new';
$valid_statuses = ['new', 'read', 'archived', 'all'];
$current_status = in_array($current_status, $valid_statuses) ? $current_status : 'new';

// Build query
$where_clause = $current_status !== 'all' ? "WHERE status = ?" : "";
$query = "SELECT id, sender_name, sender_email, message_content, status, received_at 
          FROM inbox $where_clause 
          ORDER BY received_at DESC";

try {
    $stmt = $pdo->prepare($query);
    if ($current_status !== 'all') {
        $stmt->execute([$current_status]);
    } else {
        $stmt->execute();
    }
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: Could not load inbox.");
}

function truncateMessage($text, $length = 60) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

function getStatusBadge($status) {
    $classes = [
        'new' => 'badge-new',
        'read' => 'badge-read',
        'archived' => 'badge-archived'
    ];
    $class = $classes[$status] ?? 'badge-read';
    return "<span class='badge {$class}'>" . ucfirst($status) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox</title>
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
            --new-bg: #fef2f2;
            --new-border: #dc2626;
            --radius: 8px;
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
            padding: 1rem;
        }

        .inbox-container {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .inbox-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--white);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inbox-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: var(--bg);
            padding: 0.25rem;
            border-radius: var(--radius);
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-light);
            transition: all 0.2s;
            border: none;
            background: none;
            cursor: pointer;
        }

        .filter-tab:hover {
            background: var(--white);
            color: var(--text);
        }

        .filter-tab.active {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .message-list {
            list-style: none;
        }

        .message-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background-color 0.2s;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background: var(--primary-light);
        }

        .message-item.new {
            background: var(--new-bg);
            border-left: 4px solid var(--new-border);
        }

        .message-item.new:hover {
            background: #fecaca;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
            min-width: 0;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            gap: 1rem;
        }

        .sender-info {
            flex: 1;
            min-width: 0;
        }

        .sender-name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .message-item.new .sender-name {
            color: var(--new-border);
        }

        .sender-email {
            font-size: 0.875rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .message-preview {
            font-size: 0.875rem;
            color: var(--text-light);
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--text-light);
            white-space: nowrap;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-new { background: #fef2f2; color: #dc2626; }
        .badge-read { background: #dbeafe; color: #1e40af; }
        .badge-archived { background: #f1f5f9; color: #64748b; }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .empty-description {
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 0.75rem;
            }

            .inbox-header {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .filter-tabs {
                justify-content: center;
            }

            .message-item {
                padding: 1rem;
            }

            .message-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .message-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .filter-tabs {
                flex-wrap: wrap;
                justify-content: center;
            }

            .message-item {
                flex-direction: column;
                gap: 0.75rem;
            }

            .message-avatar {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>

<div class="inbox-container">
    <div class="inbox-header">
        <div class="header-content">
            <div class="inbox-title">
                <i class="fas fa-inbox"></i>
                <?= ucfirst($current_status) ?> Messages
                <span class="badge" style="background: var(--border); color: var(--text-light);">
                    <?= count($messages) ?>
                </span>
            </div>
            
            <div class="filter-tabs">
                <a href="inbox.php?status=new" class="filter-tab <?= $current_status == 'new' ? 'active' : '' ?>">
                    New
                </a>
                <a href="inbox.php?status=read" class="filter-tab <?= $current_status == 'read' ? 'active' : '' ?>">
                    Read
                </a>
                <a href="inbox.php?status=archived" class="filter-tab <?= $current_status == 'archived' ? 'active' : '' ?>">
                    Archived
                </a>
                <a href="inbox.php?status=all" class="filter-tab <?= $current_status == 'all' ? 'active' : '' ?>">
                    All
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($messages)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="empty-title">No messages found</div>
            <div class="empty-description">
                No <?= htmlspecialchars($current_status) ?> messages in your inbox.
            </div>
        </div>
    <?php else: ?>
        <ul class="message-list">
            <?php foreach ($messages as $msg): ?>
            <li class="message-item <?= $msg['status'] === 'new' ? 'new' : '' ?>" 
                onclick="window.location.href='inbox_view.php?id=<?= $msg['id'] ?>'">
                
                <div class="message-avatar">
                    <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                </div>
                
                <div class="message-content">
                    <div class="message-header">
                        <div class="sender-info">
                            <div class="sender-name"><?= htmlspecialchars($msg['sender_name']) ?></div>
                            <div class="sender-email"><?= htmlspecialchars($msg['sender_email']) ?></div>
                        </div>
                        <div class="message-meta">
                            <div class="message-time">
                                <?= date('M d, g:i A', strtotime($msg['received_at'])) ?>
                            </div>
                            <?= getStatusBadge($msg['status']) ?>
                        </div>
                    </div>
                    
                    <div class="message-preview">
                        <?= htmlspecialchars(truncateMessage($msg['message_content'], 80)) ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

</body>
</html>