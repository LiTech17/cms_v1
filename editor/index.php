<?php
// editor/index.php - The main frame for the Editor Panel (SPA layout)

// -----------------------------------------------------------
// INITIAL SETUP: Connection, Auth, Session 
// -----------------------------------------------------------
require_once "../config/database.php"; 
require_once "../core/auth.php";

// FIX: Use the correct, defined authentication function for the Editor role.
checkEditorLogin(); 

// -----------------------------------------------------------
// ANALYTICS LOGIC INTEGRATION 
// -----------------------------------------------------------
require_once "../core/analytics.php"; 
$current_session_id = session_id();
$current_url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// FIX: Use the correct unified session key 'username' for display
$user_id = $_SESSION['user_id'] ?? ($_SESSION['editor_id'] ?? null); 
$username_display = $_SESSION['username'] ?? 'Unknown Editor';

// Log the event
log_activity(
    'editor_page_view', 
    $current_url_path,
    $user_id,
    $current_session_id,
    'Editor Access: ' . $username_display
);

// -----------------------------------------------------------
// INBOX LOGIC (Count new messages for badge)
// -----------------------------------------------------------
$new_message_count = 0;
try {
    // Assuming $pdo is available from database.php
    $stmt = $pdo->query("SELECT COUNT(id) AS count FROM inbox WHERE status = 'new'");
    $new_message_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Fail silently on dashboard load if DB error occurs
    error_log("Inbox count failed: " . $e->getMessage());
    $new_message_count = 0;
}

// Generate the Inbox Link URL and Secure Origin for PostMessage
$inbox_url = "inbox.php?status=new"; 
$current_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$current_host = $_SERVER['HTTP_HOST'];
$secure_origin = $current_protocol . "://" . $current_host;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Editor Panel | NGO CMS</title>
    <link rel="stylesheet" href="../admin/css/admin.css"> 
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        /* ===== MOBILE-FIRST VARIABLES ===== */
        :root {
            /* Colors */
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --accent: #10b981;
            --accent-dark: #059669;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --warning: #f59e0b;
            --success: #10b981;
            
            /* Neutral colors */
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            
            /* Sidebar */
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
            --sidebar-hover: #334155;
            --sidebar-active: #3b82f6;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            
            /* Typography */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            
            /* Border radius */
            --radius-sm: 4px;
            --radius: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            
            /* Transitions */
            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s ease-in-out;
            
            /* Z-index */
            --z-sidebar: 1000;
            --z-drawer: 1010;
            --z-overlay: 900;
            --z-header: 800;
        }

        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            font-size: var(--text-base);
            overflow-x: hidden;
        }

        /* ===== LAYOUT ===== */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ===== SIDEBAR - MOBILE FIRST ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100%;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            transform: translateX(-100%);
            transition: var(--transition-slow);
            z-index: var(--z-sidebar);
            box-shadow: var(--shadow-lg);
            overflow-y: auto;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 70px;
        }

        .sidebar-header h2 {
            font-size: var(--text-xl);
            font-weight: 600;
            color: white;
            white-space: nowrap;
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--space-md) 0;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0 var(--space-md);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .sidebar-nav li + li {
            margin-top: var(--space-xs);
        }

        .sidebar-nav a {
            color: var(--sidebar-text);
            display: flex;
            align-items: center;
            padding: var(--space-md) var(--space-lg);
            text-decoration: none;
            transition: var(--transition);
            border-radius: var(--radius);
            gap: var(--space-md);
            position: relative;
        }

        .sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        .sidebar-nav a.active {
            background: var(--sidebar-active);
            color: white;
            font-weight: 500;
        }

        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-nav a span {
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: var(--space-lg);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-footer a {
            color: var(--accent);
            text-decoration: none;
            transition: var(--transition);
            font-size: var(--text-sm);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            justify-content: center;
            padding: var(--space-sm);
            border-radius: var(--radius);
        }

        .sidebar-footer a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--accent-dark);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg);
            min-width: 0;
            width: 100%;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-card);
            box-shadow: var(--shadow-sm);
            padding: var(--space-md) var(--space-lg);
            min-height: 70px;
            position: sticky;
            top: 0;
            z-index: var(--z-header);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--text);
            cursor: pointer;
            font-size: var(--text-lg);
            padding: var(--space-sm);
            border-radius: var(--radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .toggle-btn:hover {
            background: var(--border-light);
            transform: scale(1.05);
        }

        .page-title {
            font-size: var(--text-xl);
            font-weight: 600;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
            color: var(--text-light);
            font-size: var(--text-lg);
            padding: var(--space-sm);
            border-radius: var(--radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .notification-icon:hover {
            background: var(--border-light);
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-xs);
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 0 0 2px var(--bg-card);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-weight: 500;
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .user-info:hover {
            background: var(--border-light);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: var(--text-sm);
        }

        /* ===== CONTENT AREA ===== */
        .content-area {
            flex: 1;
            padding: var(--space-lg);
            overflow-y: auto;
        }

        #loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: var(--text-lg);
            color: var(--primary);
            display: none;
            z-index: 10;
        }

        #contentFrame {
            width: 100%;
            height: 100%;
            border: none;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        /* ===== INBOX DRAWER ===== */
        #inboxDrawer {
            width: 100%;
            max-width: 400px;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            background: var(--bg-card);
            box-shadow: var(--shadow-lg);
            z-index: var(--z-drawer);
            transform: translateX(100%);
            transition: var(--transition-slow);
            display: flex;
            flex-direction: column;
        }

        #inboxDrawer.open {
            transform: translateX(0);
        }

        .drawer-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border);
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .drawer-header h3 {
            margin: 0;
            font-size: var(--text-lg);
            font-weight: 600;
        }

        .close-drawer-btn {
            background: none;
            border: none;
            color: white;
            font-size: var(--text-lg);
            cursor: pointer;
            padding: var(--space-sm);
            border-radius: var(--radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }

        .close-drawer-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .drawer-content {
            flex-grow: 1;
            overflow-y: auto;
            background: var(--bg);
        }

        #inboxFrame {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ===== OVERLAY ===== */
        #sidebarOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: var(--z-overlay);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-slow);
        }

        #sidebarOverlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        /* Tablet */
        @media (min-width: 768px) {
            .sidebar {
                position: static;
                transform: translateX(0);
                flex-shrink: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-btn {
                display: none;
            }
            
            #sidebarOverlay {
                display: none;
            }
            
            .content-area {
                padding: var(--space-xl);
            }
        }

        /* Desktop */
        @media (min-width: 1024px) {
            .sidebar {
                width: 260px;
            }
            
            .content-area {
                padding: var(--space-xl) var(--space-2xl);
            }
        }

        /* Small mobile */
        @media (max-width: 480px) {
            .topbar {
                padding: var(--space-sm) var(--space-md);
            }
            
            .page-title {
                font-size: var(--text-lg);
            }
            
            .user-info span {
                display: none;
            }
            
            .content-area {
                padding: var(--space-md);
            }
        }

        /* ===== ACCESSIBILITY ===== */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* Focus styles for keyboard navigation */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        textarea:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Editor Panel</h2> 
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php" data-page="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                
                <li><a href="programmes.php" data-page="programmes.php"><i class="fas fa-project-diagram"></i> <span>Programmes</span></a></li>
                
                <li><a href="profile.php" data-page="profile.php"><i class="fas fa-user-circle"></i> <span>Editor Profile</span></a></li>

                <li><a href="inbox.php" data-page="inbox.php"><i class="fas fa-inbox"></i> <span>Inbox</span></a></li>

            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button id="toggleSidebar" class="toggle-btn" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title" id="pageTitle">Dashboard</h1>
            </div>
            
            <div class="topbar-right">
                <div class="notification-icon" id="inboxToggle" aria-label="Open inbox">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge" id="inboxCount"><?= $new_message_count ?></span>
                </div>

                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($username_display, 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($username_display) ?></span>
                </div>
            </div>
        </header>

        <div class="content-area">
            <div id="loader">Loading...</div>
            <iframe id="contentFrame" src="dashboard.php" title="Editor Panel Content"></iframe>
        </div>
    </main>
</div>

<div id="inboxDrawer">
    <div class="drawer-header">
        <h3>Inbox (New: <span id="drawerCount"><?= $new_message_count ?></span>)</h3>
        <button id="closeDrawer" class="close-drawer-btn" aria-label="Close inbox">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="drawer-content">
        <iframe id="inboxFrame" src="" title="Inbox Messages"></iframe>
    </div>
</div>

<div id="sidebarOverlay"></div>

<script>
// ===== GLOBAL VARIABLES =====
const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
const contentFrame = document.getElementById('contentFrame');
const pageTitle = document.getElementById('pageTitle');
const loader = document.getElementById('loader');
const toggleSidebarBtn = document.getElementById('toggleSidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

const inboxToggle = document.getElementById('inboxToggle');
const inboxDrawer = document.getElementById('inboxDrawer');
const closeDrawerBtn = document.getElementById('closeDrawer');
const inboxFrame = document.getElementById('inboxFrame');
const inboxCountBadge = document.getElementById('inboxCount');
const drawerCountSpan = document.getElementById('drawerCount');
const sidebar = document.getElementById('sidebar');

// PHP variable passed to JS
const inboxUrl = "<?= $inbox_url; ?>"; 
const initialMessageCount = <?= $new_message_count; ?>;
const SECURE_ORIGIN = "<?= $secure_origin; ?>"; // For postMessage security

// ===== RESPONSIVENESS AND NAVIGATION LOGIC =====

// Sidebar toggle with overlay
function toggleSidebar() {
    sidebar.classList.toggle('open');
    sidebarOverlay.classList.toggle('active');
    
    // Prevent body scroll when sidebar is open on mobile
    if (sidebar.classList.contains('open')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Close sidebar function
function closeSidebar() {
    if (window.innerWidth < 768) {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Overlay click to close sidebar
sidebarOverlay.addEventListener('click', closeSidebar);

// Toggle sidebar button
toggleSidebarBtn.addEventListener('click', toggleSidebar);

// SPA navigation
sidebarLinks.forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        
        // Close the drawer and sidebar when navigating
        inboxDrawer.classList.remove('open');
        closeSidebar(); 

        // Update active state
        sidebarLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        const page = link.getAttribute('data-page');
        const title = link.querySelector('span')?.innerText || "Dashboard"; 

        // Show loader and update content
        loader.style.display = 'block';
        pageTitle.textContent = title;
        contentFrame.src = page;

        // Hide loader when content is loaded
        contentFrame.onload = () => {
            loader.style.display = 'none';
        };
    });
});

// ===== INBOX DRAWER LOGIC =====

// Update message count display
function updateMessageCount(count) {
    const finalCount = parseInt(count) || 0; 
    
    if (finalCount > 0) {
        inboxCountBadge.textContent = finalCount;
        inboxCountBadge.style.display = 'flex';
        drawerCountSpan.textContent = finalCount;
    } else {
        inboxCountBadge.textContent = '0';
        inboxCountBadge.style.display = 'none';
        drawerCountSpan.textContent = '0';
    }
}

// Toggle inbox drawer
inboxToggle.addEventListener('click', () => {
    if (!inboxDrawer.classList.contains('open')) {
        // Close sidebar if open on mobile
        closeSidebar();
        
        // Load the inbox content 
        inboxFrame.src = inboxUrl; 
        inboxDrawer.classList.add('open');
        
        // Prevent body scroll when drawer is open
        document.body.style.overflow = 'hidden';
    } else {
        closeInboxDrawer();
    }
});

// Close inbox drawer function
function closeInboxDrawer() {
    inboxDrawer.classList.remove('open');
    document.body.style.overflow = '';
}

// Close drawer with button
closeDrawerBtn.addEventListener('click', closeInboxDrawer);

// Close drawer with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeInboxDrawer();
        closeSidebar();
    }
});

// ===== POST MESSAGE LISTENER =====
window.addEventListener('message', (event) => {
    // Security check: Only accept messages from the expected origin
    if (event.origin !== SECURE_ORIGIN) {
        console.warn('PostMessage blocked: Invalid origin.', event.origin);
        return;
    }
    
    // Check for a specific message type and valid payload
    if (event.data && event.data.type === 'inboxUpdate' && typeof event.data.newCount !== 'undefined') {
        updateMessageCount(event.data.newCount);
    }
}, false);

// ===== RESPONSIVE BEHAVIOR =====
function handleResize() {
    // Auto-close sidebar on resize to larger screens
    if (window.innerWidth >= 768) {
        closeSidebar();
        document.body.style.overflow = '';
    }
}

window.addEventListener('resize', handleResize);

// ===== INITIALIZATION =====
// Set initial message count
updateMessageCount(initialMessageCount);

// Set initial state based on screen size
handleResize();
</script>

</body>
</html>