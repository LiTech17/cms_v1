</main>

<footer class="site-footer">
  <div class="footer-container">
    
    <!-- Brand / Logo Section -->
    <div class="footer-col footer-brand">
      <?php if (!empty($site['logo'])): ?>
        <img src="../uploads/<?= htmlspecialchars($site['logo']) ?>" 
             alt="<?= htmlspecialchars($site['site_title'] ?? 'NGO Logo') ?>" 
             class="footer-logo">
      <?php endif; ?>
      <h3><?= htmlspecialchars($site['site_title'] ?? 'Our Organization') ?></h3>
      <p><?= htmlspecialchars($site['tagline'] ?? 'Making a Difference') ?></p>
    </div>

    <!-- About / Mission Summary -->
    <div class="footer-col">
      <h4>About Us</h4>
      <p class="mission-snippet">
        <?= htmlspecialchars(substr($org['mission'] ?? 'To serve humanity and promote inclusiveness.', 0, 160)) ?>...
      </p>
    </div>

    <!-- Quick Links -->
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="programmes.php">Programmes</a></li>
        <li><a href="donate.php">Donate</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </div>

    <!-- Contact + Social -->
    <div class="footer-col">
      <h4>Contact</h4>
      <p><strong>Phone:</strong> <?= htmlspecialchars($site['phone'] ?? '') ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($site['email'] ?? '') ?></p>
      <p><strong>Address:</strong> <?= htmlspecialchars($site['physical_address'] ?? '') ?></p>
      
      <div class="social-links">
        <?php if(!empty($site['facebook'])): ?><a href="<?= $site['facebook'] ?>" target="_blank"><i class="fab fa-facebook"></i></a><?php endif; ?>
        <?php if(!empty($site['twitter'])): ?><a href="<?= $site['twitter'] ?>" target="_blank"><i class="fab fa-twitter"></i></a><?php endif; ?>
        <?php if(!empty($site['linkedin'])): ?><a href="<?= $site['linkedin'] ?>" target="_blank"><i class="fab fa-linkedin"></i></a><?php endif; ?>
        <?php if(!empty($site['youtube'])): ?><a href="<?= $site['youtube'] ?>" target="_blank"><i class="fab fa-youtube"></i></a><?php endif; ?>
      </div>
    </div>

  </div>

  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site['site_title'] ?? 'NGO') ?>. All rights reserved.</p>
    <p class="credit">Designed & Developed by <a href="#">Your Team</a></p>
  </div>
</footer>

<script src="https://kit.fontawesome.com/a2d1234567.js" crossorigin="anonymous"></script>
<script src="js/main.js"></script>
</body>
</html>
