<?php
require_once "../config/database.php";
require_once "../core/auth.php";

// Fetch site configuration
$site = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($site['site_title'] ?? 'NGO') ?></title>
<link rel="icon" href="../uploads/<?= htmlspecialchars($site['favicon'] ?? 'favicon.ico') ?>">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
  <nav>
    <a href="index.php" style="font-size:1.5rem; font-weight:bold;">
      <?= htmlspecialchars($site['site_title'] ?? 'NGO') ?>
    </a>
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="programmes.php">Programmes</a></li>
      <li><a href="donate.php">Donate</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="theme-toggle">🌓</button>
      <div class="hamburger">
        <div></div><div></div><div></div>
      </div>
    </div>
  </nav>
</header>


<main>
