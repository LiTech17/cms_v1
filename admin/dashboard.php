<?php
require_once "../core/auth.php";
checkLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | NGO CMS</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="admin-wrapper">


    <!-- MAIN CONTENT -->
    <main class="main-content">
        <section class="dashboard-content">
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
document.getElementById('toggleSidebar').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('collapsed');
});
</script>

</body>
</html>
