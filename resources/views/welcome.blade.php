<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VoltMaster - Solarenergie Management</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', sans-serif;
                line-height: 1.6;
                color: #333;
                overflow-x: hidden;
            }

            /* Hero Section mit Solarpanel Hintergrund */
            .hero {
                min-height: 100vh;
                background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), 
                           url('https://images.unsplash.com/photo-1509391366360-2e959784a276?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2072&q=80') center/cover no-repeat;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                color: white;
            }

            .hero-content {
                text-align: center;
                max-width: 800px;
                padding: 2rem;
                z-index: 2;
            }

            .logo {
                font-size: 4rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }

            .tagline {
                font-size: 1.5rem;
                font-weight: 300;
                margin-bottom: 2rem;
                opacity: 0.95;
            }

            .description {
                font-size: 1.1rem;
                margin-bottom: 3rem;
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

            .btn {
                display: inline-block;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 50px;
                font-size: 1.1rem;
                font-weight: 600;
                transition: all 0.3s ease;
                border: 2px solid transparent;
                min-width: 180px;
                text-align: center;
            }

            .btn-primary {
                background: linear-gradient(135deg, #f53003, #ff6b35);
                color: white;
                box-shadow: 0 8px 25px rgba(245, 48, 3, 0.3);
            }

            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 35px rgba(245, 48, 3, 0.4);
                background: linear-gradient(135deg, #d42a02, #e55a2b);
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.1);
                color: white;
                border: 2px solid rgba(255, 255, 255, 0.3);
                backdrop-filter: blur(10px);
            }

            .btn-secondary:hover {
                background: rgba(255, 255, 255, 0.2);
                border-color: rgba(255, 255, 255, 0.5);
                transform: translateY(-3px);
            }

            /* Features Section */
            .features {
                padding: 5rem 2rem;
                background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .section-title {
                text-align: center;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 3rem;
                color: #1a202c;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin-top: 3rem;
            }

            .feature-card {
                background: white;
                padding: 2rem;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                text-align: center;
                transition: transform 0.3s ease;
            }

            .feature-card:hover {
                transform: translateY(-5px);
            }

            .feature-icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }

            .feature-title {
                font-size: 1.3rem;
                font-weight: 600;
                margin-bottom: 1rem;
                color: #2d3748;
            }

            .feature-description {
                color: #718096;
                line-height: 1.6;
            }

            /* Stats Section */
            .stats {
                background: linear-gradient(135deg, #667eea, #764ba2);
                padding: 4rem 2rem;
                color: white;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 2rem;
                text-align: center;
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

            /* Footer */
            .footer {
                background: #1a202c;
                color: white;
                padding: 2rem;
                text-align: center;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .logo {
                    font-size: 2.5rem;
                }

                .tagline {
                    font-size: 1.2rem;
                }

                .cta-buttons {
                    flex-direction: column;
                    align-items: center;
                }

                .btn {
                    width: 100%;
                    max-width: 300px;
                }

                .features-grid {
                    grid-template-columns: 1fr;
                }
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

            .hero-content > * {
                animation: fadeInUp 0.8s ease-out forwards;
            }

            .hero-content > *:nth-child(2) {
                animation-delay: 0.2s;
            }

            .hero-content > *:nth-child(3) {
                animation-delay: 0.4s;
            }

            .hero-content > *:nth-child(4) {
                animation-delay: 0.6s;
            }
        </style>
    </head>
    <body>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="logo">VoltMaster</h1>
                <p class="tagline">Intelligente Solarenergie-Verwaltung</p>
                <p class="description">
                    Optimieren Sie Ihre Solaranlagen mit unserer fortschrittlichen Management-Plattform. 
                    Überwachen Sie Leistung, verwalten Sie Abrechnungen und maximieren Sie Ihre Energieeffizienz.
                </p>
                <div class="cta-buttons">
                    <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                        🔐 Admin Login
                    </a>
                    <a href="#features" class="btn btn-secondary">
                        📋 Mehr erfahren
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <h2 class="section-title">Unsere Leistungen</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">☀️</div>
                        <h3 class="feature-title">Anlagen-Monitoring</h3>
                        <p class="feature-description">
                            Überwachen Sie Ihre Solaranlagen in Echtzeit und optimieren Sie die Energieproduktion.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3 class="feature-title">Abrechnungsmanagement</h3>
                        <p class="feature-description">
                            Automatisierte Abrechnung und detaillierte Berichte für maximale Transparenz.
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3 class="feature-title">Effizienz-Optimierung</h3>
                        <p class="feature-description">
                            Intelligente Algorithmen zur Maximierung Ihrer Solarenergie-Ausbeute.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Verwaltete Anlagen</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50MW</div>
                        <div class="stat-label">Installierte Leistung</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Verfügbarkeit</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>&copy; {{ date('Y') }} VoltMaster. Alle Rechte vorbehalten.</p>
                <p>Professionelle Solarenergie-Verwaltung für eine nachhaltige Zukunft.</p>
            </div>
        </footer>

        <script>
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });

            // Add scroll effect to hero
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const hero = document.querySelector('.hero');
                hero.style.transform = `translateY(${scrolled * 0.5}px)`;
            });
        </script>
    </body>
</html>
