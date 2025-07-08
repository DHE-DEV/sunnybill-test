<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Über Uns - Unternehmensberatung für Prozessoptimierung & Organisationsentwicklung</title>
        <meta name="description" content="Professionelle Unternehmensberatung mit Schwerpunkt Prozessoptimierung und Organisationsentwicklung. Strategische und betriebswirtschaftliche Lösungen für Ihr Unternehmen.">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

            /* Navigation */
            .navbar {
                position: fixed;
                top: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                z-index: 1000;
                padding: 1rem 2rem;
                transition: all 0.3s ease;
            }

            .nav-container {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .nav-logo {
                font-size: 1.5rem;
                font-weight: 700;
                color: #1a202c;
                text-decoration: none;
                background: linear-gradient(135deg, #667eea, #764ba2);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .nav-links {
                display: flex;
                gap: 2rem;
                list-style: none;
            }

            .nav-links a {
                text-decoration: none;
                color: #4a5568;
                font-weight: 500;
                transition: color 0.3s ease;
            }

            .nav-links a:hover {
                color: #667eea;
            }

            /* Hero Section */
            .hero {
                min-height: 100vh;
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05)), 
                           url('https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2126&q=80') center/cover no-repeat;
                display: flex;
                align-items: center;
                justify-content: center;
                position: relative;
                color: #1a202c;
                padding-top: 80px;
            }

            .hero-content {
                text-align: center;
                max-width: 900px;
                padding: 2rem;
                z-index: 2;
                background: rgba(255, 255, 255, 0.9);
                border-radius: 20px;
                backdrop-filter: blur(10px);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            }

            .hero-title {
                font-size: 3.5rem;
                font-weight: 800;
                margin-bottom: 1.5rem;
                background: linear-gradient(135deg, #667eea, #764ba2);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                line-height: 1.2;
            }

            .hero-subtitle {
                font-size: 1.3rem;
                font-weight: 500;
                margin-bottom: 2rem;
                color: #4a5568;
            }

            .hero-description {
                font-size: 1.1rem;
                margin-bottom: 3rem;
                color: #718096;
                max-width: 700px;
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
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            }

            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            }

            .btn-secondary {
                background: transparent;
                color: #667eea;
                border: 2px solid #667eea;
            }

            .btn-secondary:hover {
                background: #667eea;
                color: white;
                transform: translateY(-3px);
            }

            /* About Section */
            .about-section {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .section-title {
                text-align: center;
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #1a202c, #4a5568);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .section-title.white-text {
                background: none;
                -webkit-background-clip: unset;
                -webkit-text-fill-color: unset;
                background-clip: unset;
                color: white !important;
            }

            .section-subtitle {
                text-align: center;
                font-size: 1.2rem;
                color: #718096;
                margin-bottom: 4rem;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            .about-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                margin-bottom: 6rem;
            }

            .about-text h3 {
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                color: #1a202c;
            }

            .about-text p {
                font-size: 1.1rem;
                margin-bottom: 1.5rem;
                color: #4a5568;
                line-height: 1.8;
            }

            .about-visual {
                position: relative;
                height: 400px;
                background: rgba(255, 255, 255, 0.8);
                border-radius: 20px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            }

            .consultant-icon {
                font-size: 8rem;
                color: #667eea;
                opacity: 0.8;
            }

            /* Services Section */
            .services-section {
                padding: 8rem 2rem;
                background: white;
            }

            .services-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 3rem;
                margin-top: 4rem;
            }

            .service-card {
                background: white;
                padding: 3rem 2rem;
                border-radius: 20px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
                text-align: center;
                transition: all 0.4s ease;
                border: 1px solid rgba(0, 0, 0, 0.05);
                position: relative;
                overflow: hidden;
            }

            .service-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                transform: scaleX(0);
                transition: transform 0.4s ease;
            }

            .service-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12);
            }

            .service-card:hover::before {
                transform: scaleX(1);
            }

            .service-icon {
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
                display: block;
                color: #667eea;
            }

            .service-card h3 {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                color: #1a202c;
            }

            .service-card p {
                color: #4a5568;
                line-height: 1.7;
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }

            .service-features {
                list-style: none;
                text-align: left;
            }

            .service-features li {
                padding: 0.5rem 0;
                display: flex;
                align-items: center;
                font-size: 0.95rem;
                color: #4a5568;
            }

            .service-features li::before {
                content: '✓';
                color: #00ba88;
                font-weight: bold;
                margin-right: 1rem;
                font-size: 1.2rem;
            }

            /* Unique Value Proposition Section */
            .unique-value-section {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #1a202c, #2d3748);
                color: white;
            }

            .value-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                margin-top: 4rem;
            }

            .value-text h3 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .value-text p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                opacity: 0.9;
                line-height: 1.7;
            }

            .value-benefits {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            .benefit-item {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1.5rem;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 15px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: all 0.3s ease;
            }

            .benefit-item:hover {
                transform: translateX(10px);
                background: rgba(255, 255, 255, 0.15);
            }

            .benefit-icon {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: #1a202c;
                flex-shrink: 0;
            }

            .benefit-content h4 {
                font-size: 1.2rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: #ffd700;
            }

            .benefit-content p {
                font-size: 0.95rem;
                opacity: 0.8;
                margin: 0;
            }

            .tech-stack {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
                padding: 2rem;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .stack-layer {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
                padding: 1.5rem;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 15px;
                width: 100%;
                text-align: center;
                transition: all 0.3s ease;
            }

            .stack-layer:hover {
                transform: scale(1.05);
                background: rgba(255, 255, 255, 0.15);
            }

            .stack-layer i {
                font-size: 2.5rem;
                color: #ffd700;
            }

            .stack-layer span {
                font-weight: 600;
                font-size: 1rem;
            }

            .stack-connector {
                font-size: 2rem;
                font-weight: 700;
                color: #ffd700;
                text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            }

            .stack-result {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
                padding: 2rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                color: #1a202c;
                border-radius: 15px;
                width: 100%;
                text-align: center;
                box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
                animation: pulse 2s ease-in-out infinite;
            }

            .stack-result i {
                font-size: 3rem;
            }

            .stack-result span {
                font-weight: 700;
                font-size: 1.2rem;
            }

            @keyframes pulse {
                0%, 100% { 
                    transform: scale(1);
                    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
                }
                50% { 
                    transform: scale(1.02);
                    box-shadow: 0 15px 40px rgba(255, 215, 0, 0.5);
                }
            }

            /* Expertise Section */
            .expertise-section {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
            }

            .expertise-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin-top: 4rem;
            }

            .expertise-card {
                background: rgba(255, 255, 255, 0.1);
                padding: 2.5rem 2rem;
                border-radius: 15px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                transition: all 0.3s ease;
            }

            .expertise-card:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.15);
            }

            .expertise-card h4 {
                font-size: 1.3rem;
                font-weight: 600;
                margin-bottom: 1rem;
                color: #ffd700;
            }

            .expertise-card p {
                line-height: 1.7;
                opacity: 0.9;
            }

            /* Process Section */
            .process-section {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            }

            .process-steps {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin-top: 4rem;
            }

            .process-step {
                text-align: center;
                padding: 2rem;
                position: relative;
            }

            .step-number {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                font-weight: 700;
                margin: 0 auto 1.5rem;
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            }

            .process-step h4 {
                font-size: 1.3rem;
                font-weight: 600;
                margin-bottom: 1rem;
                color: #1a202c;
            }

            .process-step p {
                color: #4a5568;
                line-height: 1.6;
            }

            /* CTA Section */
            .cta-section {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #1a202c, #2d3748);
                color: white;
                text-align: center;
            }

            .cta-content h2 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .cta-content p {
                font-size: 1.2rem;
                margin-bottom: 3rem;
                opacity: 0.9;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }

            /* Footer */
            .footer {
                background: #0d1117;
                color: white;
                padding: 4rem 2rem 2rem;
            }

            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 3rem;
                margin-bottom: 2rem;
            }

            .footer-section h3 {
                font-size: 1.2rem;
                font-weight: 600;
                margin-bottom: 1rem;
                color: #ffd700;
            }

            .footer-section p,
            .footer-section a {
                color: #8b949e;
                text-decoration: none;
                line-height: 1.6;
            }

            .footer-section a:hover {
                color: #ffd700;
            }

            .footer-bottom {
                text-align: center;
                padding-top: 2rem;
                border-top: 1px solid #21262d;
                color: #8b949e;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .hero-title {
                    font-size: 2.5rem;
                }

                .about-content,
                .value-content {
                    grid-template-columns: 1fr;
                    text-align: center;
                    gap: 2rem;
                }

                .nav-links {
                    display: none;
                }

                .cta-buttons {
                    flex-direction: column;
                    align-items: center;
                }

                .btn {
                    width: 100%;
                    max-width: 300px;
                }

                .section-title {
                    font-size: 2rem;
                }

                .cta-content h2 {
                    font-size: 2rem;
                }

                .value-text h3 {
                    font-size: 2rem;
                }

                .benefit-item {
                    flex-direction: column;
                    text-align: center;
                    gap: 1rem;
                }

                .benefit-item:hover {
                    transform: translateY(-5px);
                }

                .tech-stack {
                    padding: 1rem;
                }

                .stack-layer {
                    padding: 1rem;
                }

                .stack-result {
                    padding: 1.5rem;
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

            .scroll-animate {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s ease;
            }

            .scroll-animate.animate {
                opacity: 1;
                transform: translateY(0);
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <a href="/" class="nav-logo">VoltMaster</a>
                <ul class="nav-links">
                    <li><a href="/">Startseite</a></li>
                    <li><a href="/ueber-uns">Über Uns</a></li>
                    <li><a href="{{ config('app.url') }}/admin">Dashboard</a></li>
                    <li><a href="mailto:info@voltmaster.de">Kontakt</a></li>
                </ul>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">Unternehmensberatung</h1>
                <p class="hero-subtitle">Prozessoptimierung & Organisationsentwicklung</p>
                <p class="hero-description">
                    Als erfahrener Unternehmensberater unterstütze ich Sie dabei, Ihre Geschäftsziele effizienter zu erreichen. 
                    Mit strategischem und betriebswirtschaftlichem Fokus optimiere ich Ihre Prozesse und entwickle Ihre Organisation nachhaltig weiter.
                </p>
                <div class="cta-buttons">
                    <a href="mailto:info@voltmaster.de" class="btn btn-primary">
                        Beratung anfragen
                    </a>
                    <a href="#services" class="btn btn-secondary">
                        Leistungen entdecken
                    </a>
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section class="about-section">
            <div class="container">
                <h2 class="section-title scroll-animate">Über mich</h2>
                <p class="section-subtitle scroll-animate">
                    Professionelle Unternehmensberatung mit Fokus auf nachhaltige Veränderungen
                </p>
                
                <div class="about-content">
                    <div class="about-text scroll-animate">
                        <h3>Ihr Partner für Unternehmenserfolg</h3>
                        <p>
                            Als Unternehmensberater mit über <strong>30 Jahren Erfahrung in der Softwareentwicklung</strong> 
                            biete ich Ihnen eine einzigartige Kombination aus strategischer Beratung und technischer Umsetzung. 
                            Ich arbeite projektbezogen als externer Berater und helfe Unternehmen dabei, 
                            ihre Geschäftsziele effizienter und nachhaltiger zu erreichen.
                        </p>
                        <p>
                            <strong>Mein entscheidender Vorteil:</strong> Ich programmiere selbst und habe direkten Zugriff 
                            auf ein erfahrenes Entwicklerteam. Dieser Synergieeffekt ermöglicht es mir, 
                            Prozessoptimierungen nicht nur zu konzipieren, sondern auch durch maßgeschneiderte 
                            Software direkt umzusetzen – kostengünstiger und effizienter als herkömmliche Beratung.
                        </p>
                        <p>
                            Mein Ansatz ist strategisch und betriebswirtschaftlich geprägt, häufig verbunden mit 
                            detaillierter Kennzahlenanalyse und professionellem Controlling. Durch die Kombination 
                            aus Beratung und Softwareentwicklung entstehen Lösungen, die nicht nur kurzfristige 
                            Verbesserungen bringen, sondern langfristig Ihre Wettbewerbsfähigkeit stärken und 
                            <strong>deutlich höhere Erträge</strong> generieren.
                        </p>
                    </div>
                    <div class="about-visual scroll-animate">
                        <i class="consultant-icon fas fa-code"></i>
                    </div>
                </div>
            </div>
        </section>

        <!-- Unique Value Proposition Section -->
        <section class="unique-value-section">
            <div class="container">
                <h2 class="section-title white-text scroll-animate">Einzigartiger Wettbewerbsvorteil</h2>
                <p class="section-subtitle scroll-animate" style="color: rgba(255,255,255,0.8);">
                    30+ Jahre Softwareentwicklung treffen auf strategische Unternehmensberatung
                </p>
                
                <div class="value-content">
                    <div class="value-text scroll-animate">
                        <h3>Beratung + Entwicklung = Maximaler ROI</h3>
                        <p>
                            Während herkömmliche Berater nur Konzepte liefern, setze ich Ihre Prozessoptimierungen 
                            direkt in maßgeschneiderte Software um. Diese einzigartige Kombination aus 
                            <strong>30+ Jahren Programmiererfahrung</strong> und strategischer Beratung 
                            schafft einen unschlagbaren Synergieeffekt.
                        </p>
                        <div class="value-benefits">
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-euro-sign"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Bis zu 60% Kosteneinsparung</h4>
                                    <p>Keine externen Entwickler nötig - alles aus einer Hand</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>3x schnellere Umsetzung</h4>
                                    <p>Direkter Zugriff auf erfahrenes Entwicklerteam</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="benefit-content">
                                    <h4>Deutlich höhere Erträge</h4>
                                    <p>Optimierte Prozesse durch perfekt angepasste Software</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="value-visual scroll-animate">
                        <div class="tech-stack">
                            <div class="stack-layer">
                                <i class="fas fa-brain"></i>
                                <span>Strategische Beratung</span>
                            </div>
                            <div class="stack-connector">+</div>
                            <div class="stack-layer">
                                <i class="fas fa-code"></i>
                                <span>30+ Jahre Entwicklung</span>
                            </div>
                            <div class="stack-connector">=</div>
                            <div class="stack-result">
                                <i class="fas fa-trophy"></i>
                                <span>Maximaler Erfolg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section id="services" class="services-section">
            <div class="container">
                <h2 class="section-title scroll-animate">Meine Leistungen</h2>
                <p class="section-subtitle scroll-animate">
                    Umfassende Beratungsleistungen für Ihre Unternehmensentwicklung
                </p>
                
                <div class="services-grid">
                    <div class="service-card scroll-animate">
                        <i class="service-icon fas fa-cogs"></i>
                        <h3>Prozessoptimierung</h3>
                        <p>
                            Analyse und Optimierung Ihrer Geschäftsprozesse für maximale Effizienz 
                            und Kosteneinsparungen.
                        </p>
                        <ul class="service-features">
                            <li>Prozessanalyse und -dokumentation</li>
                            <li>Identifikation von Optimierungspotenzialen</li>
                            <li>Implementierung effizienter Workflows</li>
                            <li>Digitalisierung von Arbeitsprozessen</li>
                            <li>Kontinuierliche Verbesserung (KVP)</li>
                        </ul>
                    </div>
                    
                    <div class="service-card scroll-animate">
                        <i class="service-icon fas fa-sitemap"></i>
                        <h3>Organisationsentwicklung</h3>
                        <p>
                            Strategische Weiterentwicklung Ihrer Organisationsstrukturen und 
                            Unternehmenskultur für nachhaltigen Erfolg.
                        </p>
                        <ul class="service-features">
                            <li>Organisationsanalyse und -design</li>
                            <li>Change Management Prozesse</li>
                            <li>Führungskräfteentwicklung</li>
                            <li>Teambuilding und Kommunikation</li>
                            <li>Kulturwandel und Transformation</li>
                        </ul>
                    </div>
                    
                    <div class="service-card scroll-animate">
                        <i class="service-icon fas fa-chart-line"></i>
                        <h3>Kennzahlenanalyse & Controlling</h3>
                        <p>
                            Entwicklung aussagekräftiger KPIs und Controlling-Systeme für 
                            datenbasierte Entscheidungen.
                        </p>
                        <ul class="service-features">
                            <li>KPI-Entwicklung und -implementierung</li>
                            <li>Reporting und Dashboard-Erstellung</li>
                            <li>Budgetplanung und -kontrolle</li>
                            <li>Kostenstellenrechnung</li>
                            <li>Performance Management</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Expertise Section -->
        <section class="expertise-section">
            <div class="container">
                <h2 class="section-title scroll-animate" style="color: white;">Meine Expertise</h2>
                <p class="section-subtitle scroll-animate" style="color: rgba(255,255,255,0.8);">
                    Fundierte Kenntnisse und bewährte Methoden für Ihren Unternehmenserfolg
                </p>
                
                <div class="expertise-grid">
                    <div class="expertise-card scroll-animate">
                        <h4>Strategische Beratung</h4>
                        <p>
                            Entwicklung und Umsetzung von Unternehmensstrategien, die Ihre 
                            langfristigen Ziele unterstützen und Wettbewerbsvorteile schaffen.
                        </p>
                    </div>
                    
                    <div class="expertise-card scroll-animate">
                        <h4>Betriebswirtschaftliche Analyse</h4>
                        <p>
                            Tiefgreifende Analyse Ihrer Geschäftsprozesse, Kostenstrukturen 
                            und Ertragspotenziale mit konkreten Handlungsempfehlungen.
                        </p>
                    </div>
                    
                    <div class="expertise-card scroll-animate">
                        <h4>Projektmanagement</h4>
                        <p>
                            Professionelle Leitung und Begleitung von Veränderungsprojekten 
                            von der Konzeption bis zur erfolgreichen Implementierung.
                        </p>
                    </div>
                    
                    <div class="expertise-card scroll-animate">
                        <h4>Digitale Transformation</h4>
                        <p>
                            Unterstützung bei der digitalen Transformation Ihres Unternehmens 
                            mit modernen Tools und Technologien.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Section -->
        <section class="process-section">
            <div class="container">
                <h2 class="section-title scroll-animate">Mein Beratungsprozess</h2>
                <p class="section-subtitle scroll-animate">
                    Strukturiertes Vorgehen für nachhaltige Ergebnisse
                </p>
                
                <div class="process-steps">
                    <div class="process-step scroll-animate">
                        <div class="step-number">1</div>
                        <h4>Analyse & Diagnose</h4>
                        <p>
                            Umfassende Analyse Ihrer aktuellen Situation, Identifikation von 
                            Herausforderungen und Potenzialen.
                        </p>
                    </div>
                    
                    <div class="process-step scroll-animate">
                        <div class="step-number">2</div>
                        <h4>Strategieentwicklung</h4>
                        <p>
                            Entwicklung maßgeschneiderter Lösungsansätze und strategischer 
                            Handlungsempfehlungen.
                        </p>
                    </div>
                    
                    <div class="process-step scroll-animate">
                        <div class="step-number">3</div>
                        <h4>Implementierung</h4>
                        <p>
                            Begleitung bei der Umsetzung der entwickelten Maßnahmen mit 
                            kontinuierlicher Erfolgskontrolle.
                        </p>
                    </div>
                    
                    <div class="process-step scroll-animate">
                        <div class="step-number">4</div>
                        <h4>Nachhaltigkeit</h4>
                        <p>
                            Sicherstellung der langfristigen Wirksamkeit durch Monitoring 
                            und kontinuierliche Optimierung.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Bereit für Veränderung?</h2>
                    <p>
                        Lassen Sie uns gemeinsam Ihre Unternehmensziele erreichen. 
                        Kontaktieren Sie mich für ein unverbindliches Beratungsgespräch.
                    </p>
                    <div class="cta-buttons">
                        <a href="mailto:info@voltmaster.de" class="btn btn-primary">
                            Beratung anfragen
                        </a>
                        <a href="tel:+49123456789" class="btn btn-secondary">
                            Anrufen
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>Unternehmensberatung</h3>
                        <p>
                            Professionelle Beratung für Prozessoptimierung und Organisationsentwicklung. 
                            Ihr Partner für nachhaltigen Unternehmenserfolg.
                        </p>
                    </div>
                    <div class="footer-section">
                        <h3>Leistungen</h3>
                        <p><a href="#services">Prozessoptimierung</a></p>
                        <p><a href="#services">Organisationsentwicklung</a></p>
                        <p><a href="#services">Kennzahlenanalyse</a></p>
                        <p><a href="#services">Strategische Beratung</a></p>
                    </div>
                    <div class="footer-section">
                        <h3>Kontakt</h3>
                        <p><a href="mailto:info@voltmaster.de">info@voltmaster.de</a></p>
                        <p><a href="tel:+49123456789">+49 123 456 789</a></p>
                        <p>Verfügbar für Projekte deutschlandweit</p>
                    </div>
                    <div class="footer-section">
                        <h3>Rechtliches</h3>
                        <p><a href="{{ route('datenschutz') }}">Datenschutz</a></p>
                        <p><a href="{{ route('impressum') }}">Impressum</a></p>
                        <p><a href="{{ route('nutzungsbedingungen') }}">Nutzungsbedingungen</a></p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; {{ date('Y') }} Unternehmensberatung. Alle Rechte vorbehalten.</p>
                </div>
            </div>
        </footer>

        <script>
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

            // Scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.scroll-animate').forEach(el => {
                observer.observe(el);
            });

            // Navbar scroll effect
            window.addEventListener('scroll', () => {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 100) {
                    navbar.style.background = 'rgba(255, 255, 255, 0.98)';
                    navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
                } else {
                    navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                    navbar.style.boxShadow = 'none';
                }
            });
        </script>
    </body>
</html>
