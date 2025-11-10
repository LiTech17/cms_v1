<?php
require_once "../core/auth.php";
// **REQUIRED** Include database connection to fetch analytics data
require_once "../config/database.php"; 
checkAdminLogin();

// --- PHP: Fetch Analytics Data ---
$daily_unique_visitors = 0;
$total_page_views = 0;
$total_submissions = 0;

try {
    // Ensure $pdo is available from database.php
    if (!isset($pdo)) {
        throw new Exception("Database connection (\$pdo) not established.");
    }
    
    $today = date('Y-m-d');
    
    // 1. Daily Unique Visitors (Head Count): Count distinct session IDs for today
    $stmt_head_count = $pdo->prepare("
        SELECT COUNT(DISTINCT session_id) 
        FROM analytics_log 
        WHERE DATE(timestamp) = :today AND event_type = 'page_view'
    ");
    $stmt_head_count->execute([':today' => $today]);
    $daily_unique_visitors = $stmt_head_count->fetchColumn();

    // 2. Total Page Views: Count all 'page_view' events
    $stmt_total_views = $pdo->query("
        SELECT COUNT(id) 
        FROM analytics_log 
        WHERE event_type = 'page_view'
    ");
    $total_page_views = $stmt_total_views->fetchColumn();

    // 3. Total Contact Submissions (Example of another tracked event)
    $stmt_submissions = $pdo->query("
        SELECT COUNT(id) 
        FROM analytics_log 
        WHERE event_type = 'form_submission'
    ");
    $total_submissions = $stmt_submissions->fetchColumn();

} catch (Exception $e) {
    error_log("Analytics Dashboard Error: " . $e->getMessage());
}

// Ensure the counts are cast to integer for clean display
$daily_unique_visitors = (int)$daily_unique_visitors;
$total_page_views = (int)$total_page_views;
$total_submissions = (int)$total_submissions;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | NGO CMS</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        /* Add basic styling for the new analytics cards (You may want to move this to admin.css) */
        .analytics-card {
            background-color: #f7f7f7;
            /* Use a primary color for visual emphasis */
            border-left: 5px solid var(--primary, #007bff); 
            padding: 1.5rem;
            text-align: center;
        }
        .analytics-card h4 {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .analytics-card .count {
            font-size: 2.5rem;
            font-weight: 700;
            color: #343a40;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">


    <main class="main-content">
        <section class="dashboard-content">
            
            <h2 style="margin-top: 0;">📊 Website Analytics</h2>
            <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
                
                <div class="card analytics-card">
                    <h4>Today's Head Count</h4>
                    <p class="count"><?= number_format($daily_unique_visitors) ?></p>
                    <small>Unique Daily Visitors</small>
                </div>

                <div class="card analytics-card" style="border-left-color: #28a745;">
                    <h4>Total Page Views</h4>
                    <p class="count"><?= number_format($total_page_views) ?></p>
                    <small>All-time traffic views</small>
                </div>
                
                <div class="card analytics-card" style="border-left-color: #ffc107;">
                    <h4>Total Submissions</h4>
                    <p class="count"><?= number_format($total_submissions) ?></p>
                    <small>Form Submission Events</small>
                </div>
            </div>
            
            <h2>📝 Content Management</h2>
            <div class="card-grid">
                <div class="card">
                    <h3>⚙️ Site Settings</h3>
                    <p>Manage site configuration and branding.</p>
                    <a href="settings.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>🏠 Home Carousel</h3>
                    <p>Update hero images and carousel captions.</p>
                    <a href="home_hero.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>📜 Organization Info</h3>
                    <p>Edit mission, vision, goals, and objectives.</p>
                    <a href="organization_info.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>🎓 Programmes</h3>
                    <p>Manage NGO programmes and activities.</p>
                    <a href="programmes.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>💳 Donation Options</h3>
                    <p>Configure donation accounts and methods.</p>
                    <a href="donation_methods.php" class="btn">Open</a>
                </div>
            </div>
        </section>
    </main>

</div>

<script>
// NOTE: This script tag is likely redundant as the parent index.php handles the sidebar toggle.
// However, if this dashboard is accessed directly, it provides fallback functionality.
if (window.parent && window.parent.document.getElementById('toggleSidebar')) {
    // Logic is handled by the parent frame (admin/index.php)
} else if (document.getElementById('toggleSidebar')) {
    document.getElementById('toggleSidebar').addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
             sidebar.classList.toggle('collapsed');
        }
    });
}
</script>

</body>
</html>