<?php
// admin/partials/header.php (Updated for Analytics and Inbox Count)

// ------------------------------------------------------------------
// 1. INBOX LOGIC (Needed for Notification Badge)
// ------------------------------------------------------------------
// Assuming database connection ($pdo) is already available.
$new_message_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(id) AS count FROM inbox WHERE status = 'new'");
    $new_message_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Fail gracefully
    $new_message_count = 0;
}



// ------------------------------------------------------------------
// 3. FRONT-END RENDERING
// ------------------------------------------------------------------
?>

<header class="topbar">
    <div class="topbar-left">
        <h1>Editor Dashboard</h1>
    </div>
    <div class="topbar-right">
        
        <div class="notification-icon" id="inboxToggle">
            <i class="fas fa-envelope"></i>
            <?php if ($new_message_count > 0): ?>
                <span class="notification-badge" id="inboxCount"><?= $new_message_count ?></span>
            <?php endif; ?>
        </div>

        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Editor'); ?></span>
        </div>
    </div>
</header>

