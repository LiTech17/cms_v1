<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch donation methods
$methods = $pdo->query("SELECT * FROM donation_methods ORDER BY id DESC")->fetchAll();
?>

<section class="donate-page">
    <h2>Support Our Work</h2>
    <?php foreach($methods as $m): ?>
        <div class="donation-card">
            <h3><?= htmlspecialchars($m['method_name']) ?></h3>
            <?php if($m['qr_image']): ?>
                <img src="../uploads/<?= htmlspecialchars($m['qr_image']) ?>" alt="">
            <?php endif; ?>
            <p><strong>Account:</strong> <?= htmlspecialchars($m['account_name']) ?></p>
            <p><strong>Number:</strong> <?= htmlspecialchars($m['account_number']) ?></p>
            <p><strong>Merchant:</strong> <?= htmlspecialchars($m['merchant_code']) ?></p>
            <p><?= nl2br(htmlspecialchars($m['instructions'])) ?></p>
        </div>
    <?php endforeach; ?>
</section>

<?php include "partials/footer.php"; ?>
