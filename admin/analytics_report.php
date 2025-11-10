<?php
// admin/analytics.php - Advanced Analytics Dashboard

// 1. Security & Configuration
require_once "../config/database.php"; 
require_once "../core/auth.php"; 

checkAdminLogin(); // Ensure the user is logged in as an Administrator

$error = null;
$analytics_data = [];

// Helper function to fetch a single scalar value from the database
function fetchScalar($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Analytics Scalar Fetch Error: " . $e->getMessage());
        return 0;
    }
}

// -----------------------------------------------------------
// 2. Advanced Analytics Data Retrieval
// -----------------------------------------------------------

try {
    if (!isset($pdo)) {
        throw new Exception("Database connection (\$pdo) not established.");
    }

    // Optional: Only consider last 30 days
    $time_filter = "WHERE timestamp >= NOW() - INTERVAL 30 DAY";

    // --- A. Key Performance Indicators (KPIs) ---
    
    // 1. Total Page Views (all event types of interest)
    $analytics_data['total_views'] = fetchScalar(
        $pdo, 
        "SELECT COUNT(id) FROM analytics_log WHERE event_type IN ('page_view', 'admin_page_view', 'editor_page_view')"
    );
    
    // 2. Total Unique Visitors (based on unique session_id, unauthenticated users)
    $analytics_data['unique_visitors'] = fetchScalar(
        $pdo,
        "SELECT COUNT(DISTINCT session_id) FROM analytics_log WHERE user_id IS NULL OR user_id = 0"
    );

    // 3. Unique Logged-in Users (Admins/Editors)
    $analytics_data['unique_users'] = fetchScalar(
        $pdo,
        "SELECT COUNT(DISTINCT user_id) FROM analytics_log WHERE user_id IS NOT NULL AND user_id != 0"
    );

    // 4. Contact Form Submissions
    $analytics_data['form_submissions'] = fetchScalar(
        $pdo,
        "SELECT COUNT(id) FROM analytics_log WHERE event_type = 'form_submission' AND url_path LIKE '%contact%'"
    );

    // --- B. Top Pages ---
    $stmt = $pdo->query("
        SELECT url_path, COUNT(id) AS view_count
        FROM analytics_log
        WHERE url_path NOT LIKE '%admin%' AND url_path NOT LIKE '%editor%'
        AND event_type IN ('page_view', 'editor_page_view')
        GROUP BY url_path
        ORDER BY view_count DESC
        LIMIT 10
    ");
    $analytics_data['top_pages'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- C. Event Type Breakdown ---
    $stmt = $pdo->query("
        SELECT event_type, COUNT(id) AS event_count
        FROM analytics_log
        GROUP BY event_type
        ORDER BY event_count DESC
    ");
    $analytics_data['event_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- D. Recent Activity Log ---
    $stmt = $pdo->query("
        SELECT id, timestamp, event_type, url_path, ip_address
        FROM analytics_log
        ORDER BY timestamp DESC
        LIMIT 15
    ");
    $analytics_data['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Analytics Data Fetch Error: " . $e->getMessage());
    $error = "Could not load analytics data: Database error.";
}

// Helper to clean up URL paths for display
function cleanUrlPath($path) {
    return str_replace('/ngo_cms/', '/', $path);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .analytics-card {
            border-left: 5px solid var(--bs-primary);
            transition: transform 0.3s;
            min-height: 100%;
        }
        .analytics-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .card-body h1 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="container-fluid p-4">
    <h3 class="mb-4 text-primary"><i class="fas fa-chart-bar me-2"></i> Website Analytics Overview</h3>
    <p class="text-muted">Detailed activity log and performance indicators for the CMS.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm analytics-card" style="--bs-primary: #0d6efd;">
                <div class="card-body">
                    <h5 class="card-title text-primary">Total Views</h5>
                    <h1 class="text-primary"><?= number_format($analytics_data['total_views'] ?? 0) ?></h1>
                    <p class="card-text text-muted"><i class="fas fa-eye"></i> Total page loads.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm analytics-card" style="--bs-primary: #198754;">
                <div class="card-body">
                    <h5 class="card-title text-success">Unique Visitors</h5>
                    <h1 class="text-success"><?= number_format($analytics_data['unique_visitors'] ?? 0) ?></h1>
                    <p class="card-text text-muted"><i class="fas fa-users"></i> Unauthenticated sessions.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm analytics-card" style="--bs-primary: #ffc107;">
                <div class="card-body">
                    <h5 class="card-title text-warning">Admin/Editor Logins</h5>
                    <h1 class="text-warning"><?= number_format($analytics_data['unique_users'] ?? 0) ?></h1>
                    <p class="card-text text-muted"><i class="fas fa-user-shield"></i> Unique authenticated users.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm analytics-card" style="--bs-primary: #dc3545;">
                <div class="card-body">
                    <h5 class="card-title text-danger">Form Submissions</h5>
                    <h1 class="text-danger"><?= number_format($analytics_data['form_submissions'] ?? 0) ?></h1>
                    <p class="card-text text-muted"><i class="fas fa-paper-plane"></i> Contact forms sent.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <i class="fas fa-globe me-2"></i> Top 10 Public Pages
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($analytics_data['top_pages'])): ?>
                            <li class="list-group-item text-muted">No public page views logged yet.</li>
                        <?php else: ?>
                            <?php foreach ($analytics_data['top_pages'] as $index => $page): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary rounded-pill me-2"><?= $index + 1 ?></span>
                                    <code><?= htmlspecialchars(cleanUrlPath($page['url_path'])) ?></code>
                                    <span class="badge bg-secondary"><?= number_format($page['view_count']) ?> Views</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <i class="fas fa-list-ol me-2"></i> Event Type Breakdown
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($analytics_data['event_breakdown'])): ?>
                            <li class="list-group-item text-muted">No events logged yet.</li>
                        <?php else: ?>
                            <?php foreach ($analytics_data['event_breakdown'] as $event): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $event['event_type']))) ?>
                                    <span class="badge bg-dark"><?= number_format($event['event_count']) ?> Events</span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm mt-5">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-history me-2"></i> Recent Activity Log
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Timestamp</th>
                            <th>Event Type</th>
                            <th>URL Path</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($analytics_data['recent_activity'])): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No recent activity found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($analytics_data['recent_activity'] as $activity): ?>
                                <tr>
                                    <td><?= $activity['id'] ?></td>
                                    <td><?= date('M d, H:i:s', strtotime($activity['timestamp'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $activity['event_type']))) ?></span></td>
                                    <td><small><code><?= htmlspecialchars(cleanUrlPath($activity['url_path'])) ?></code></small></td>
                                    <td><?= htmlspecialchars($activity['ip_address'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>
