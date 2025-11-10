<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch donation methods
$methods = $pdo->query("SELECT * FROM donation_methods ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2b6cb0">
    <title>Support Our Work | Donate</title>
    <style>
        /* === MOBILE-FIRST CSS VARIABLES === */
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --success: #38a169;
            --warning: #d69e2e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --text-muted: #a0aec0;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-md: rgba(0, 0, 0, 0.15);
            --shadow-lg: rgba(0, 0, 0, 0.2);
            --radius: 12px;
            --radius-lg: 16px;
            --transition: all 0.2s ease-in-out;
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
        }

        /* === BASE STYLES === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-tap-highlight-color: transparent;
        }

        /* === PAGE LAYOUT === */
        .donate-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--space-xl) var(--space-md);
            min-height: 100vh;
        }

        /* === PAGE HEADER === */
        .page-header {
            text-align: center;
            margin-bottom: var(--space-2xl);
            padding: var(--space-xl) 0;
        }

        .page-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: var(--space-md);
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* === DONATION GRID === */
        .donation-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-xl);
            margin-bottom: var(--space-2xl);
        }

        /* === DONATION CARD === */
        .donation-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: 0 4px 6px var(--shadow);
            padding: var(--space-xl);
            transition: var(--transition-slow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .donation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }

        .donation-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px var(--shadow-lg);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: var(--space-lg);
            gap: var(--space-md);
        }

        .method-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
            line-height: 1.3;
        }

        .method-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* === QR CODE STYLES === */
        .qr-section {
            text-align: center;
            margin: var(--space-lg) 0;
            padding: var(--space-lg);
            background: var(--bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        .qr-image {
            max-width: 200px;
            height: auto;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .qr-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 24px var(--shadow-md);
        }

        .qr-caption {
            margin-top: var(--space-md);
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* === ACCOUNT DETAILS === */
        .account-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-md);
            background: var(--bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .detail-item:hover {
            background: rgba(43, 108, 176, 0.05);
            border-color: var(--primary);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
            margin-bottom: var(--space-xs);
        }

        .detail-value {
            color: var(--text);
            font-weight: 500;
            font-size: 1.1rem;
            word-break: break-all;
        }

        /* === INSTRUCTIONS === */
        .instructions {
            background: linear-gradient(135deg, #f0fff4, #f0f9ff);
            border: 1px solid #c6f6d5;
            border-radius: var(--radius);
            padding: var(--space-lg);
            margin-top: var(--space-lg);
        }

        .instructions-title {
            font-weight: 600;
            color: var(--success);
            margin-bottom: var(--space-md);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .instructions-content {
            color: var(--text);
            line-height: 1.7;
        }

        .instructions-content p {
            margin-bottom: var(--space-sm);
        }

        .instructions-content p:last-child {
            margin-bottom: 0;
        }

        /* === COPY TO CLIPBOARD === */
        .copy-section {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            margin-top: var(--space-sm);
        }

        .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 0.8rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .copy-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .copy-success {
            color: var(--success);
            font-size: 0.8rem;
            font-weight: 500;
            opacity: 0;
            transition: var(--transition);
        }

        .copy-success.show {
            opacity: 1;
        }

        /* === EMPTY STATE === */
        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: var(--space-lg);
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: var(--space-md);
            color: var(--text);
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
            .donate-page {
                padding: var(--space-2xl) var(--space-lg);
            }

            .donation-grid {
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                gap: var(--space-2xl);
            }

            .account-details {
                grid-template-columns: 1fr 1fr;
            }

            .page-header h2 {
                font-size: 3rem;
            }
        }

        /* Desktop */
        @media (min-width: 1024px) {
            .donate-page {
                padding: var(--space-2xl);
            }

            .donation-grid {
                grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            }

            .card-header {
                align-items: center;
            }
        }

        /* Large Desktop */
        @media (min-width: 1200px) {
            .donation-grid {
                grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            }
        }

        /* Small mobile adjustments */
        @media (max-width: 360px) {
            .donate-page {
                padding: var(--space-lg) var(--space-sm);
            }

            .donation-card {
                padding: var(--space-lg);
            }

            .method-name {
                font-size: 1.25rem;
            }

            .qr-image {
                max-width: 150px;
            }
        }

        /* === TOUCH DEVICE OPTIMIZATIONS === */
        @media (hover: none) {
            .donation-card:hover {
                transform: none;
                box-shadow: 0 4px 6px var(--shadow);
            }

            .qr-image:hover {
                transform: none;
            }

            .detail-item:hover {
                background: var(--bg);
                border-color: var(--border);
            }
        }

        /* === DARK MODE SUPPORT === */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #1a202c;
                --card-bg: #2d3748;
                --text: #f7fafc;
                --text-light: #cbd5e0;
                --text-muted: #a0aec0;
                --border: #4a5568;
                --shadow: rgba(0, 0, 0, 0.3);
                --shadow-md: rgba(0, 0, 0, 0.4);
                --shadow-lg: rgba(0, 0, 0, 0.5);
            }

            .instructions {
                background: linear-gradient(135deg, #1a4731, #1a365d);
                border-color: #2d5a3a;
            }

            .detail-item {
                background: #2d3748;
            }

            .qr-section {
                background: #2d3748;
            }
        }
    </style>
</head>
<body>
    <section class="donate-page">
        <!-- Page Header -->
        <div class="page-header">
            <h2>Support Our Work</h2>
            <p class="page-subtitle">Your generous donation helps us continue our mission and make a positive impact in our community.</p>
        </div>

        <!-- Donation Methods Grid -->
        <div class="donation-grid">
            <?php if (count($methods) > 0): ?>
                <?php foreach($methods as $m): ?>
                    <div class="donation-card">
                        <!-- Card Header -->
                        <div class="card-header">
                            <h3 class="method-name"><?= htmlspecialchars($m['method_name']) ?></h3>
                            <div class="method-icon">
                                <i class="fas fa-donate"></i>
                            </div>
                        </div>

                        <!-- QR Code Section -->
                        <?php if($m['qr_image']): ?>
                            <div class="qr-section">
                                <img src="../uploads/<?= htmlspecialchars($m['qr_image']) ?>" 
                                     alt="QR Code for <?= htmlspecialchars($m['method_name']) ?>" 
                                     class="qr-image"
                                     loading="lazy">
                                <div class="qr-caption">Scan to Donate</div>
                            </div>
                        <?php endif; ?>

                        <!-- Account Details -->
                        <div class="account-details">
                            <?php if(!empty($m['account_name'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Account Name</div>
                                        <div class="detail-value"><?= htmlspecialchars($m['account_name']) ?></div>
                                        <div class="copy-section">
                                            <button class="copy-btn" data-text="<?= htmlspecialchars($m['account_name']) ?>">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                            <span class="copy-success">Copied!</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($m['account_number'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Account Number</div>
                                        <div class="detail-value"><?= htmlspecialchars($m['account_number']) ?></div>
                                        <div class="copy-section">
                                            <button class="copy-btn" data-text="<?= htmlspecialchars($m['account_number']) ?>">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                            <span class="copy-success">Copied!</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($m['merchant_code'])): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <div class="detail-content">
                                        <div class="detail-label">Merchant Code</div>
                                        <div class="detail-value"><?= htmlspecialchars($m['merchant_code']) ?></div>
                                        <div class="copy-section">
                                            <button class="copy-btn" data-text="<?= htmlspecialchars($m['merchant_code']) ?>">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                            <span class="copy-success">Copied!</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Instructions -->
                        <?php if(!empty($m['instructions'])): ?>
                            <div class="instructions">
                                <div class="instructions-title">
                                    <i class="fas fa-info-circle"></i>
                                    Instructions
                                </div>
                                <div class="instructions-content">
                                    <?= nl2br(htmlspecialchars($m['instructions'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-donate"></i>
                    <h3>No Donation Methods Available</h3>
                    <p>We're currently setting up our donation methods. Please check back soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Enhanced Donation Page Functionality
        class DonationPage {
            constructor() {
                this.copyButtons = document.querySelectorAll('.copy-btn');
                this.init();
            }
            
            init() {
                this.setupCopyToClipboard();
                this.setupAnimations();
                this.setupAccessibility();
            }
            
            setupCopyToClipboard() {
                this.copyButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        const textToCopy = button.getAttribute('data-text');
                        const successMessage = button.nextElementSibling;
                        
                        this.copyToClipboard(textToCopy).then(() => {
                            // Show success message
                            successMessage.classList.add('show');
                            
                            // Update button text temporarily
                            const originalHTML = button.innerHTML;
                            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                            button.style.background = 'var(--success)';
                            
                            setTimeout(() => {
                                successMessage.classList.remove('show');
                                button.innerHTML = originalHTML;
                                button.style.background = '';
                            }, 2000);
                            
                        }).catch(err => {
                            console.error('Failed to copy text: ', err);
                            // Fallback for older browsers
                            this.fallbackCopyText(textToCopy);
                        });
                    });
                });
            }
            
            async copyToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback for older browsers
                    this.fallbackCopyText(text);
                }
            }
            
            fallbackCopyText(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.error('Fallback copy failed: ', err);
                }
                
                document.body.removeChild(textArea);
            }
            
            setupAnimations() {
                // Add intersection observer for scroll animations
                const observerOptions = {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                };
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, observerOptions);
                
                // Observe donation cards
                document.querySelectorAll('.donation-card').forEach(card => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    observer.observe(card);
                });
            }
            
            setupAccessibility() {
                // Add keyboard navigation support
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        // Close any open modals or focus traps
                        document.activeElement.blur();
                    }
                });
                
                // Add focus styles for better accessibility
                const focusableElements = document.querySelectorAll('button, [tabindex]');
                focusableElements.forEach(el => {
                    el.addEventListener('focus', () => {
                        el.style.outline = '2px solid var(--primary)';
                        el.style.outlineOffset = '2px';
                    });
                    
                    el.addEventListener('blur', () => {
                        el.style.outline = 'none';
                    });
                });
            }
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new DonationPage();
        });
        
        // Touch device detection
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
        
        // Reduced motion support
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.style.setProperty('--transition', 'none');
            document.documentElement.style.setProperty('--transition-slow', 'none');
        }
    </script>
</body>
</html>

<?php include "partials/footer.php"; ?>