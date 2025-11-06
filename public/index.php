<?php
require_once "../config/database.php";
include "partials/header.php";

// Fetch homepage hero carousel
$heroes = $pdo->query("SELECT * FROM home_hero ORDER BY id ASC")->fetchAll();

// Fetch organization info
$org = $pdo->query("SELECT * FROM organization_info LIMIT 1")->fetch();

// Fetch featured programmes with their images
$featured_programmes = $pdo->query("
    SELECT p.*, pm.file_name as programme_image 
    FROM programmes p 
    LEFT JOIN programme_media pm ON p.id = pm.programme_id 
    WHERE p.is_featured = 1 
    GROUP BY p.id 
    ORDER BY p.id DESC 
    LIMIT 3
")->fetchAll();

// Fetch all programmes for the filter section with images
$all_programmes = $pdo->query("
    SELECT p.*, pm.file_name as programme_image 
    FROM programmes p 
    LEFT JOIN programme_media pm ON p.id = pm.programme_id 
    GROUP BY p.id 
    ORDER BY p.is_featured DESC, p.id DESC
")->fetchAll();

// Fetch leadership team for about section
$leaders = $pdo->query("SELECT * FROM leadership_team ORDER BY id DESC LIMIT 4")->fetchAll();

// Fetch donation methods
$donation_methods = $pdo->query("SELECT * FROM donation_methods ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO - Empowering People with Disabilities</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --accent: #38b2ac;
            --danger: #e53e3e;
            --success: #38a169;
            --warning: #d69e2e;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #2d3748;
            --text-light: #718096;
            --text-muted: #a0aec0;
            --border: #e2e8f0;
            --shadow: rgba(0, 0, 0, 0.08);
            --shadow-md: rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

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
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Enhanced Hero Carousel */
        .hero-carousel {
            position: relative;
            height: 80vh;
            min-height: 600px;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(43, 108, 176, 0.2) 0%, rgba(56, 178, 172, 0.6) 100%);
        }

        .slide-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 0 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .slide-caption {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 3;
        }

        .carousel-prev,
        .carousel-next {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .carousel-prev:hover,
        .carousel-next:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .carousel-indicators {
            position: absolute;
            bottom: 2rem;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            z-index: 3;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            background: transparent;
            cursor: pointer;
            transition: var(--transition);
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
        }

        /* Quick Stats Section */
        .quick-stats {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 4rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Enhanced Mission Section with Retractable Cards */
        .organization-info {
            padding: 5rem 0;
            background: var(--bg);
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: var(--transition);
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px var(--shadow-md);
        }

        .panel-header {
            width: 100%;
            padding: 2rem;
            background: var(--card-bg);
            border: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .panel-header:hover {
            background: #f7fafc;
        }

        .panel-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .panel-title {
            flex: 1;
            text-align: left;
        }

        .panel-title h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1.25rem;
            color: var(--text);
        }

        .panel-title p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .toggle-icon {
            color: var(--text-light);
            transition: transform 0.3s ease;
            font-size: 1.2rem;
        }

        .mission-card.active .toggle-icon {
            transform: rotate(45deg);
        }

        .panel-content {
            padding: 0 2rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.5s ease-in-out;
        }

        .mission-card.active .panel-content {
            padding: 0 2rem 2rem;
            max-height: 1000px;
        }

        .info-block {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.6s ease;
        }

        .info-block h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .info-block p {
            color: #4a5568;
            line-height: 1.6;
            margin: 0;
        }

        .objectives-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .objective-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            color: #4a5568;
            animation: fadeInUp 0.6s ease;
        }

        .objective-item i {
            color: var(--success);
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .no-data {
            color: var(--text-light);
            font-style: italic;
            text-align: center;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-data i {
            font-size: 2rem;
            opacity: 0.5;
        }

        /* Enhanced Programmes Sections with Images */
        .featured-programmes,
        .all-programmes {
            padding: 5rem 0;
        }

        .all-programmes {
            background: var(--bg);
        }

        .programmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .programme-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px);
        }

        .programme-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .programme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px var(--shadow-md);
        }

        .programme-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .programme-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .programme-card:hover .programme-image img {
            transform: scale(1.05);
        }

        .featured-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--warning);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .programme-content {
            padding: 1.5rem;
        }

        .programme-content h3 {
            margin: 0 0 1rem 0;
            color: var(--text);
            font-size: 1.25rem;
        }

        .programme-content p {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .programme-actions {
            display: flex;
            gap: 0.75rem;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--border);
            background: var(--card-bg);
            color: #4a5568;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Empty States */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Section CTA */
        .section-cta {
            text-align: center;
        }

        /* Leadership Team */
        .leadership-team {
            padding: 5rem 0;
            background: var(--card-bg);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .team-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px var(--shadow-md);
        }

        .team-image {
            height: 250px;
            overflow: hidden;
        }

        .team-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .team-content {
            padding: 1.5rem;
            text-align: center;
        }

        .team-content h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text);
        }

        .position {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .qualification {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .background {
            color: #4a5568;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Donation Methods */
        .donation-methods {
            padding: 5rem 0;
            background: var(--bg);
        }

        .donation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .donation-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px var(--shadow);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .donation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px var(--shadow-md);
        }

        .donation-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .donation-content h3 {
            margin: 0 0 1rem 0;
            color: var(--text);
        }

        .donation-content p {
            margin: 0.5rem 0;
            color: #4a5568;
        }

        .qr-code {
            margin: 1rem 0;
        }

        .qr-code img {
            max-width: 150px;
            border-radius: var(--radius-sm);
        }

        .instructions {
            background: #f7fafc;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 1rem;
            text-align: left;
        }

        .instructions p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Enhanced Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background: var(--card-bg);
            margin: 2rem auto;
            max-width: 800px;
            width: 90%;
            border-radius: var(--radius);
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
            transition: background 0.3s ease;
        }

        .close-modal:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .modal-image {
            height: 300px;
            overflow: hidden;
        }

        .modal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-body h3 {
            font-size: 1.75rem;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .modal-description {
            color: #4a5568;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.3);
        }

        .btn-secondary {
            background: var(--card-bg);
            color: #4a5568;
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .read-more-btn,
        .donate-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: var(--transition);
            text-decoration: none;
        }

        .read-more-btn {
            background: var(--primary);
            color: white;
        }

        .read-more-btn:hover {
            background: var(--primary-dark);
        }

        .donate-btn {
            background: var(--danger);
            color: white;
        }

        .donate-btn:hover {
            background: #c53030;
        }

        .close-modal-btn {
            background: var(--text-light);
            color: white;
        }

        .close-modal-btn:hover {
            background: #4a5568;
        }

        /* Animation Classes */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .slide-caption {
                font-size: 2.5rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .mission-grid {
                grid-template-columns: 1fr;
            }
            
            .programmes-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .donation-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .carousel-controls {
                padding: 0 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .modal-content {
                margin: 1rem;
                width: calc(100% - 2rem);
            }
            
            .container {
                padding: 0 1rem;
            }

            .panel-header {
                padding: 1.5rem;
            }

            .panel-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-tabs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .programme-actions {
                flex-direction: column;
            }
            
            .modal-actions {
                flex-direction: column;
            }

            .section-title {
                font-size: 2rem;
            }

            .slide-caption {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- HERO SECTION -->
    <section class="hero-carousel">
        <?php foreach($heroes as $i => $h): ?>
            <div class="slide<?= $i === 0 ? ' active' : '' ?>" style="background-image: url('../uploads/<?= htmlspecialchars($h['image']) ?>')">
                <div class="slide-overlay"></div>
                <div class="slide-content">
                    <?php if($h['caption']): ?>
                        <h1 class="slide-caption"><?= htmlspecialchars($h['caption']) ?></h1>
                    <?php endif; ?>
                    <div class="hero-actions">
                        <a href="#programmes" class="btn btn-primary">
                            <i class="fas fa-play-circle"></i> Explore Our Work
                        </a>
                        <a href="#about" class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Learn More
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if(count($heroes) > 1): ?>
            <div class="carousel-controls">
                <button class="carousel-prev" aria-label="Previous slide">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="carousel-next" aria-label="Next slide">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="carousel-indicators">
                <?php foreach($heroes as $i => $h): ?>
                    <button class="indicator<?= $i === 0 ? ' active' : '' ?>" data-slide="<?= $i ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- QUICK STATS SECTION -->
    <section class="quick-stats reveal">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" data-count="1500">0</h3>
                        <p class="stat-label">People Helped Annually</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" data-count="12">0</h3>
                        <p class="stat-label">Communities Served</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" data-count="8">0</h3>
                        <p class="stat-label">Active Programmes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number" data-count="5">0</h3>
                        <p class="stat-label">Years of Service</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MISSION SECTION -->
    <section class="organization-info reveal" id="about">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">CARING FOR PEOPLE WITH DISABILITIES</h2>
                <p class="section-subtitle">Committed to creating an inclusive society where everyone thrives with dignity and opportunity</p>
            </div>
            
            <div class="mission-grid">
                <!-- Panel 1 -->
                <div class="mission-card retractable active">
                    <button class="panel-header">
                        <div class="panel-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="panel-title">
                            <h3>Our Vision & Mission</h3>
                            <p>Our guiding principles and aspirations</p>
                        </div>
                        <span class="toggle-icon">
                            <i class="fas fa-minus"></i>
                        </span>
                    </button>
                    <div class="panel-content">
                        <?php if(!empty($org['vision'])): ?>
                            <div class="info-block">
                                <h4><i class="fas fa-eye"></i> Our Vision</h4>
                                <p><?= nl2br(htmlspecialchars($org['vision'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($org['mission'])): ?>
                            <div class="info-block">
                                <h4><i class="fas fa-flag"></i> Our Mission</h4>
                                <p><?= nl2br(htmlspecialchars($org['mission'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($org['goals'])): ?>
                            <div class="info-block">
                                <h4><i class="fas fa-trophy"></i> Our Goals</h4>
                                <p><?= nl2br(htmlspecialchars($org['goals'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel 2 -->
                <div class="mission-card retractable">
                    <button class="panel-header">
                        <div class="panel-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <div class="panel-title">
                            <h3>Our Reach</h3>
                            <p>Where we make an impact</p>
                        </div>
                        <span class="toggle-icon">
                            <i class="fas fa-plus"></i>
                        </span>
                    </button>
                    <div class="panel-content">
                        <?php if(!empty($org['geographical_location'])): ?>
                            <div class="info-block">
                                <h4><i class="fas fa-globe-africa"></i> Geographical Coverage</h4>
                                <p><?= nl2br(htmlspecialchars($org['geographical_location'])) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-map"></i>
                                <p>Geographical information coming soon...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel 3 -->
                <div class="mission-card retractable">
                    <button class="panel-header">
                        <div class="panel-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="panel-title">
                            <h3>Our Objectives</h3>
                            <p>Key focus areas and targets</p>
                        </div>
                        <span class="toggle-icon">
                            <i class="fas fa-plus"></i>
                        </span>
                    </button>
                    <div class="panel-content">
                        <?php if(!empty($org['objectives'])): ?>
                            <div class="objectives-list">
                                <?php 
                                $objectives = array_filter(array_map('trim', explode(",", $org['objectives'])));
                                foreach($objectives as $obj): 
                                    if(!empty($obj)):
                                ?>
                                    <div class="objective-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= htmlspecialchars($obj) ?></span>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-bullseye"></i>
                                <p>Objectives information coming soon...</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED PROGRAMMES -->
    <section class="featured-programmes reveal">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Programmes</h2>
                <p class="section-subtitle">Our flagship initiatives making a tangible difference in the community</p>
            </div>

            <div class="programmes-grid">
                <?php foreach($featured_programmes as $p): 
                    $thumb = !empty($p['programme_image']) ? "../uploads/" . htmlspecialchars($p['programme_image']) : "../public/img/default-programme.jpg";
                ?>
                    <div class="programme-card fade-up">
                        <div class="programme-image">
                            <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
                            <div class="featured-badge">
                                <i class="fas fa-star"></i> Featured
                            </div>
                        </div>
                        <div class="programme-content">
                            <h3><?= htmlspecialchars($p['title']) ?></h3>
                            <p><?= nl2br(htmlspecialchars(substr($p['introduction'], 0, 150))) ?>...</p>
                            <button class="read-more-btn" 
                                    data-title="<?= htmlspecialchars($p['title']) ?>"
                                    data-description="<?= htmlspecialchars($p['introduction']) ?>"
                                    data-thumb="<?= $thumb ?>">
                                <i class="fas fa-book-open"></i> Read More
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($featured_programmes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-project-diagram"></i>
                        <h3>No Featured Programmes</h3>
                        <p>Check back soon for updates on our featured initiatives.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($featured_programmes)): ?>
                <div class="section-cta">
                    <a href="programmes.php" class="btn btn-outline">
                        <i class="fas fa-arrow-right"></i> View All Programmes
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ALL PROGRAMMES SECTION -->
    <section class="all-programmes reveal" id="programmes">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Programmes & Impact</h2>
                <p class="section-subtitle">Discover the full range of our initiatives and their community impact</p>
            </div>

            <!-- FILTER BUTTONS -->
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th-large"></i> All Programmes
                </button>
                <button class="filter-btn" data-filter="featured">
                    <i class="fas fa-star"></i> Featured
                </button>
                <button class="filter-btn" data-filter="latest">
                    <i class="fas fa-clock"></i> Latest
                </button>
            </div>

            <div class="programmes-grid" id="programmeContainer">
                <?php foreach($all_programmes as $p): 
                    $is_featured = $p['is_featured'] == 1 ? 'featured' : 'latest';
                    $thumb = !empty($p['programme_image']) ? "../uploads/" . htmlspecialchars($p['programme_image']) : "../public/img/default-programme.jpg";
                ?>
                    <div class="programme-card fade-up <?= $is_featured ?>" data-category="<?= $is_featured ?>">
                        <div class="programme-image">
                            <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
                            <?php if($p['is_featured'] == 1): ?>
                                <div class="featured-badge">
                                    <i class="fas fa-star"></i> Featured
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="programme-content">
                            <h3><?= htmlspecialchars($p['title']) ?></h3>
                            <p><?= nl2br(htmlspecialchars(substr($p['introduction'], 0, 120))) ?>...</p>
                            <div class="programme-actions">
                                <button class="read-more-btn" 
                                        data-title="<?= htmlspecialchars($p['title']) ?>"
                                        data-description="<?= htmlspecialchars($p['introduction']) ?>"
                                        data-thumb="<?= $thumb ?>">
                                    <i class="fas fa-book-open"></i> Read More
                                </button>
                                <a href="donate.php" class="donate-btn">
                                    <i class="fas fa-heart"></i> Support
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($all_programmes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-project-diagram"></i>
                        <h3>No Programmes Available</h3>
                        <p>Our programmes information is being updated. Please check back soon.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- LEADERSHIP TEAM SECTION -->
    <?php if(!empty($leaders)): ?>
    <section class="leadership-team reveal">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Leadership Team</h2>
                <p class="section-subtitle">Meet the dedicated professionals driving our mission forward</p>
            </div>
            
            <div class="team-grid">
                <?php foreach($leaders as $leader): ?>
                    <div class="team-card fade-up">
                        <div class="team-image">
                            <img src="../uploads/<?= htmlspecialchars($leader['image']) ?>" alt="<?= htmlspecialchars($leader['name']) ?>" loading="lazy">
                        </div>
                        <div class="team-content">
                            <h3><?= htmlspecialchars($leader['name']) ?></h3>
                            <p class="position"><?= htmlspecialchars($leader['position']) ?></p>
                            <?php if(!empty($leader['qualification'])): ?>
                                <p class="qualification"><?= htmlspecialchars($leader['qualification']) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($leader['background'])): ?>
                                <p class="background"><?= nl2br(htmlspecialchars(substr($leader['background'], 0, 100))) ?>...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-cta">
                <a href="about.php#team" class="btn btn-outline">
                    <i class="fas fa-users"></i> Meet Full Team
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CALL TO ACTION SECTION -->
    <section class="cta-section reveal">
        <div class="container">
            <div class="cta-content">
                <h2>Join Us in Making a Difference</h2>
                <p>Your support can transform lives and create lasting change for people with disabilities in our communities.</p>
                <div class="cta-buttons">
                    <a href="donate.php" class="btn btn-primary">
                        <i class="fas fa-donate"></i> Donate Now
                    </a>
                    <a href="volunteer.php" class="btn btn-secondary">
                        <i class="fas fa-hands-helping"></i> Volunteer
                    </a>
                    <a href="contact.php" class="btn btn-outline">
                        <i class="fas fa-envelope"></i> Get In Touch
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- QUICK DONATION METHODS -->
    <?php if(!empty($donation_methods)): ?>
    <section class="donation-methods reveal">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Support Our Cause</h2>
                <p class="section-subtitle">Multiple convenient ways to make your donation</p>
            </div>
            
            <div class="donation-grid">
                <?php foreach($donation_methods as $method): ?>
                    <div class="donation-card fade-up">
                        <div class="donation-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="donation-content">
                            <h3><?= htmlspecialchars($method['method_name']) ?></h3>
                            <?php if(!empty($method['account_name'])): ?>
                                <p><strong>Account:</strong> <?= htmlspecialchars($method['account_name']) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($method['account_number'])): ?>
                                <p><strong>Number:</strong> <?= htmlspecialchars($method['account_number']) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($method['merchant_code'])): ?>
                                <p><strong>Code:</strong> <?= htmlspecialchars($method['merchant_code']) ?></p>
                            <?php endif; ?>
                            <?php if(!empty($method['qr_image'])): ?>
                                <div class="qr-code">
                                    <img src="../uploads/<?= htmlspecialchars($method['qr_image']) ?>" alt="QR Code">
                                </div>
                            <?php endif; ?>
                            <?php if(!empty($method['instructions'])): ?>
                                <div class="instructions">
                                    <p><?= nl2br(htmlspecialchars($method['instructions'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-cta">
                <a href="donate.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> More Donation Options
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MODAL POPUP -->
    <div id="programmeModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" aria-label="Close modal">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-image">
                <img id="modalImage" src="" alt="">
            </div>
            <div class="modal-body">
                <h3 id="modalTitle"></h3>
                <div id="modalDescription" class="modal-description"></div>
                <div class="modal-actions">
                    <a href="donate.php" class="btn btn-primary">
                        <i class="fas fa-heart"></i> Support This Programme
                    </a>
                    <button class="btn btn-secondary close-modal-btn">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hero Carousel
        const carousel = {
            slides: document.querySelectorAll('.hero-carousel .slide'),
            indicators: document.querySelectorAll('.indicator'),
            currentSlide: 0,
            interval: null,
            
            init() {
                if (this.slides.length <= 1) return;
                
                this.startAutoPlay();
                
                document.querySelector('.carousel-prev')?.addEventListener('click', () => this.prevSlide());
                document.querySelector('.carousel-next')?.addEventListener('click', () => this.nextSlide());
                
                this.indicators.forEach((indicator, index) => {
                    indicator.addEventListener('click', () => this.goToSlide(index));
                });
                
                const carouselEl = document.querySelector('.hero-carousel');
                carouselEl.addEventListener('mouseenter', () => this.stopAutoPlay());
                carouselEl.addEventListener('mouseleave', () => this.startAutoPlay());
            },
            
            startAutoPlay() {
                this.interval = setInterval(() => this.nextSlide(), 5000);
            },
            
            stopAutoPlay() {
                if (this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
            },
            
            nextSlide() {
                this.goToSlide((this.currentSlide + 1) % this.slides.length);
            },
            
            prevSlide() {
                this.goToSlide((this.currentSlide - 1 + this.slides.length) % this.slides.length);
            },
            
            goToSlide(index) {
                this.slides[this.currentSlide].classList.remove('active');
                this.indicators[this.currentSlide].classList.remove('active');
                
                this.currentSlide = index;
                
                this.slides[this.currentSlide].classList.add('active');
                this.indicators[this.currentSlide].classList.add('active');
            }
        };
        
        carousel.init();

        // Enhanced Retractable Panels
        const panels = document.querySelectorAll('.mission-card');
        panels.forEach(panel => {
            const header = panel.querySelector('.panel-header');
            const content = panel.querySelector('.panel-content');
            const toggleIcon = panel.querySelector('.toggle-icon i');
            
            header.addEventListener('click', () => {
                const isActive = panel.classList.contains('active');
                
                // Close all panels
                panels.forEach(p => {
                    p.classList.remove('active');
                    p.querySelector('.toggle-icon i').className = 'fas fa-plus';
                });
                
                // Open clicked panel if it wasn't active
                if (!isActive) {
                    panel.classList.add('active');
                    toggleIcon.className = 'fas fa-minus';
                }
            });
        });

        // Programme Filtering
        const filterButtons = document.querySelectorAll('.filter-btn');
        const programmeCards = document.querySelectorAll('.programme-card[data-category]');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;
                
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                programmeCards.forEach(card => {
                    if (filter === 'all' || card.dataset.category === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Modal Functionality
        const modal = document.getElementById('programmeModal');
        const readMoreButtons = document.querySelectorAll('.read-more-btn');
        const closeModalButtons = document.querySelectorAll('.close-modal, .close-modal-btn');
        
        readMoreButtons.forEach(button => {
            button.addEventListener('click', () => {
                const title = button.dataset.title;
                const description = button.dataset.description;
                const thumb = button.dataset.thumb;
                
                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalDescription').innerHTML = description.replace(/\n/g, '<br>');
                document.getElementById('modalImage').src = thumb;
                document.getElementById('modalImage').alt = title;
                
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        });
        
        closeModalButtons.forEach(button => {
            button.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Animated Number Counting
        const animateNumbers = () => {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.count);
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        };

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    
                    if (entry.target.classList.contains('quick-stats')) {
                        animateNumbers();
                    }
                    
                    if (entry.target.classList.contains('programmes-grid') || entry.target.classList.contains('team-grid')) {
                        const children = entry.target.querySelectorAll('.fade-up');
                        children.forEach((child, index) => {
                            setTimeout(() => {
                                child.classList.add('visible');
                            }, index * 100);
                        });
                    }
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.reveal, .programmes-grid, .team-grid, .donation-grid').forEach(el => {
            observer.observe(el);
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading state to images
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
            });
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        });
    });
    </script>
    <?php include "partials/footer.php"; ?>s
</body>
</html>