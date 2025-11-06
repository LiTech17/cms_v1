<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch all programmes
$programmes = $pdo->query("SELECT * FROM programmes ORDER BY id DESC")->fetchAll();
?>

<section class="programmes-grid">
    <h2>Our Programmes</h2>
    <?php foreach($programmes as $p): ?>
        <div class="programme-card">
            <h3><?= htmlspecialchars($p['title']) ?></h3>
            <p><?= nl2br(htmlspecialchars($p['introduction'])) ?></p>
            <a href="programme_detail.php?id=<?= $p['id'] ?>">View Details</a>
        </div>
    <?php endforeach; ?>
</section>

<?php include "partials/footer.php"; ?>
