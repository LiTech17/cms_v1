<?php
// Maintained paths with explicit directory resolution 
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/auth.php';

// Path for analytics file, maintained from the original full code
require_once "../core/analytics.php"; // <--- ADDED: Include analytics core

// Fetch site configuration
$site = $pdo->query("SELECT * FROM site_settings LIMIT 1")->fetch();

// --- START ANALYTICS PAGE VIEW LOGGING ---
// Note: This relies on a session being active for session_id to work.
// Ensure session_start() is called somewhere globally if it hasn't been already.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_url = $_SERVER['REQUEST_URI'] ?? '/unknown';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Log the page view event for the public site
log_activity(
    'page_view',             // Event Type: Public page view
    $current_url,           // URL of the current page
    0,                       // user_id is 0 or NULL for public unauthenticated users
    session_id(),            // Use the PHP session ID to track unique visitors (Head Count)
    'Public page view'       // Optional: Detailed description
);
// --- END ANALYTICS PAGE VIEW LOGGING ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#2b6cb0">
<meta name="color-scheme" content="light dark">
<title><?= htmlspecialchars($site['site_title'] ?? 'NGO') ?></title>
<link rel="icon" href="../uploads/<?= htmlspecialchars($site['favicon'] ?? 'favicon.ico') ?>">
<link rel="stylesheet" href="css/style.css">
<style>
/* =====================================
   🌗 ENHANCED THEME SYSTEM (LIGHT + DARK)
===================================== */

/* === LIGHT THEME VARIABLES === */
:root {
  /* Core Colors */
  --primary: #2b6cb0;
  --primary-dark: #2c5282;
  --primary-light: #bee3f8;
  --accent: #38b2ac;
  --accent-dark: #319795;
  --success: #38a169;
  --warning: #d69e2e;
  --error: #e53e3e;

  /* Backgrounds */
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-tertiary: #f1f5f9;
  --bg-card: #ffffff;
  --bg-card-rgb: 255, 255, 255; /* ✅ Added for rgba() use */
  --bg-overlay: rgba(255, 255, 255, 0.95);

  /* Text Colors */
  --text-primary: #1a202c;
  --text-secondary: #4a5568;
  --text-tertiary: #718096;
  --text-muted: #a0aec0;

  /* Borders */
  --border-primary: #e2e8f0;
  --border-secondary: #cbd5e0;
  --border-accent: #2b6cb0;

  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.15);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.2);

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

/* === DARK THEME VARIABLES === */
[data-theme="dark"] {
  --primary: #63b3ed;
  --primary-dark: #4299e1;
  --primary-light: #bee3f8;
  --accent: #4fd1c7;
  --accent-dark: #38b2ac;
  --success: #68d391;
  --warning: #faf089;
  --error: #fc8181;

  /* Dark Backgrounds */
  --bg-primary: #0f1419;
  --bg-secondary: #1a202c;
  --bg-tertiary: #2d3748;
  --bg-card: #1e2736;
  --bg-card-rgb: 30, 39, 54; /* ✅ Added for rgba() use */
  --bg-overlay: rgba(15, 20, 25, 0.95);

  /* Text */
  --text-primary: #f7fafc;
  --text-secondary: #e2e8f0;
  --text-tertiary: #cbd5e0;
  --text-muted: #a0aec0;

  /* Borders */
  --border-primary: #2d3748;
  --border-secondary: #4a5568;
  --border-accent: #63b3ed;

  /* Shadows */
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.4);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.5);
  --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.6);
}

/* =====================================
   🧭 BASE STYLES
===================================== */
html {
  scroll-behavior: smooth;
}

body {
  background: var(--bg-primary);
  color: var(--text-primary);
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  line-height: 1.6;
  margin: 0;
  transition: background-color var(--transition-normal), color var(--transition-normal);
  min-height: 100vh;
}

/* =====================================
   🧱 HEADER & NAVIGATION
===================================== */
header {
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(10px);
  background: rgba(var(--bg-card-rgb), 0.85);
  border-bottom: 1px solid var(--border-primary);
  box-shadow: var(--shadow-sm);
  transition: background var(--transition-normal), box-shadow var(--transition-normal);
}

nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
  gap: 2rem;
}

/* === Brand (Logo + Title) === */
.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.nav-brand img.nav-logo {
  height: 40px;
  width: auto;
  border-radius: 6px;
  position: relative;
  z-index: 2;
  transition: transform var(--transition-normal), filter var(--transition-normal);
}

[data-theme="dark"] .nav-brand img.nav-logo {
  filter: brightness(0.95) contrast(1.1);
}

.nav-brand span {
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-brand:hover img.nav-logo {
  transform: scale(1.05);
}

/* Hide text on very small screens */
@media (max-width: 480px) {
  .nav-brand span { display: none; }
}

/* === Menu === */
.nav-menu {
  display: flex;
  gap: 2rem;
  list-style: none;
  margin: 0;
  padding: 0;
  align-items: center;
}

.nav-menu li a {
  color: var(--text-secondary);
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  position: relative;
}

.nav-menu li a:hover {
  color: var(--primary);
  background: var(--bg-tertiary);
}

.nav-menu li a::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 50%;
  width: 0;
  height: 2px;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  transition: width var(--transition-normal);
  transform: translateX(-50%);
}

.nav-menu li a:hover::after {
  width: 80%;
}

/* === Actions === */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}



/* =====================================
   ☀️🌙 THEME TOGGLE
===================================== */
.theme-toggle {
  background: var(--bg-tertiary);
  border: 2px solid var(--border-primary);
  border-radius: var(--radius-xl);
  width: 60px;
  height: 32px;
  position: relative;
  cursor: pointer;
  transition: all var(--transition-normal);
  overflow: hidden;
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
}

[data-theme="dark"] .theme-toggle::before {
  left: calc(100% - 28px);
  background: linear-gradient(135deg, #cbd5e0, #a0aec0);
  box-shadow: 0 0 10px rgba(203, 213, 224, 0.5);
}

.theme-toggle:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-md);
  transform: scale(1.05);
}

/* =====================================
   🍔 HAMBURGER MENU (MOBILE)
===================================== */
.hamburger {
  display: none;
  flex-direction: column;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.hamburger div {
  width: 25px;
  height: 2px;
  background: var(--text-primary);
  margin: 3px 0;
  border-radius: 2px;
  transition: all var(--transition-normal);
}

.hamburger.active div:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
.hamburger.active div:nth-child(2) { opacity: 0; }
.hamburger.active div:nth-child(3) { transform: rotate(-45deg) translate(7px, -6px); }

/* =====================================
   📱 RESPONSIVE NAVIGATION
===================================== */
@media (max-width: 768px) {
  nav { flex-wrap: wrap; padding: 1rem; }
  .hamburger { display: flex; }

  .nav-menu {
    display: none;
    flex-direction: column;
    position: absolute;
    top: 100%;
    left: 0; right: 0;
    background: var(--bg-card);
    border-top: 1px solid var(--border-primary);
    box-shadow: var(--shadow-lg);
    padding: 1rem;
    gap: 0.5rem;
  }

  .nav-menu.active { display: flex; }
  .nav-menu li a { text-align: center; display: block; padding: 1rem; }
}

/* =====================================
   ♿ ACCESSIBILITY & ENHANCEMENTS
===================================== */
@media (prefers-reduced-motion: reduce) {
  * { transition: none !important; animation: none !important; }
}

:focus-visible {
  outline: 2px solid var(--primary);
  outline-offset: 2px;
}

::selection {
  background: var(--primary-light);
  color: var(--text-primary);
}

[data-theme="dark"] ::selection {
  background: var(--primary-dark);
}

/* Scrollbar */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--bg-secondary); }
::-webkit-scrollbar-thumb { background: var(--border-secondary); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }
</style>

</head>
<body>
<header>
  <nav>
    <a href="index.php" class="nav-brand">
  <?php if (!empty($site['logo'])): ?>
    <img src="../uploads/<?= htmlspecialchars($site['logo']) ?>" 
         alt="<?= htmlspecialchars($site['site_title'] ?? 'NGO Logo') ?>" 
         class="nav-logo">
  <?php endif; ?>
  <span><?= htmlspecialchars($site['site_title'] ?? 'NGO') ?></span>
</a>

    <ul class="nav-menu">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="programmes.php">Programmes</a></li>
      <li><a href="donate.php">Donate</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
    <div class="nav-actions">
      <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <div class="toggle-icons">
          <span>☀️</span>
          <span>🌙</span>
        </div>
      </button>
      <div class="hamburger" id="hamburger" aria-label="Toggle menu">
        <div></div><div></div><div></div>
      </div>
    </div>
  </nav>
</header>

<main>

<script>
// Enhanced Theme Manager with Advanced Features
class EnhancedThemeManager {
    constructor() {
        this.themeToggle = document.getElementById('themeToggle');
        this.hamburger = document.getElementById('hamburger');
        this.navMenu = document.querySelector('.nav-menu');
        this.currentTheme = this.getStoredTheme() || this.getSystemTheme();
        this.init();
    }
    
    init() {
        this.applyTheme(this.currentTheme);
        this.setupEventListeners();
        this.setupMobileMenu();
        this.setupPerformanceOptimizations();
    }
    
    setupEventListeners() {
        this.themeToggle.addEventListener('click', () => this.toggleTheme());
        
        // System theme change listener
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!this.hasStoredPreference()) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }
    
    setupMobileMenu() {
        if (this.hamburger && this.navMenu) {
            this.hamburger.addEventListener('click', () => {
                this.navMenu.classList.toggle('active');
                this.hamburger.classList.toggle('active');
            });
            
            // Close menu when clicking on links
            this.navMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    this.navMenu.classList.remove('active');
                    this.hamburger.classList.remove('active');
                });
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.navMenu.contains(e.target) && !this.hamburger.contains(e.target)) {
                    this.navMenu.classList.remove('active');
                    this.hamburger.classList.remove('active');
                }
            });
        }
    }
    
    toggleTheme() {
        // Add smooth transition
        document.documentElement.style.transition = 'all 0.3s ease';
        
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(this.currentTheme);
        this.saveTheme();
        
        // Add haptic feedback on supported devices
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // Reset transition after animation
        setTimeout(() => {
            document.documentElement.style.transition = '';
        }, 300);
    }
    
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        // Update meta theme-color for mobile browsers
        this.updateMetaThemeColor(theme);
        
        // Update favicon for theme (optional)
        this.updateFavicon(theme);
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
    
    updateFavicon(theme) {
        // Optional: Switch favicon based on theme
        const favicon = document.querySelector('link[rel="icon"]');
        if (favicon && theme === 'dark') {
            // You could have different favicons for dark/light modes
            // favicon.href = '/path/to/dark-favicon.ico';
        }
    }
    
    getStoredTheme() {
        return localStorage.getItem('theme');
    }
    
    saveTheme() {
        localStorage.setItem('theme', this.currentTheme);
    }
    
    hasStoredPreference() {
        return localStorage.getItem('theme') !== null;
    }
    
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    setupPerformanceOptimizations() {
        // Use requestAnimationFrame for smooth animations
        this.raf = requestAnimationFrame.bind(window);
        
        // Preload critical theme assets
        if (this.currentTheme === 'dark') {
            this.preloadDarkAssets();
        }
    }
    
    preloadDarkAssets() {
        // Preload any dark theme specific assets
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = 'css/dark-theme.css'; // Optional separate dark theme CSS
        document.head.appendChild(link);
    }
}

// Initialize enhanced theme manager
document.addEventListener('DOMContentLoaded', () => {
    new EnhancedThemeManager();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        // Re-apply theme when page becomes visible again
        const theme = localStorage.getItem('theme') || 
                     (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    }
});

// Enhanced error handling
window.addEventListener('error', (e) => {
    console.error('Theme manager error:', e.error);
});
</script>