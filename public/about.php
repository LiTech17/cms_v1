<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch about page content
$about = $pdo->query("SELECT * FROM about_page LIMIT 1")->fetch();
$bases = $pdo->query("SELECT * FROM operational_bases ORDER BY id ASC")->fetchAll();
$leaders = $pdo->query("SELECT * FROM leadership_team ORDER BY id ASC")->fetchAll();

// Fallback to organization_info if about_page is empty
$org = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// Helper function for displaying stats
function displayStat($value, $default) {
    return !empty($value) ? htmlspecialchars($value) : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Learn about our organization, mission, vision, and the impact we're making in communities.">
    <title>About Us | <?= htmlspecialchars($org['name'] ?? 'NGO Name') ?></title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
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

        /* Hero Header - Mobile First */
        .hero {
            position: relative;
            background: var(--gradient);
            color: white;
            padding: 4rem 0 3rem;
            overflow: hidden;
            min-height: 50vh;
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

        <?php if(!empty($about['hero_image'])): ?>
        .hero {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.85) 0%, rgba(6, 182, 212, 0.9) 100%),
                        url('../uploads/<?= htmlspecialchars($about['hero_image']) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: scroll;
        }
        <?php endif; ?>

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
            width: 100%;
        }

        .hero h1 {
            font-size: clamp(1.75rem, 6vw, 4rem);
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 3vw, 1.3rem);
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
            padding: 0 1rem;
        }

        /* Section Styles - Mobile First */
        .section {
            padding: 3rem 0;
        }

        .section-alt {
            background: var(--white);
        }

        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.6s ease-out;
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

        .section-title {
            font-size: clamp(1.75rem, 5vw, 3rem);
            font-weight: 800;
            color: var(--text);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .section-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            color: var(--text-light);
            max-width: 650px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Mission Cards - Mobile First */
        .mission-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .mission-card {
            background: var(--white);
            padding: 2rem 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .mission-card:active {
            transform: scale(0.98);
        }

        .mission-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .mission-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        .mission-card p {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        /* Stats Section - Mobile First */
        .stats-section {
            background: var(--gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
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

        [data-theme="dark"] .stats-section::before {
            background: rgba(255, 255, 255, 0.03);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem 1rem;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 0.25rem;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Story Section - Mobile First */
        .story-content {
            max-width: 900px;
            margin: 0 auto;
            background: var(--white);
            padding: 2rem 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text);
            border-left: 4px solid var(--primary);
        }

        /* Team Grid - Mobile First */
        .team-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .team-card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .team-card:active {
            transform: scale(0.98);
        }

        .team-image-wrapper {
            position: relative;
            overflow: hidden;
            padding-top: 100%;
            background: var(--border);
        }

        .team-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .team-content {
            padding: 1.5rem;
        }

        .team-name {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .team-role {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .team-qualification {
            color: var(--text-light);
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            font-style: italic;
        }

        .team-bio {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Bases Section - Mobile First */
        .bases-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .base-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        .base-card:active {
            transform: translateX(4px);
        }

        .base-card h3 {
            color: var(--primary);
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .base-card h3 i {
            font-size: 1rem;
            flex-shrink: 0;
        }

        .base-card p {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 0.95rem;
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

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Touch-friendly tap targets */
        a, button, .mission-card, .team-card, .base-card {
            -webkit-tap-highlight-color: rgba(37, 99, 235, 0.1);
            min-height: 44px;
            min-width: 44px;
        }

        /* Tablet (481px - 768px) */
        @media (min-width: 481px) {
            .container {
                padding: 0 1.5rem;
            }

            .mission-grid,
            .bases-grid {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            }

            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2.5rem 2rem;
            }

            .stat-icon {
                font-size: 2.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .stat-label {
                font-size: 1rem;
            }

            .section {
                padding: 4rem 0;
            }

            .section-header {
                margin-bottom: 3rem;
            }

            .mission-card,
            .base-card {
                padding: 2rem;
            }

            .mission-icon {
                width: 65px;
                height: 65px;
                font-size: 1.6rem;
            }
        }

        /* Desktop (769px and up) */
        @media (min-width: 769px) {
            .container {
                padding: 0 2rem;
            }

            .hero {
                padding: 8rem 0 6rem;
                min-height: 60vh;
            }

            <?php if(!empty($about['hero_image'])): ?>
            .hero {
                background-attachment: fixed;
            }
            <?php endif; ?>

            .section {
                padding: 5rem 0;
            }

            .section-header {
                margin-bottom: 4rem;
            }

            .section-badge {
                font-size: 0.875rem;
                padding: 0.5rem 1.25rem;
            }

            .mission-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
            }

            .mission-card {
                padding: 2.5rem;
            }

            .mission-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--shadow-lg);
            }

            .mission-card:hover::before {
                transform: scaleX(1);
            }

            .mission-icon {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }

            .stats-section::before {
                width: 500px;
                height: 500px;
                top: -250px;
                right: -150px;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 3rem;
            }

            .stat-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }

            .stat-number {
                font-size: 3rem;
            }

            .stat-label {
                font-size: 1.1rem;
            }

            .story-content {
                padding: 3rem;
                font-size: 1.15rem;
                line-height: 1.9;
            }

            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 2rem;
            }

            .team-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--shadow-lg);
            }

            .team-card:hover .team-image {
                transform: scale(1.05);
            }

            .team-image-wrapper {
                padding-top: 0;
                height: 280px;
            }

            .team-image {
                position: static;
            }

            .team-name {
                font-size: 1.25rem;
            }

            .team-role {
                font-size: 0.95rem;
            }

            .team-qualification {
                font-size: 0.875rem;
            }

            .bases-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 2rem;
            }

            .base-card {
                padding: 2rem;
            }

            .base-card:hover {
                transform: translateX(8px);
                box-shadow: var(--shadow-lg);
            }

            .base-card h3 {
                font-size: 1.3rem;
            }

            .base-card h3 i {
                font-size: 1.1rem;
            }
        }

        /* Large Desktop (1024px and up) */
        @media (min-width: 1024px) {
            .mission-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Extra Large Desktop (1280px and up) */
        @media (min-width: 1280px) {
            .team-grid {
                grid-template-columns: repeat(4, 1fr);
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

        /* Print Styles */
        @media print {
            .hero {
                background: none !important;
                color: #000 !important;
                padding: 2rem 0 !important;
            }

            .mission-card,
            .base-card,
            .team-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .stats-section {
                background: none !important;
                color: #000 !important;
            }
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
    <!-- Hero Header -->
    <header class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>About Our Organization</h1>
                <p class="hero-subtitle">
                    Committed to creating positive change and empowering communities through sustainable development initiatives.
                </p>
            </div>
        </div>
    </header>

    <!-- Mission & Vision -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Our Foundation</span>
                <h2 class="section-title">Mission & Vision</h2>
                <p class="section-subtitle">Guided by our core principles to make a lasting impact</p>
            </div>

            <div class="mission-grid">
                <article class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p><?= nl2br(htmlspecialchars($about['vision'] ?? $org['vision'] ?? 'A world where every community thrives with dignity, opportunity, and sustainable development.')) ?></p>
                </article>

                <article class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-bullseye" aria-hidden="true"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p><?= nl2br(htmlspecialchars($about['mission'] ?? $org['mission'] ?? 'To empower marginalized communities through education, healthcare, and economic development programs that create lasting positive change.')) ?></p>
                </article>

                <article class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-heart" aria-hidden="true"></i>
                    </div>
                    <h3>Our Values</h3>
                    <p><?= nl2br(htmlspecialchars($about['core_values'] ?? $org['core_values'] ?? 'Integrity, Compassion, Empowerment, Sustainability, and Community Partnership guide everything we do.')) ?></p>
                </article>
            </div>
        </div>
    </section>

    <!-- Impact Stats -->
    <section class="section stats-section" aria-label="Impact Statistics">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-number"><?= displayStat($about['year_established'], '5+') ?></div>
                    <div class="stat-label">Years of Service</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?= displayStat($about['lives_impacted'], '1,500+') ?></div>
                    <div class="stat-label">Lives Impacted</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="stat-number"><?= displayStat($about['communities_served'], '12+') ?></div>
                    <div class="stat-label">Communities Served</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon" aria-hidden="true"><i class="fas fa-project-diagram"></i></div>
                    <div class="stat-number"><?= displayStat($about['active_programs'], '8') ?></div>
                    <div class="stat-label">Active Programmes</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story -->
    <?php if(!empty($about['establishment_story']) || !empty($org['history'])): ?>
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Our Journey</span>
                <h2 class="section-title">Our Story</h2>
                <p class="section-subtitle">From humble beginnings to meaningful impact</p>
            </div>
            
            <article class="story-content">
                <p>
                    <?= nl2br(htmlspecialchars($about['establishment_story'] ?? $org['history'] ?? 'Founded with a vision to make a difference, our organization has grown through dedication and community partnership.')) ?>
                </p>
            </article>
        </div>
    </section>
    <?php endif; ?>

    <!-- Operational Bases -->
    <?php if(!empty($bases)): ?>
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Where We Work</span>
                <h2 class="section-title">Operational Bases</h2>
                <p class="section-subtitle">Serving communities across multiple locations</p>
            </div>

            <div class="bases-grid">
                <?php foreach($bases as $base): ?>
                <article class="base-card">
                    <h3><i class="fas fa-map-pin" aria-hidden="true"></i> <?= htmlspecialchars($base['location_name']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($base['description'])) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Leadership Team -->
    <?php if(!empty($leaders)): ?>
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Meet Our Team</span>
                <h2 class="section-title">Leadership Team</h2>
                <p class="section-subtitle">Dedicated individuals driving our mission forward</p>
            </div>

            <div class="team-grid">
                <?php foreach($leaders as $leader): ?>
                <article class="team-card">
                    <div class="team-image-wrapper">
                        <img src="../uploads/<?= htmlspecialchars($leader['image']) ?>" 
                             alt="<?= htmlspecialchars($leader['name']) ?>" 
                             class="team-image"
                             loading="lazy"
                             width="280"
                             height="280">
                    </div>
                    <div class="team-content">
                        <h3 class="team-name"><?= htmlspecialchars($leader['name']) ?></h3>
                        <p class="team-role"><?= htmlspecialchars($leader['position']) ?></p>
                        <?php if(!empty($leader['qualification'])): ?>
                            <p class="team-qualification">
                                <?= htmlspecialchars($leader['qualification']) ?>
                            </p>
                        <?php endif; ?>
                        <p class="team-bio">
                            <?= nl2br(htmlspecialchars(substr($leader['background'] ?? 'Dedicated professional committed to community development and sustainable impact.', 0, 120))) ?><?= strlen($leader['background'] ?? '') > 120 ? '...' : '' ?>
                        </p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <?php include "partials/footer.php"; ?> 
</body>
</html>