<?php
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

$message_id = $_GET['id'] ?? null;
$message = null;
$error = null;

// Validate ID and fetch message
if (!$message_id || !is_numeric($message_id)) {
    $error = "Invalid message ID";
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id, sender_name, sender_email, sender_phone, sender_location, 
                   message_content, status, received_at 
            FROM inbox 
            WHERE id = ?
        ");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $error = "Message not found";
        }

        // Mark as read if new
        if ($message && $message['status'] === 'new') {
            $update_stmt = $pdo->prepare("UPDATE inbox SET status = 'read' WHERE id = ?");
            $update_stmt->execute([$message_id]);
            $message['status'] = 'read';
            
            // Notify parent to update count
            echo "<script>
                if (window.parent && window.parent.updateMessageCount) {
                    window.parent.updateMessageCount();
                }
            </script>";
        }

    } catch (PDOException $e) {
        $error = "Database error loading message";
    }
}

function displayDetail($value, $default = 'Not provided') {
    return htmlspecialchars($value ?: $default);
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
    <title>View Message</title>
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem;
            border: none;
            background: none;
            border-radius: var(--radius);
            cursor: pointer;
            color: var(--text-light);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .btn:hover {
            background: var(--border);
            color: var(--text);
        }

        .header-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
        }

        .content {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .error-card {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            color: #dc2626;
        }

        .message-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .message-meta {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .message-info h1 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .message-time {
            color: var(--text-light);
            font-size: 0.875rem;
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

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .contact-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .contact-label {
            font-size: 0.75rem;
            color: var(--text-light);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-value {
            font-weight: 500;
            color: var(--text);
        }

        .message-content {
            padding: 1.5rem;
        }

        .message-content h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .message-body {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            white-space: pre-wrap;
            line-height: 1.6;
            color: var(--text);
        }

        .actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            .content {
                padding: 1rem;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .message-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-actions">
        <button class="btn" onclick="history.back()" title="Back">
            <i class="fas fa-arrow-left"></i>
        </button>
    </div>
    
    <div class="header-title">
        <?= $message ? 'Message Details' : 'View Message' ?>
    </div>
    
    <div class="header-actions">
        <button class="btn" onclick="closeViewer()" title="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<div class="content">
    <?php if ($error): ?>
        <div class="error-card">
            <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">Unable to load message</h3>
            <p style="margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></p>
            <button class="btn-primary" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Go Back
            </button>
        </div>

    <?php elseif ($message): ?>
        <div class="message-card">
            <div class="message-meta">
                <div class="message-info">
                    <h1><?= displayDetail($message['sender_name']) ?></h1>
                    <div class="message-time">
                        Received <?= date('M j, Y \a\t g:i A', strtotime($message['received_at'])) ?>
                    </div>
                </div>
                <?= getStatusBadge($message['status']) ?>
            </div>

            <div class="contact-grid">
                <div class="contact-item">
                    <span class="contact-label">Email</span>
                    <span class="contact-value">
                        <i class="fas fa-envelope"></i> <?= displayDetail($message['sender_email']) ?>
                    </span>
                </div>
                <div class="contact-item">
                    <span class="contact-label">Phone</span>
                    <span class="contact-value">
                        <i class="fas fa-phone"></i> <?= displayDetail($message['sender_phone']) ?>
                    </span>
                </div>
                <div class="contact-item">
                    <span class="contact-label">Location</span>
                    <span class="contact-value">
                        <i class="fas fa-map-marker-alt"></i> <?= displayDetail($message['sender_location']) ?>
                    </span>
                </div>
            </div>

            <div class="message-content">
                <h3>Message</h3>
                <div class="message-body">
                    <?= nl2br(displayDetail($message['message_content'])) ?>
                </div>
            </div>

            <?php if ($message['status'] !== 'archived'): ?>
                <div class="actions">
                    <form action="archive_message.php" method="POST" style="display: inline;">
                        <input type="hidden" name="id" value="<?= $message['id'] ?>">
                        <input type="hidden" name="action" value="archive">
                        <button type="submit" class="btn-danger" onclick="return confirm('Archive this message?')">
                            <i class="fas fa-archive"></i> Archive Message
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function closeViewer() {
    if (window.parent && window.parent.document.getElementById('inboxDrawer')) {
        window.parent.document.getElementById('inboxDrawer').classList.remove('open');
    } else {
        history.back();
    }
}

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeViewer();
    }
});
</script>

</body>
</html>