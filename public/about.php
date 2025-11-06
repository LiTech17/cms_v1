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
    <title>About Us | <?= htmlspecialchars($org['name'] ?? 'NGO Name') ?></title>
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--bg);
            overflow-x: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Hero Header */
        .hero {
            position: relative;
            background: var(--gradient);
            color: white;
            padding: 8rem 0 6rem;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        <?php if(!empty($about['hero_image'])): ?>
        .hero {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2) 0%, rgba(6, 182, 212, 0.9) 100%),
                        url('../uploads/<?= htmlspecialchars($about['hero_image']) ?>');
            background-size: cover;
            background-position: center;
        }
        <?php endif; ?>

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2vw, 1.3rem);
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        /* Section Styles */
        .section {
            padding: 5rem 0;
        }

        .section-alt {
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
            animation: fadeInUp 0.6s ease-out;
        }

        .section-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--gradient);
            color: white;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            color: var(--text);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 650px;
            margin: 0 auto;
        }

        /* Mission Cards */
        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
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
            background: var(--gradient);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .mission-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .mission-card p {
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Stats Section */
        .stats-section {
            background: var(--gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -250px;
            right: -150px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Story Section */
        .story-content {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            font-size: 1.15rem;
            line-height: 1.9;
            color: var(--text);
            border-left: 4px solid var(--primary);
        }

        /* Team Grid */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .team-image-wrapper {
            position: relative;
            overflow: hidden;
            height: 280px;
            background: var(--border);
        }

        .team-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .team-card:hover .team-image {
            transform: scale(1.05);
        }

        .team-content {
            padding: 1.5rem;
        }

        .team-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .team-role {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .team-qualification {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            font-style: italic;
        }

        .team-bio {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Bases Section */
        .bases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .base-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .base-card:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow-lg);
        }

        .base-card h3 {
            color: var(--primary);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .base-card h3 i {
            font-size: 1.1rem;
        }

        .base-card p {
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            text-align: center;
            margin-bottom: 3rem;
        }

        .footer-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
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

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }

            .hero {
                padding: 5rem 0 4rem;
            }

            .section {
                padding: 3rem 0;
            }

            .section-header {
                margin-bottom: 2.5rem;
            }

            .mission-grid,
            .stats-grid,
            .team-grid,
            .bases-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .story-content {
                padding: 2rem 1.5rem;
            }

            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
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
                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p><?= nl2br(htmlspecialchars($about['vision'] ?? $org['vision'] ?? 'A world where every community thrives with dignity, opportunity, and sustainable development.')) ?></p>
                </div>

                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p><?= nl2br(htmlspecialchars($about['mission'] ?? $org['mission'] ?? 'To empower marginalized communities through education, healthcare, and economic development programs that create lasting positive change.')) ?></p>
                </div>

                <div class="mission-card">
                    <div class="mission-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Our Values</h3>
                    <p><?= nl2br(htmlspecialchars($about['core_values'] ?? $org['core_values'] ?? 'Integrity, Compassion, Empowerment, Sustainability, and Community Partnership guide everything we do.')) ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Impact Stats -->
    <section class="section stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-number"><?= displayStat($about['year_established'], '5+') ?></div>
                    <div class="stat-label">Years of Service</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-number"><?= displayStat($about['lives_impacted'], '1,500+') ?></div>
                    <div class="stat-label">Lives Impacted</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="stat-number"><?= displayStat($about['communities_served'], '12+') ?></div>
                    <div class="stat-label">Communities Served</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-project-diagram"></i></div>
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
            
            <div class="story-content">
                <p>
                    <?= nl2br(htmlspecialchars($about['establishment_story'] ?? $org['history'] ?? 'Founded with a vision to make a difference, our organization has grown through dedication and community partnership.')) ?>
                </p>
            </div>
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
                <div class="base-card">
                    <h3><i class="fas fa-map-pin"></i> <?= htmlspecialchars($base['location_name']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($base['description'])) ?></p>
                </div>
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
                <div class="team-card">
                    <div class="team-image-wrapper">
                        <img src="../uploads/<?= htmlspecialchars($leader['image']) ?>" 
                             alt="<?= htmlspecialchars($leader['name']) ?>" 
                             class="team-image">
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
                            <?= nl2br(htmlspecialchars(substr($leader['background'] ?? 'Dedicated professional committed to community development and sustainable impact.', 0, 120))) ?>...
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <h3>Join Us in Making a Difference</h3>
                <p>Together, we can create lasting change in communities that need it most.</p>
            </div>
            
            <div class="footer-links">
                <a href="contact.php">Get Involved</a>
                <a href="donate.php">Donate</a>
                <a href="volunteer.php">Volunteer</a>
                <a href="contact.php">Contact Us</a>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($org['name'] ?? 'NGO Name') ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>