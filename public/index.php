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
    <meta name="theme-color" content="#2b6cb0">
    <meta name="color-scheme" content="light dark">
    <title>NGO - Empowering People with Disabilities</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* === ENHANCED DARK MODE VARIABLES === */
        :root {
            /* Light Theme Colors */
            --primary: #2b6cb0;
            --primary-dark: #2c5282;
            --primary-light: #bee3f8;
            --accent: #38b2ac;
            --accent-dark: #319795;
            --success: #38a169;
            --warning: #d69e2e;
            --error: #e53e3e;
            
            /* Light Theme Backgrounds */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-card: #ffffff;
            --bg-overlay: rgba(255, 255, 255, 0.95);
            
            /* Light Theme Text */
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-tertiary: #718096;
            --text-muted: #a0aec0;
            
            /* Light Theme Borders */
            --border-primary: #e2e8f0;
            --border-secondary: #cbd5e0;
            --border-accent: #2b6cb0;
            
            /* Light Theme Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            /* Animation */
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
            
            /* Layout */
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
        }

        /* === ENHANCED DARK THEME === */
        [data-theme="dark"] {
            /* Dark Theme Colors */
            --primary: #63b3ed;
            --primary-dark: #4299e1;
            --primary-light: #bee3f8;
            --accent: #4fd1c7;
            --accent-dark: #38b2ac;
            --success: #68d391;
            --warning: #faf089;
            --error: #fc8181;
            
            /* Dark Theme Backgrounds - Enhanced Depth */
            --bg-primary: #0f1419;
            --bg-secondary: #1a202c;
            --bg-tertiary: #2d3748;
            --bg-card: #1e2736;
            --bg-overlay: rgba(15, 20, 25, 0.95);
            
            /* Dark Theme Text - Better Contrast */
            --text-primary: #f7fafc;
            --text-secondary: #e2e8f0;
            --text-tertiary: #cbd5e0;
            --text-muted: #a0aec0;
            
            /* Dark Theme Borders */
            --border-primary: #2d3748;
            --border-secondary: #4a5568;
            --border-accent: #63b3ed;
            
            /* Dark Theme Shadows - Subtle Glow */
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.4);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.6), 0 10px 10px -5px rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color var(--transition-normal), color var(--transition-normal);
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
            transition: var(--transition-normal);
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
            transition: var(--transition-normal);
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

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            transition: color var(--transition-normal);
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            transition: color var(--transition-normal);
        }

        .mission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .mission-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-primary);
            overflow: hidden;
            transition: var(--transition-normal);
        }

        .mission-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .panel-header {
            width: 100%;
            padding: 2rem;
            background: var(--bg-card);
            border: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            transition: background var(--transition-normal);
        }

        .panel-header:hover {
            background: var(--bg-tertiary);
        }

        .panel-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: var(--radius-md);
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
            color: var(--text-primary);
            transition: color var(--transition-normal);
        }

        .panel-title p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            transition: color var(--transition-normal);
        }

        .toggle-icon {
            color: var(--text-secondary);
            transition: transform var(--transition-normal), color var(--transition-normal);
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
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
            transition: color var(--transition-normal);
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
            color: var(--text-secondary);
            animation: fadeInUp 0.6s ease;
            transition: color var(--transition-normal);
        }

        .objective-item i {
            color: var(--success);
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .no-data {
            color: var(--text-secondary);
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
            background: var(--bg-secondary);
        }

        .programmes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .programme-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-primary);
            transition: var(--transition-normal);
            opacity: 0;
            transform: translateY(30px);
        }

        .programme-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .programme-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
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
            color: var(--text-primary);
            font-size: 1.25rem;
            transition: color var(--transition-normal);
        }

        .programme-content p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            transition: color var(--transition-normal);
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
            border: 2px solid var(--border-primary);
            background: var(--bg-card);
            color: var(--text-secondary);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition-normal);
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
            color: var(--text-secondary);
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
            background: var(--bg-card);
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .team-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-primary);
            transition: var(--transition-normal);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
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
            color: var(--text-primary);
            transition: color var(--transition-normal);
        }

        .position {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .qualification {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            transition: color var(--transition-normal);
        }

        .background {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            transition: color var(--transition-normal);
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
            background: var(--bg-secondary);
        }

        .donation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .donation-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-primary);
            transition: var(--transition-normal);
        }

        .donation-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .donation-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .donation-content h3 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
            transition: color var(--transition-normal);
        }

        .donation-content p {
            margin: 0.5rem 0;
            color: var(--text-secondary);
            transition: color var(--transition-normal);
        }

        .qr-code {
            margin: 1rem 0;
        }

        .qr-code img {
            max-width: 150px;
            border-radius: var(--radius-sm);
        }

        .instructions {
            background: var(--bg-tertiary);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 1rem;
            text-align: left;
        }

        .instructions p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
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
            background: var(--bg-card);
            margin: 2rem auto;
            max-width: 800px;
            width: 90%;
            border-radius: var(--radius-lg);
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
            color: var(--text-primary);
            margin-bottom: 1rem;
            transition: color var(--transition-normal);
        }

        .modal-description {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 2rem;
            transition: color var(--transition-normal);
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
            transition: var(--transition-normal);
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
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 2px solid var(--border-primary);
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-secondary);
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
            transition: var(--transition-normal);
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
            background: var(--error);
            color: white;
        }

        .donate-btn:hover {
            background: #c53030;
        }

        .close-modal-btn {
            background: var(--text-secondary);
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

        /* Theme Toggle Styles */
        .theme-toggle {
            background: var(--bg-tertiary);
            border: 2px solid var(--border-primary);
            border-radius: var(--radius-xl);
            width: 60px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
            position: relative;
            transition: all var(--transition-normal);
            overflow: hidden;
        }

        .theme-toggle:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-md);
            transform: scale(1.05);
        }

        .theme-toggle::before {
            content: '';
            position: absolute;
            left: 4px;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #f6ad55, #ed8936);
            border-radius: 50%;
            transition: all var(--transition-normal);
            z-index: 1;
        }

        .theme-toggle::after {
            content: '☀️';
            font-size: 0.9rem;
            position: absolute;
            right: 8px;
            transition: all var(--transition-normal);
            filter: grayscale(0.3);
        }

        [data-theme="dark"] .theme-toggle::before {
            left: calc(100% - 28px);
            background: linear-gradient(135deg, #cbd5e0, #a0aec0);
            box-shadow: 0 0 10px rgba(203, 213, 224, 0.5);
        }

        [data-theme="dark"] .theme-toggle::after {
            content: '🌙';
            left: 8px;
            right: auto;
            filter: brightness(1.2);
        }

        .theme-toggle .toggle-icons {
            display: flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
            pointer-events: none;
        }

        .theme-toggle .toggle-icons span {
            font-size: 0.8rem;
            opacity: 0.7;
            transition: opacity var(--transition-normal);
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

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-secondary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
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

    <!-- THEME TOGGLE BUTTON -->
    <div class="theme-toggle-container" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 1000;">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
            <div class="toggle-icons">
                <span>☀️</span>
                <span>🌙</span>
            </div>
        </button>
    </div>

    <script>
    // Enhanced Theme Manager
    class ThemeManager {
        constructor() {
            this.themeToggle = document.getElementById('themeToggle');
            this.currentTheme = localStorage.getItem('theme') || this.getSystemTheme();
            this.init();
        }
        
        init() {
            this.applyTheme(this.currentTheme);
            this.setupEventListeners();
        }
        
        setupEventListeners() {
            this.themeToggle.addEventListener('click', () => this.toggleTheme());
            
            // System theme change listener
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
        
        toggleTheme() {
            this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.applyTheme(this.currentTheme);
            this.saveTheme();
        }
        
        applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            
            // Update meta theme-color for mobile browsers
            this.updateMetaThemeColor(theme);
        }
        
        updateMetaThemeColor(theme) {
            let metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.name = 'theme-color';
                document.head.appendChild(metaThemeColor);
            }
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#0f1419' : '#2b6cb0');
        }
        
        saveTheme() {
            localStorage.setItem('theme', this.currentTheme);
        }
        
        getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
    }

    // Initialize theme manager
    document.addEventListener('DOMContentLoaded', function() {
        const themeManager = new ThemeManager();

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

    // Handle reduced motion preferences
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.style.setProperty('--transition-fast', 'none');
        document.documentElement.style.setProperty('--transition-normal', 'none');
        document.documentElement.style.setProperty('--transition-slow', 'none');
    }
    </script>
    <?php include "partials/footer.php"; ?>
</body>
</html>