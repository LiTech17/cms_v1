<?php
// admin/index.php

// -----------------------------------------------------------
// INITIAL SETUP: Connection, Auth, Session (Implicitly from Auth)
// -----------------------------------------------------------
require_once "../config/database.php";
require_once "../core/auth.php";
checkAdminLogin();

// -----------------------------------------------------------
// ANALYTICS LOGIC INTEGRATION (Tracking Head Count/Activity)
// -----------------------------------------------------------
require_once "../core/analytics.php";
$current_session_id = session_id();
$current_url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$admin_user_id = $_SESSION['user_id'] ?? null;

log_activity(
    'admin_page_view', 
    $current_url_path,
    $admin_user_id,
    $current_session_id
);

// -----------------------------------------------------------
// INBOX LOGIC
// -----------------------------------------------------------
$new_message_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(id) AS count FROM inbox WHERE status = 'new'");
    $new_message_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    $new_message_count = 0;
}

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
    <meta name="theme-color" content="#2b6cb0">
    <title>Admin Dashboard | NGO CMS</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        /* === MOBILE-FIRST CSS VARIABLES === */
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --danger: #e53e3e;
            --success: #38a169;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-md: rgba(0, 0, 0, 0.15);
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
        }

        /* === BASE STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-tap-highlight-color: transparent;
        }

        /* === ADMIN WRAPPER === */
        .admin-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* === MOBILE-FIRST SIDEBAR === */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: var(--card-bg);
            box-shadow: 2px 0 10px var(--shadow-md);
            z-index: 1000;
            transform: translateX(-100%);
            transition: var(--transition-slow);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-lg);
            background: var(--primary);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 70px;
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .sidebar-nav {
            flex: 1;
            padding: var(--space-md) 0;
            overflow-y: auto;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0 var(--space-md);
        }

        .sidebar-nav li + li {
            margin-top: var(--space-xs);
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: var(--space-md) var(--space-lg);
            color: var(--text);
            text-decoration: none;
            border-radius: var(--radius);
            transition: var(--transition);
            gap: var(--space-md);
            font-weight: 500;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(43, 108, 176, 0.1);
            color: var(--primary);
            transform: translateX(4px);
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
            border-top: 1px solid var(--border);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .sidebar-footer a:hover {
            color: var(--danger);
        }

        /* === MAIN CONTENT === */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: var(--transition);
            position: relative;
        }

        /* === TOPBAR === */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            box-shadow: 0 1px 3px var(--shadow);
            padding: var(--space-md) var(--space-lg);
            min-height: 70px;
            position: sticky;
            top: 0;
            z-index: 900;
            border-bottom: 1px solid var(--border);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: var(--space-lg);
        }

        .toggle-btn {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.25rem;
            cursor: pointer;
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
            background: var(--bg);
            color: var(--primary);
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        /* === NOTIFICATION ICON === */
        .notification-icon {
            position: relative;
            cursor: pointer;
            color: var(--text-light);
            font-size: 1.25rem;
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
            background: var(--bg);
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* === USER INFO === */
        .user-info {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius);
            transition: var(--transition);
            cursor: pointer;
        }

        .user-info:hover {
            background: var(--bg);
        }

        .user-info i {
            font-size: 1.5rem;
            color: var(--text-light);
        }

        .user-info span {
            font-weight: 500;
            color: var(--text);
        }

        /* === CONTENT FRAME === */
        .content-wrapper {
            flex: 1;
            position: relative;
            background: var(--bg);
            padding: var(--space-lg);
        }

        iframe#contentFrame {
            width: 100%;
            height: 100%;
            min-height: calc(100vh - 70px);
            border: none;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 3px var(--shadow);
        }

        /* === LOADER === */
        #loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--card-bg);
            padding: var(--space-xl);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 12px var(--shadow-md);
            display: none;
            align-items: center;
            gap: var(--space-md);
            font-weight: 500;
            color: var(--text);
            z-index: 100;
        }

        #loader::before {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* === INBOX DRAWER === */
        #inboxDrawer {
            position: fixed;
            top: 0;
            right: 0;
            width: 100%;
            height: 100vh;
            background: var(--card-bg);
            box-shadow: -5px 0 25px var(--shadow-md);
            z-index: 1100;
            transform: translateX(100%);
            transition: var(--transition-slow);
            display: flex;
            flex-direction: column;
            border-left: 1px solid var(--border);
        }

        #inboxDrawer.open {
            transform: translateX(0);
        }

        .drawer-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary);
            color: white;
        }

        .drawer-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .close-drawer-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: var(--space-sm);
            border-radius: var(--radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .close-drawer-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .drawer-content {
            flex: 1;
            overflow: hidden;
            background: var(--bg);
        }

        #inboxFrame {
            width: 100%;
            height: 100%;
            border: none;
            background: var(--card-bg);
        }

        /* === OVERLAY === */
        #sidebarOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-slow);
        }

        #sidebarOverlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* === ACCESSIBILITY === */
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

        /* Focus styles */
        button:focus-visible,
        a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* === RESPONSIVE DESIGN === */
        /* Tablet */
        @media (min-width: 768px) {
            .admin-wrapper {
                flex-direction: row;
            }

            .sidebar {
                position: static;
                transform: translateX(0);
                width: 280px;
                flex-shrink: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-btn {
                display: none;
            }

            #inboxDrawer {
                width: 400px;
            }

            .content-wrapper {
                padding: var(--space-xl);
            }
        }

        /* Desktop */
        @media (min-width: 1024px) {
            .sidebar {
                width: 300px;
            }

            .sidebar.collapsed {
                width: 80px;
            }

            .sidebar.collapsed .sidebar-nav a span,
            .sidebar.collapsed .sidebar-footer a span {
                display: none;
            }

            .sidebar.collapsed .sidebar-nav a {
                justify-content: center;
                padding: var(--space-md);
            }

            .sidebar.collapsed ~ .main-content {
                margin-left: 80px;
            }
        }

        /* Large Desktop */
        @media (min-width: 1200px) {
            .sidebar {
                width: 320px;
            }

            #inboxDrawer {
                width: 500px;
            }
        }

        /* Small mobile adjustments */
        @media (max-width: 360px) {
            .topbar {
                padding: var(--space-sm);
            }

            .page-title {
                font-size: 1.1rem;
            }

            .user-info span {
                display: none;
            }

            .content-wrapper {
                padding: var(--space-md);
            }
        }

        /* === TOUCH DEVICE OPTIMIZATIONS === */
        @media (hover: none) {
            .sidebar-nav a:hover {
                transform: none;
            }

            .notification-icon:hover,
            .user-info:hover {
                background: transparent;
            }
        }

        /* === DARK MODE SUPPORT === */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #1a202c;
                --card-bg: #2d3748;
                --text: #f7fafc;
                --text-light: #cbd5e0;
                --border: #4a5568;
                --shadow: rgba(0, 0, 0, 0.3);
                --shadow-md: rgba(0, 0, 0, 0.4);
            }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>NGO CMS</h2>
        </div>
        <nav class="sidebar-nav" aria-label="Main navigation">
            <ul>
                <li><a href="dashboard.php" data-page="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="analytics_report.php" data-page="analytics_report.php"><i class="fas fa-chart-line"></i> <span>Analytics Report</span></a></li>
                <li><a href="settings.php" data-page="settings.php"><i class="fas fa-cogs"></i> <span>Site Settings</span></a></li>
                <li><a href="home_hero.php" data-page="home_hero.php"><i class="fas fa-images"></i> <span>Home Hero</span></a></li>
                <li><a href="organization_info.php" data-page="organization_info.php"><i class="fas fa-building"></i> <span>Organization Info</span></a></li>
                <li><a href="about_page.php" data-page="about_page.php"><i class="fas fa-landmark"></i> <span>About Page</span></a></li>
                <li><a href="inbox.php" data-page="inbox.php"><i class="fas fa-inbox"></i> <span>Inbox</span></a></li>
                <li><a href="operational_bases.php" data-page="operational_bases.php"><i class="fas fa-map-marker-alt"></i> <span>Operational Bases</span></a></li>
                <li><a href="leadership_team.php" data-page="leadership_team.php"><i class="fas fa-users"></i> <span>Leadership</span></a></li>
                <li><a href="programmes.php" data-page="programmes.php"><i class="fas fa-graduation-cap"></i> <span>Programmes</span></a></li>
                <li><a href="donation_methods.php" data-page="donation_methods.php"><i class="fas fa-donate"></i> <span>Donations</span></a></li>
                <li><a href="editors.php" data-page="editors.php"><i class="fas fa-user-edit"></i> <span>Editors</span></a></li>
                <li><a href="profile.php" data-page="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button id="toggleSidebar" class="toggle-btn" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="pageTitle" class="page-title">Dashboard</h1>
            </div>
            
            <div class="topbar-right">
                <div class="notification-icon" id="inboxToggle" aria-label="Open inbox" role="button" tabindex="0">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <span class="notification-badge" id="inboxCount" aria-live="polite"><?= $new_message_count ?></span>
                </div>

                <div class="user-info" role="button" tabindex="0">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div id="loader" aria-live="polite">Loading content...</div>
            <iframe id="contentFrame" src="dashboard.php" title="Admin Content" loading="eager"></iframe>
        </div>
    </main>
</div>

<!-- Inbox Drawer -->
<div id="inboxDrawer" role="dialog" aria-label="Inbox messages" aria-modal="true">
    <div class="drawer-header">
        <h3>Inbox <span id="drawerCount">(New: <?= $new_message_count ?>)</span></h3>
        <button id="closeDrawer" class="close-drawer-btn" aria-label="Close inbox">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="drawer-content">
        <iframe id="inboxFrame" src="" title="Inbox Messages" loading="eager"></iframe>
    </div>
</div>

<!-- Overlay -->
<div id="sidebarOverlay"></div>

<script>
// Enhanced Admin Interface Manager
class AdminInterface {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.contentFrame = document.getElementById('contentFrame');
        this.pageTitle = document.getElementById('pageTitle');
        this.loader = document.getElementById('loader');
        this.toggleSidebarBtn = document.getElementById('toggleSidebar');
        this.inboxToggle = document.getElementById('inboxToggle');
        this.inboxDrawer = document.getElementById('inboxDrawer');
        this.closeDrawerBtn = document.getElementById('closeDrawer');
        this.inboxFrame = document.getElementById('inboxFrame');
        this.inboxCountBadge = document.getElementById('inboxCount');
        this.drawerCountSpan = document.getElementById('drawerCount');
        this.overlay = document.getElementById('sidebarOverlay');
        this.sidebarLinks = document.querySelectorAll('.sidebar-nav a');
        
        // Configuration
        this.config = {
            inboxUrl: "<?= $inbox_url; ?>",
            initialMessageCount: <?= $new_message_count; ?>,
            secureOrigin: "<?= $secure_origin; ?>"
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupResponsiveBehavior();
        this.setupAccessibility();
        this.updateMessageCount(this.config.initialMessageCount);
    }
    
    setupEventListeners() {
        // Sidebar toggle
        this.toggleSidebarBtn.addEventListener('click', () => this.toggleSidebar());
        
        // Overlay click to close sidebar
        this.overlay.addEventListener('click', () => this.closeSidebar());
        
        // Navigation links
        this.sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleNavigation(e, link));
        });
        
        // Inbox drawer
        this.inboxToggle.addEventListener('click', () => this.toggleInboxDrawer());
        this.closeDrawerBtn.addEventListener('click', () => this.closeInboxDrawer());
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Resize handling
        window.addEventListener('resize', () => this.handleResize());
        
        // PostMessage listener for inbox updates
        window.addEventListener('message', (e) => this.handlePostMessage(e));
    }
    
    setupResponsiveBehavior() {
        // Close sidebar by default on mobile
        if (window.innerWidth < 768) {
            this.closeSidebar();
        }
    }
    
    setupAccessibility() {
        // Set initial ARIA states
        this.sidebar.setAttribute('aria-hidden', window.innerWidth < 768);
        this.inboxDrawer.setAttribute('aria-hidden', true);
    }
    
    toggleSidebar() {
        const isMobile = window.innerWidth < 768;
        const isOpen = this.sidebar.classList.contains('open');
        
        if (isMobile) {
            if (isOpen) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
        } else {
            // Desktop: toggle collapsed state
            this.sidebar.classList.toggle('collapsed');
        }
    }
    
    openSidebar() {
        this.sidebar.classList.add('open');
        this.overlay.classList.add('active');
        this.sidebar.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    
    closeSidebar() {
        this.sidebar.classList.remove('open');
        this.overlay.classList.remove('active');
        if (window.innerWidth < 768) {
            this.sidebar.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
    }
    
    handleNavigation(e, link) {
        e.preventDefault();
        
        // Close drawers
        this.closeInboxDrawer();
        this.closeSidebar();
        
        // Update active state
        this.sidebarLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        
        const page = link.getAttribute('data-page');
        const title = link.querySelector('span')?.innerText || "Dashboard";
        
        this.showLoader();
        this.pageTitle.textContent = title;
        this.contentFrame.src = page;
        
        // Update browser history
        history.pushState({ page, title }, '', `?page=${page}`);
    }
    
    toggleInboxDrawer() {
        if (!this.inboxDrawer.classList.contains('open')) {
            this.openInboxDrawer();
        } else {
            this.closeInboxDrawer();
        }
    }
    
    openInboxDrawer() {
        this.closeSidebar();
        this.inboxFrame.src = this.config.inboxUrl;
        this.inboxDrawer.classList.add('open');
        this.inboxDrawer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    
    closeInboxDrawer() {
        this.inboxDrawer.classList.remove('open');
        this.inboxDrawer.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    
    updateMessageCount(count) {
        const finalCount = parseInt(count) || 0;
        this.inboxCountBadge.textContent = finalCount;
        this.drawerCountSpan.textContent = `(New: ${finalCount})`;
        
        if (finalCount > 0) {
            this.inboxCountBadge.style.display = 'flex';
        } else {
            this.inboxCountBadge.style.display = 'none';
        }
    }
    
    showLoader() {
        this.loader.style.display = 'flex';
        this.contentFrame.onload = () => {
            this.loader.style.display = 'none';
        };
    }
    
    handleKeyboard(e) {
        // ESC key closes modals/drawers
        if (e.key === 'Escape') {
            if (this.inboxDrawer.classList.contains('open')) {
                this.closeInboxDrawer();
            } else if (this.sidebar.classList.contains('open')) {
                this.closeSidebar();
            }
        }
    }
    
    handleResize() {
        // Auto-close sidebar on mobile when resizing to desktop
        if (window.innerWidth >= 768) {
            this.closeSidebar();
            this.sidebar.setAttribute('aria-hidden', 'false');
        } else if (this.sidebar.classList.contains('open')) {
            this.sidebar.setAttribute('aria-hidden', 'false');
        }
    }
    
    handlePostMessage(event) {
        // Security check
        if (event.origin !== this.config.secureOrigin) {
            console.warn('PostMessage blocked: Invalid origin.', event.origin);
            return;
        }
        
        // Handle inbox updates
        if (event.data && event.data.type === 'inboxUpdate' && typeof event.data.newCount !== 'undefined') {
            this.updateMessageCount(event.data.newCount);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminInterface();
});

// Handle browser back/forward
window.addEventListener('popstate', (event) => {
    if (event.state && event.state.page) {
        const link = document.querySelector(`[data-page="${event.state.page}"]`);
        if (link) {
            link.click();
        }
    }
});
</script>

</body>
</html>