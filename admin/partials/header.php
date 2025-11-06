<!-- partials/header.php -->
<header class="topbar">
    <div class="topbar-left">
        <h1>Admin Dashboard</h1>
    </div>
    <div class="topbar-right">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
    </div>
</header>
