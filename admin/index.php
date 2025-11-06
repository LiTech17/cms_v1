<?php
require_once "../core/auth.php";
checkLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | NGO CMS</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
    <style>
        iframe#contentFrame {
            width: 100%;
            height: calc(100vh - 70px);
            border: none;
            background: #fff;
            border-radius: 8px;
        }

        /* Add a smooth loading spinner */
        #loader {
            position: absolute;
            top: 50%;
            left: 55%;
            transform: translate(-50%, -50%);
            font-size: 1.2rem;
            color: var(--primary);
            display: none;
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>NGO CMS</h2>
            <button id="toggleSidebar" class="toggle-btn"><i class="fas fa-bars"></i></button>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php" data-page="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="settings.php" data-page="settings.php"><i class="fas fa-cogs"></i> <span>Site Settings</span></a></li>
                <li><a href="home_hero.php" data-page="home_hero.php"><i class="fas fa-images"></i> <span>Home Hero</span></a></li>
                <li><a href="organization_info.php" data-page="organization_info.php"><i class="fas fa-building"></i> <span>Organization Info</span></a></li>
                <li><a href="about_page.php" data-page="about_page.php"><i class="fas fa-landmark"></i> <span>About Page</span></a></li>
                <li><a href="operational_bases.php" data-page="operational_bases.php"><i class="fas fa-map-marker-alt"></i> <span>Operational Bases</span></a></li>
                <li><a href="leadership_team.php" data-page="leadership_team.php"><i class="fas fa-users"></i> <span>Leadership</span></a></li>
                <li><a href="programmes.php" data-page="programmes.php"><i class="fas fa-graduation-cap"></i> <span>Programmes</span></a></li>
                <li><a href="donation_methods.php" data-page="donation_methods.php"><i class="fas fa-donate"></i> <span>Donations</span></a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <main class="main-content">
        <header class="topbar">
            <h1 id="pageTitle">Dashboard</h1>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </header>

        <!-- SPA CONTENT AREA -->
        <div id="loader">Loading...</div>
        <iframe id="contentFrame" src="dashboard.php"></iframe>
    </main>
</div>

<script>
const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
const contentFrame = document.getElementById('contentFrame');
const pageTitle = document.getElementById('pageTitle');
const loader = document.getElementById('loader');

// Sidebar toggle
document.getElementById('toggleSidebar').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
});

// SPA behavior
sidebarLinks.forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();

        // Remove old active link
        sidebarLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        const page = link.getAttribute('data-page');
        const title = link.querySelector('span')?.innerText || "Dashboard";

        loader.style.display = 'block';
        pageTitle.textContent = title;
        contentFrame.src = page;

        // Wait for iframe load
        contentFrame.onload = () => loader.style.display = 'none';
    });
});
</script>

</body>
</html>
