<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch all programmes
$programmes = $pdo->query("SELECT * FROM programmes ORDER BY id DESC")->fetchAll();

// Fetch site configuration for fallback
$site = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore our community development programmes and initiatives making a positive impact.">
    <title>Our Programmes | <?= htmlspecialchars($site['site_title'] ?? 'NGO Name') ?></title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #3b82f6;
            --accent: #06b6d4;
            --accent-light: #22d3ee;
            --text: #1e293b;
            --text-light: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
            --gradient: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #60a5fa;
            --accent: #22d3ee;
            --accent-light: #06b6d4;
            --text: #e2e8f0;
            --text-light: #94a3b8;
            --bg: #0f172a;
            --white: #1e293b;
            --border: #334155;
            --gradient: linear-gradient(135deg, #3b82f6 0%, #22d3ee 100%);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        /* Base Styles - Mobile First */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Hero Section - Mobile First */
        .hero {
            position: relative;
            background: var(--gradient);
            color: white;
            padding: 4rem 0 3rem;
            overflow: hidden;
            min-height: 40vh;
            display: flex;
            align-items: center;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -150px;
            right: -100px;
            animation: pulse 8s ease-in-out infinite;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
            width: 100%;
        }

        .hero h1 {
            font-size: clamp(1.75rem, 6vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 3vw, 1.2rem);
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
            padding: 0 1rem;
        }

        /* Programmes Section - Mobile First */
        .programmes-section {
            padding: 3rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .section-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--gradient);
            color: white;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
        }

        .section-description {
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            color: var(--text-light);
            max-width: 650px;
            margin: 0 auto 2rem;
            padding: 0 1rem;
        }

        /* Programme Cards Grid - Mobile First */
        .programmes-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .programme-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .programme-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .programme-card:active {
            transform: scale(0.98);
        }

        .programme-icon {
            width: 100%;
            padding: 2rem 1.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-wrapper {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .programme-content {
            padding: 0 1.5rem 2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .programme-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text);
            line-height: 1.3;
        }

        .programme-description {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .programme-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            align-self: flex-start;
            box-shadow: var(--shadow-sm);
        }

        .programme-link:active {
            transform: scale(0.95);
        }

        .programme-link i {
            transition: transform 0.3s ease;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .empty-state p {
            font-size: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.05;
                transform: scale(1);
            }
            50% {
                opacity: 0.08;
                transform: scale(1.1);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .programme-card {
            animation: slideIn 0.5s ease-out;
        }

        .programme-card:nth-child(1) { animation-delay: 0.1s; }
        .programme-card:nth-child(2) { animation-delay: 0.2s; }
        .programme-card:nth-child(3) { animation-delay: 0.3s; }
        .programme-card:nth-child(4) { animation-delay: 0.4s; }
        .programme-card:nth-child(5) { animation-delay: 0.5s; }
        .programme-card:nth-child(6) { animation-delay: 0.6s; }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Touch-friendly tap targets */
        a, button {
            -webkit-tap-highlight-color: rgba(37, 99, 235, 0.1);
            min-height: 44px;
        }

        /* Tablet (481px - 768px) */
        @media (min-width: 481px) {
            .container {
                padding: 0 1.5rem;
            }

            .programmes-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 2rem;
            }

            .icon-wrapper {
                width: 65px;
                height: 65px;
                font-size: 1.6rem;
            }

            .programme-card h3 {
                font-size: 1.35rem;
            }

            .programmes-section {
                padding: 4rem 0;
            }

            .section-header {
                margin-bottom: 3rem;
            }
        }

        /* Desktop (769px and up) */
        @media (min-width: 769px) {
            .container {
                padding: 0 2rem;
            }

            .hero {
                padding: 6rem 0 5rem;
                min-height: 50vh;
            }

            .hero::after {
                width: 500px;
                height: 500px;
                top: -250px;
                right: -150px;
            }

            .programmes-section {
                padding: 5rem 0;
            }

            .section-header {
                margin-bottom: 4rem;
            }

            .section-badge {
                font-size: 0.875rem;
                padding: 0.5rem 1.25rem;
            }

            .programmes-grid {
                grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
                gap: 2.5rem;
            }

            .programme-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--shadow-lg);
            }

            .programme-card:hover::before {
                transform: scaleX(1);
            }

            .programme-link:hover {
                box-shadow: var(--shadow);
                transform: translateY(-2px);
            }

            .programme-link:hover i {
                transform: translateX(4px);
            }

            .programme-icon {
                padding: 2.5rem 2rem 1.5rem;
            }

            .programme-content {
                padding: 0 2rem 2.5rem;
            }

            .icon-wrapper {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }

            .programme-card h3 {
                font-size: 1.5rem;
            }

            .programme-description {
                font-size: 1rem;
            }
        }

        /* Large Desktop (1024px and up) */
        @media (min-width: 1024px) {
            .programmes-grid {
                grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            }
        }

        /* Extra Large Desktop (1280px and up) */
        @media (min-width: 1280px) {
            .programmes-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Dark Mode Adjustments */
        [data-theme="dark"] .hero::after {
            background: rgba(255, 255, 255, 0.03);
        }
    </style>
</head>
<body>
    <script>
        // Dark mode initialization - must run before page renders
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Hero Section -->
    <header class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Our Programmes</h1>
                <p class="hero-subtitle">
                    Transforming communities through innovative and sustainable development initiatives
                </p>
            </div>
        </div>
    </header>

    <!-- Programmes Section -->
    <section class="programmes-section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">What We Do</span>
                <p class="section-description">
                    Explore our diverse range of programmes designed to create lasting positive change in communities
                </p>
            </div>

            <?php if(empty($programmes)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    <h3>No Programmes Yet</h3>
                    <p>Check back soon for updates on our upcoming community development programmes.</p>
                </div>
            <?php else: ?>
                <!-- Programmes Grid -->
                <div class="programmes-grid">
                    <?php 
                    // Icon mapping for different programme types
                    $icons = [
                        'fas fa-graduation-cap', 'fas fa-heartbeat', 'fas fa-hands-helping',
                        'fas fa-seedling', 'fas fa-water', 'fas fa-lightbulb',
                        'fas fa-users', 'fas fa-home', 'fas fa-leaf'
                    ];
                    $iconIndex = 0;
                    
                    foreach($programmes as $p): 
                        $icon = $icons[$iconIndex % count($icons)];
                        $iconIndex++;
                    ?>
                        <article class="programme-card">
                            <div class="programme-icon">
                                <div class="icon-wrapper">
                                    <i class="<?= $icon ?>" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="programme-content">
                                <h3><?= htmlspecialchars($p['title']) ?></h3>
                                <div class="programme-description">
                                    <?= nl2br(htmlspecialchars(substr($p['introduction'], 0, 180))) ?><?= strlen($p['introduction']) > 180 ? '...' : '' ?>
                                </div>
                                <a href="programme_detail.php?id=<?= $p['id'] ?>" class="programme-link">
                                    Learn More
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include "partials/footer.php"; ?>
</body>
</html>