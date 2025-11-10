<?php
// editor/dashboard.php - The landing page content for the Editor Panel (iframe content)

// 1. Security & Configuration
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

checkEditorLogin(); 

// 2. Data Retrieval for Dashboard & Display
$editor_username = $_SESSION['username'] ?? 'Editor';
$editor_id = $_SESSION['user_id'] ?? ($_SESSION['editor_id'] ?? 0);
$editor_role = $_SESSION['role'] ?? 'Editor';

date_default_timezone_set('Africa/Blantyre'); 
$current_date = date('l, F j, Y');
$current_time = date('h:i A');

// Data queries
$inbox_message_count = 0;
$programme_count = 0;
$editor_email = 'N/A';
$editor_full_name = 'Not specified';
$member_since = 'Unknown';

try {
    if (isset($pdo)) {
        // Inbox count
        $stmt = $pdo->query("SELECT COUNT(id) AS count FROM inbox WHERE status = 'new'");
        $inbox_message_count = $stmt->fetchColumn();

        // Editor profile
        $stmt = $pdo->prepare("SELECT email, full_name, created_at FROM editors WHERE id = ?");
        $stmt->execute([$editor_id]);
        $editor_profile = $stmt->fetch(PDO::FETCH_ASSOC);

        $editor_email = $editor_profile['email'] ?? 'N/A';
        $editor_full_name = $editor_profile['full_name'] ?? 'Not specified';
        $member_since = isset($editor_profile['created_at']) ? date('F j, Y', strtotime($editor_profile['created_at'])) : 'Unknown';

        // Programmes count
        $stmt = $pdo->query("SELECT COUNT(id) FROM programmes");
        $programme_count = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    error_log("Editor Dashboard Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header p {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
        }
        
        .stat-icon.profile { background: #dbeafe; color: var(--primary); }
        .stat-icon.time { background: #f1f5f9; color: var(--text-light); }
        .stat-icon.programmes { background: #dcfce7; color: var(--success); }
        .stat-icon.inbox { background: #e0f2fe; color: var(--info); }
        
        .stat-content h3 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
        }
        
        .stat-meta {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .stat-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        
        .stat-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .quick-actions {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .quick-actions h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }
        
        .action-btn.secondary {
            background: var(--card-bg);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .action-btn.secondary:hover {
            background: #f1f5f9;
            border-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 0.75rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>Dashboard Overview</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($editor_username) ?></strong>! Here's your current snapshot.</p>
    </div>

    <div class="stats-grid">
        <!-- Profile Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon profile">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <h3>User Profile</h3>
                    <div class="stat-value"><?= htmlspecialchars($editor_full_name) ?></div>
                </div>
            </div>
            <div class="stat-meta">
                <p style="color: var(--text-light); font-size: 0.875rem; margin-bottom: 0.5rem;">
                    <strong>Role:</strong> <?= htmlspecialchars(ucfirst($editor_role)) ?><br>
                    <strong>Member since:</strong> <?= $member_since ?>
                </p>
                <a href="profile.php" class="stat-link">
                    Update Profile <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Time Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon time">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Current Time</h3>
                    <div class="stat-value" id="currentTime"><?= $current_time ?></div>
                </div>
            </div>
            <div class="stat-meta">
                <p style="color: var(--text-light); font-size: 0.875rem;">
                    <?= $current_date ?>
                </p>
            </div>
        </div>

        <!-- Programmes Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon programmes">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-content">
                    <h3>Programmes Managed</h3>
                    <div class="stat-value"><?= $programme_count ?></div>
                </div>
            </div>
            <div class="stat-meta">
                <a href="programmes.php" class="stat-link">
                    Manage Programmes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Inbox Card -->
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon inbox">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3>New Messages</h3>
                    <div class="stat-value"><?= $inbox_message_count ?></div>
                </div>
            </div>
            <div class="stat-meta">
                <a href="inbox.php" class="stat-link">
                    Check Inbox <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="programmes.php" class="action-btn">
                <i class="fas fa-project-diagram"></i>
                Manage Programmes
            </a>
            <a href="inbox.php" class="action-btn secondary">
                <i class="fas fa-inbox"></i>
                Check Inbox
            </a>
            <a href="profile.php" class="action-btn secondary">
                <i class="fas fa-user-cog"></i>
                Profile Settings
            </a>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 60000); // Update every minute
    </script>
</body>
</html>