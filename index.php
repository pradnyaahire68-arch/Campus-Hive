<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusHive - Connect Colleges & Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span class="fw-bold text-primary">Campus</span><span class="fw-bold text-accent">Hive</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-accent btn-sm px-3" href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Register
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="registerDropdown">
                            <li><a class="dropdown-item" href="auth/register_student.php">Join as Student</a></li>
                            <li><a class="dropdown-item" href="auth/register_college.php">join as College</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="fade-in">Connect Colleges & Students</h1>
                    <p class="lead">Discover inter-college events, workshops, and career opportunities. Register for events, track attendance, and navigate to venues with ease.
                        
                    </p>
                    
                    <div class="mt-4">
                        <a href="#features" class="btn btn-primary btn-lg px-5 py-3">
                            <i class="fas fa-search me-2"></i>Explore Services
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <img src="assets/images/hero-image.png" alt="CampusHive" class="img-fluid rounded shadow-lg" style="max-width: 100%; height: auto;" onerror="this.style.display='none'">
                        <div class="mt-3" style="display: none;">
                            <i class="fas fa-graduation-cap fa-5x text-white opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="text-primary mb-3">Why Choose CampusHive?</h2>
                    <p class="lead text-muted">A comprehensive platform designed for educational institutions and students</p>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-accent rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-calendar-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Event Discovery</h5>
                            <p class="card-text">Browse and filter events from multiple colleges. Find workshops, seminars, competitions, and career guidance programs.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-credit-card fa-2x text-white"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Online Payment Integration</h5>
                            <p class="card-text">Secure online payments for event registration. Colleges set fees, students pay securely and register instantly.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-map-marked-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Live Navigation</h5>
                            <p class="card-text">Get real-time directions to event venues using Google Maps. Never miss an event due to navigation issues.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-users fa-2x text-white"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Multi-User Platform</h5>
                            <p class="card-text">Separate panels for students, colleges, and administrators. Each user type has tailored features and permissions.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-mobile-alt fa-2x text-dark"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Responsive Design</h5>
                            <p class="card-text">Fully responsive interface that works perfectly on desktop, tablet, and mobile devices.</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-shield-alt fa-2x text-white"></i>
                            </div>
                            <h5 class="card-title text-primary mb-3">Secure & Reliable</h5>
                            <p class="card-text">Secure authentication, data protection, and reliable performance for all your educational needs.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3">CampusHive</h5>
                    <p class="mb-3">Bridging the gap between colleges and students through innovative event management.</p>
                    <div class="d-flex">
                        <a href="#" class="text-secondary me-3 fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-secondary me-3 fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-secondary me-3 fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-secondary fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Platform</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-secondary text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="auth/login.php" class="text-secondary text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="mb-3">For Students</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="auth/register_student.php" class="text-secondary text-decoration-none">Join as Student</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Browse Events</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">My Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="mb-3">For Colleges</h6>
                    <ul class="list-unstyled">

                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">Manage Events</a></li>
                        <li class="mb-2"><a href="#" class="text-secondary text-decoration-none">College Dashboard</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 CampusHive. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-secondary text-decoration-none me-3">Privacy Policy</a>
                    <a href="#" class="text-secondary text-decoration-none">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

    <!-- Smooth scrolling for anchor links -->
    <script>
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

        // Add fade-in animation to features on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('#features .card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
