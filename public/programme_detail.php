<?php
require_once "../config/database.php";

$programme_id = $_GET['id'] ?? null;
if (!$programme_id) {
    header("Location: programmes.php");
    exit();
}

// Fetch programme details
$stmt = $pdo->prepare("SELECT * FROM programmes WHERE id = ?");
$stmt->execute([$programme_id]);
$programme = $stmt->fetch();

if (!$programme) {
    die("Programme not found.");
}

// Fetch programme media/images
$media_stmt = $pdo->prepare("SELECT * FROM programme_media WHERE programme_id = ? ORDER BY uploaded_at DESC");
$media_stmt->execute([$programme_id]);
$programme_media = $media_stmt->fetchAll();

// Get primary image (first image or default)
$primary_image = null;
if (!empty($programme_media)) {
    $primary_image = $programme_media[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($programme['title']) ?> | Our NGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header Styles */
        .page-header {
            margin-bottom: 3rem;
            padding: 2rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .header-content {
            text-align: center;
            padding: 0 2rem;
        }

        .header-content h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .page-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.15);
            transition: var(--transition);
        }

        .back-link:hover {
            transform: translateX(-5px);
            box-shadow: 0 6px 20px rgba(43, 108, 176, 0.25);
        }

        /* Hero Section */
        .programme-hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
            align-items: center;
        }

        @media (max-width: 968px) {
            .programme-hero {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        .hero-image {
            border-radius: var(--radius);
            overflow: hidden;
            height: 500px;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .hero-image:hover img {
            transform: scale(1.05);
        }

        .hero-content {
            padding: 2rem 0;
        }

        .programme-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text);
            border: 2px solid var(--border);
        }

        .featured-badge {
            background: var(--warning);
            color: white;
            border-color: var(--warning);
        }

        .programme-description {
            color: var(--text);
            line-height: 1.8;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .impact-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px var(--shadow);
        }

        .impact-stat {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Gallery Section */
        .gallery-section {
            margin: 4rem 0;
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .gallery-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            height: 250px;
            background: var(--bg);
            cursor: pointer;
            transition: var(--transition);
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(transparent 50%, rgba(0,0,0,0.7));
            display: flex;
            align-items: flex-end;
            padding: 1.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-info {
            color: white;
        }

        .gallery-info .file-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .gallery-info .file-meta {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 4rem 2rem;
            border-radius: var(--radius);
            text-align: center;
            margin: 4rem 0;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Modal for Image Viewing */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            margin: 2rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: var(--radius);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
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
            font-size: 1.5rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            .header-content h1 {
                font-size: 2.5rem;
            }

            .page-subtitle {
                font-size: 1.1rem;
            }

            .hero-image {
                height: 350px;
            }

            .programme-description {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .impact-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .header-content h1 {
                font-size: 2rem;
            }

            .programme-meta {
                flex-direction: column;
                align-items: flex-start;
            }

            .impact-stats {
                grid-template-columns: 1fr;
            }

            .meta-badge {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .stagger-item {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .stagger-item.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Navigation -->
        <a href="programmes.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Programmes
        </a>

        <!-- Page Header -->
        <section class="page-header fade-in">
            <div class="header-content">
                <h1><?= htmlspecialchars($programme['title']) ?></h1>
                <p class="page-subtitle">Making a meaningful impact in our community through dedicated service and support</p>
                
                <div class="programme-meta" style="justify-content: center; margin-top: 2rem;">
                    <span class="meta-badge">
                        <i class="fas fa-calendar"></i>
                        Active since <?= date('M Y', strtotime($programme['created_at'])) ?>
                    </span>
                    <?php if($programme['is_featured']): ?>
                        <span class="meta-badge featured-badge">
                            <i class="fas fa-star"></i> Featured Programme
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Programme Hero Section -->
        <section class="programme-hero fade-in">
            <div class="hero-image">
                <?php if($primary_image): ?>
                    <img src="../uploads/<?= htmlspecialchars($primary_image['file_name']) ?>" 
                         alt="<?= htmlspecialchars($programme['title']) ?>"
                         loading="eager">
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-light); padding: 2rem;">
                        <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--text-light);">Programme Image</h3>
                        <p>Visual representation coming soon</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="hero-content">
                <div class="programme-description">
                    <?= nl2br(htmlspecialchars($programme['introduction'] ?? 'We are committed to making a positive difference through this programme. Our dedicated team works tirelessly to achieve meaningful outcomes and create lasting impact in the community.')) ?>
                </div>

                <!-- Impact Statistics -->
                <div class="impact-stats">
                    <div class="impact-stat">
                        <div class="stat-number" data-count="1500">0</div>
                        <div class="stat-label">Lives Impacted</div>
                    </div>
                    <div class="impact-stat">
                        <div class="stat-number" data-count="12">0</div>
                        <div class="stat-label">Communities Served</div>
                    </div>
                    <div class="impact-stat">
                        <div class="stat-number" data-count="95">0</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                    <div class="impact-stat">
                        <div class="stat-number" data-count="24">0</div>
                        <div class="stat-label">Ongoing Projects</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Programme Gallery -->
        <?php if(!empty($programme_media)): ?>
        <section class="gallery-section fade-in">
            <div class="section-header">
                <h2 class="section-title">Programme Gallery</h2>
                <p class="section-subtitle">Visual stories from our work in the community</p>
            </div>

            <div class="gallery-grid">
                <?php foreach($programme_media as $index => $media): ?>
                    <div class="gallery-item stagger-item" data-index="<?= $index ?>">
                        <img src="../uploads/<?= htmlspecialchars($media['file_name']) ?>" 
                             alt="Programme activity image"
                             loading="lazy">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <div class="file-name">Image <?= $index + 1 ?></div>
                                <div class="file-meta">
                                    <?= date('M j, Y', strtotime($media['uploaded_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Call to Action -->
        <section class="cta-section fade-in">
            <div class="cta-content">
                <h2>Support This Programme</h2>
                <p>Your contribution helps us continue this vital work and expand our impact in the community. Every donation makes a difference.</p>
                <div class="cta-buttons">
                    <a href="donate.php?programme=<?= $programme_id ?>" class="btn btn-primary">
                        <i class="fas fa-donate"></i> Donate Now
                    </a>
                    <a href="volunteer.php" class="btn btn-outline">
                        <i class="fas fa-hands-helping"></i> Volunteer
                    </a>
                    <a href="contact.php" class="btn btn-outline">
                        <i class="fas fa-envelope"></i> Get Involved
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <button class="close-modal" aria-label="Close modal">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-content">
            <img id="modalImage" src="" alt="" class="modal-image">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        
                        // Animate impact numbers
                        if (entry.target.classList.contains('impact-stats')) {
                            animateNumbers();
                        }
                        
                        // Stagger gallery items
                        if (entry.target.classList.contains('gallery-grid')) {
                            const items = entry.target.querySelectorAll('.stagger-item');
                            items.forEach((item, index) => {
                                setTimeout(() => {
                                    item.classList.add('visible');
                                }, index * 100);
                            });
                        }
                    }
                });
            }, observerOptions);

            // Observe elements
            document.querySelectorAll('.fade-in, .gallery-grid, .impact-stats').forEach(el => {
                observer.observe(el);
            });

            // Animated Number Counting
            function animateNumbers() {
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
                            counter.textContent = target + (counter.dataset.count === '95' ? '%' : '+');
                        }
                    };
                    
                    updateCounter();
                });
            }

            // Image Modal Functionality
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const galleryItems = document.querySelectorAll('.gallery-item');
            const closeModal = document.querySelector('.close-modal');

            galleryItems.forEach(item => {
                item.addEventListener('click', () => {
                    const img = item.querySelector('img');
                    modalImage.src = img.src;
                    modalImage.alt = img.alt;
                    modal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            });

            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
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
        });
    </script>
</body>
</html>