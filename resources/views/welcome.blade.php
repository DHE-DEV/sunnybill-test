<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VoltMaster - Solarenergie Management</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

            /* Hero Section */
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

            /* Features Section - Stripe Style */
            .features {
                padding: 8rem 2rem;
                background: #fafbfc;
                position: relative;
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
                color: #1a202c;
                background: linear-gradient(135deg, #1a202c, #4a5568);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
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

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 3rem;
                margin-top: 4rem;
            }

            .feature-card {
                background: white;
                padding: 3rem 2rem;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
                text-align: left;
                transition: all 0.4s ease;
                border: 1px solid rgba(0, 0, 0, 0.05);
                position: relative;
                overflow: hidden;
            }

            .feature-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(135deg, #f53003, #ff6b35);
                transform: scaleX(0);
                transition: transform 0.4s ease;
            }

            .feature-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 30px 80px rgba(0, 0, 0, 0.12);
            }

            .feature-card:hover::before {
                transform: scaleX(1);
            }

            .feature-icon {
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
                display: block;
            }

            .feature-title {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                color: #1a202c;
            }

            .feature-description {
                color: #4a5568;
                line-height: 1.7;
                font-size: 1rem;
            }

            /* Customer Management Section */
            .customer-management {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #f8fafc, #e2e8f0);
                position: relative;
            }

            .customer-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                position: relative;
                z-index: 2;
            }

            .customer-text h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                line-height: 1.2;
                color: #1a202c;
            }

            .customer-text p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                color: #4a5568;
                line-height: 1.7;
            }

            .customer-features {
                list-style: none;
                margin-bottom: 2rem;
            }

            .customer-features li {
                padding: 0.5rem 0;
                display: flex;
                align-items: center;
                font-size: 1rem;
                color: #4a5568;
            }

            .customer-features li::before {
                content: '✓';
                color: #00ba88;
                font-weight: bold;
                margin-right: 1rem;
                font-size: 1.2rem;
            }

            .customer-visual {
                position: relative;
                height: 500px;
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

            .dashboard-screenshot {
                width: 95%;
                height: 95%;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }

            .dashboard-screenshot img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 15px;
            }

            /* Product Showcase Section */
            .product-showcase {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                position: relative;
                overflow: hidden;
            }

            .showcase-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                position: relative;
                z-index: 2;
            }

            .showcase-text h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                line-height: 1.2;
            }

            .showcase-text p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                opacity: 0.9;
                line-height: 1.7;
            }

            .showcase-features {
                list-style: none;
                margin-bottom: 2rem;
            }

            .showcase-features li {
                padding: 0.5rem 0;
                display: flex;
                align-items: center;
                font-size: 1rem;
            }

            .showcase-features li::before {
                content: '✓';
                color: #ffd700;
                font-weight: bold;
                margin-right: 1rem;
                font-size: 1.2rem;
            }

            .showcase-visual {
                position: relative;
                height: 400px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 20px;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            .dashboard-preview {
                width: 90%;
                height: 90%;
                background: linear-gradient(135deg, #1a202c, #2d3748);
                border-radius: 15px;
                position: relative;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            }

            .dashboard-header {
                height: 60px;
                background: linear-gradient(135deg, #f53003, #ff6b35);
                border-radius: 15px 15px 0 0;
                display: flex;
                align-items: center;
                padding: 0 1.5rem;
                color: white;
                font-weight: 600;
            }

            .dashboard-content {
                padding: 1.5rem;
                height: calc(100% - 60px);
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .dashboard-card {
                background: rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                padding: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .dashboard-card h4 {
                color: #ffd700;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }

            .dashboard-card .value {
                color: white;
                font-size: 1.5rem;
                font-weight: 700;
            }

            /* Stats Section */
            .stats {
                background: #1a202c;
                padding: 6rem 2rem;
                color: white;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 3rem;
                text-align: center;
            }

            .stat-item {
                padding: 2rem;
                border-radius: 15px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
            }

            .stat-item:hover {
                transform: translateY(-5px);
                background: rgba(255, 255, 255, 0.08);
            }

            .stat-number {
                font-size: 3.5rem;
                font-weight: 800;
                margin-bottom: 0.5rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .stat-label {
                font-size: 1.1rem;
                opacity: 0.9;
                font-weight: 500;
            }

            /* Testimonials Section */
            .testimonials {
                padding: 8rem 2rem;
                background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            }

            .testimonials-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 2rem;
                margin-top: 4rem;
            }

            .testimonial-card {
                background: white;
                padding: 2.5rem;
                border-radius: 20px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
                position: relative;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .testimonial-quote {
                font-size: 1.1rem;
                line-height: 1.7;
                color: #4a5568;
                margin-bottom: 2rem;
                font-style: italic;
            }

            .testimonial-author {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .author-avatar {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(135deg, #f53003, #ff6b35);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 700;
                font-size: 1.2rem;
            }

            .author-info h4 {
                font-weight: 600;
                color: #1a202c;
                margin-bottom: 0.2rem;
            }

            .author-info p {
                color: #718096;
                font-size: 0.9rem;
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

                .features-grid,
                .testimonials-grid {
                    grid-template-columns: 1fr;
                }

                .showcase-content,
                .customer-content {
                    grid-template-columns: 1fr;
                    text-align: center;
                }

                .customer-visual {
                    height: 300px;
                }

                .section-title {
                    font-size: 2rem;
                }

                .cta-content h2 {
                    font-size: 2rem;
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

            /* Scroll Animations */
            .scroll-animate {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s ease;
            }

            .scroll-animate.animate {
                opacity: 1;
                transform: translateY(0);
            }

            /* Custom Cursor Styles */
            .custom-cursor {
                position: fixed;
                width: 20px;
                height: 20px;
                background: radial-gradient(circle, rgba(255, 215, 0, 0.8), rgba(255, 237, 78, 0.4));
                border-radius: 50%;
                pointer-events: none;
                z-index: 9998;
                transform: translate(-50%, -50%);
                transition: all 0.1s ease;
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
                border: 2px solid rgba(255, 255, 255, 0.3);
            }

            .custom-cursor-inner {
                position: fixed;
                width: 8px;
                height: 8px;
                background: #ffd700;
                border-radius: 50%;
                pointer-events: none;
                z-index: 9999;
                transform: translate(-50%, -50%);
                transition: all 0.05s ease;
                box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
            }

            .custom-cursor.hovering {
                width: 40px;
                height: 40px;
                background: radial-gradient(circle, rgba(245, 48, 3, 0.3), rgba(255, 107, 53, 0.1));
                box-shadow: 0 0 30px rgba(245, 48, 3, 0.6);
                border-color: rgba(245, 48, 3, 0.5);
            }

            .custom-cursor-inner.hovering {
                width: 12px;
                height: 12px;
                background: #f53003;
                box-shadow: 0 0 15px rgba(245, 48, 3, 0.8);
            }

            .custom-cursor.clicking {
                width: 30px;
                height: 30px;
                background: radial-gradient(circle, rgba(255, 215, 0, 1), rgba(255, 237, 78, 0.6));
                box-shadow: 0 0 40px rgba(255, 215, 0, 0.8);
            }

            .custom-cursor-inner.clicking {
                width: 6px;
                height: 6px;
                background: #ffed4e;
            }

            /* Hide cursor on mobile devices */
            @media (max-width: 768px) {
                .custom-cursor,
                .custom-cursor-inner {
                    display: none !important;
                }
                
                body {
                    cursor: auto !important;
                }
            }
        </style>
    </head>
    <body>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="logo">VoltMaster</h1>
                <p class="tagline">Professionelles Solarenergie-Management</p>
                <p class="description">
                    Intelligente Plattform für die Verwaltung und Optimierung 
                    Ihrer Solarenergie-Infrastruktur.
                </p>
                <div class="cta-buttons">
                    <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                        Jetzt starten
                    </a>
                    <a href="#features" class="btn btn-secondary">
                        Mehr erfahren
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <h2 class="section-title scroll-animate">Kernfunktionen</h2>
                <p class="section-subtitle scroll-animate">Professionelle Lösungen für Ihr Solarenergie-Management</p>
                
                <div class="features-grid">
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">🏢</span>
                        <h3 class="feature-title">Stakeholder-Management</h3>
                        <p class="feature-description">
                            Zentrale Verwaltung von Kunden, Lieferanten und Dienstleistern. 
                            Komplette Stammdatenverwaltung mit Vertragsmanagement und Kommunikationshistorie.
                        </p>
                    </div>
                    
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">🤖</span>
                        <h3 class="feature-title">KI-gestützte Abrechnung</h3>
                        <p class="feature-description">
                            Automatische Kostenaufteilung und Positionsgenerierung durch KI. 
                            Intelligente Abrechnungsprozesse mit präziser Artikelverwaltung bis zu 6 Nachkommastellen.
                        </p>
                    </div>
                    
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">⚖️</span>
                        <h3 class="feature-title">Beteiligungsmanagement</h3>
                        <p class="feature-description">
                            Verwaltung von Firmen- und Privatkundenbeteiligungen an Solaranlagen. 
                            Automatische Ertragsverteilung und transparente Abrechnungsübersichten.
                        </p>
                    </div>
                    
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">📋</span>
                        <h3 class="feature-title">Aufgaben & Dokumente</h3>
                        <p class="feature-description">
                            Integriertes Aufgabenmanagement mit umfassender Dokumentenverwaltung. 
                            Zentrale Ablage für alle Dokumente zu Kunden, Anlagen, Verträgen und Abrechnungen.
                        </p>
                    </div>
                    
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">💳</span>
                        <h3 class="feature-title">Rechnungswesen</h3>
                        <p class="feature-description">
                            Vollständiges Rechnungsmanagement mit Gutschriften und ZUGFeRD-Integration. 
                            Automatisierte Rechnungsstellung mit KI-Unterstützung für optimale Effizienz.
                        </p>
                    </div>
                    
                    <div class="feature-card scroll-animate">
                        <span class="feature-icon">⚡</span>
                        <h3 class="feature-title">Anlagenverwaltung</h3>
                        <p class="feature-description">
                            Komplette Solaranlagen-Administration mit Leistungsüberwachung. 
                            Detaillierte Anlagendokumentation und Performance-Tracking für maximale Effizienz.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Management Section -->
        <section class="customer-management">
            <div class="container">
                <div class="customer-content">
                    <div class="customer-text">
                        <h2>Intelligente Kundenverwaltung</h2>
                        <p>
                            Behalten Sie den Überblick über alle Ihre Kunden mit detaillierten Analysen und Statistiken. 
                            Unterscheiden Sie zwischen Privat- und Firmenkunden und verfolgen Sie deren Aktivitäten in Echtzeit.
                        </p>
                        <ul class="customer-features">
                            <li>Automatische Kategorisierung von Privat- und Firmenkunden</li>
                            <li>Detaillierte Kundenstatistiken und Wachstumsanalysen</li>
                            <li>Solar-Beteiligungen und Rechnungsübersicht</li>
                            <li>Aktivitätstracking und Kundenverteilung nach Monaten</li>
                            <li>Umfassende Dokumentenverwaltung pro Kunde</li>
                        </ul>
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Kundenverwaltung erkunden
                        </a>
                    </div>
                    <div class="customer-visual">
                        <div class="dashboard-screenshot">
                            <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI4MDAiIHZpZXdCb3g9IjAgMCAxMjAwIDgwMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjEyMDAiIGhlaWdodD0iODAwIiBmaWxsPSIjRkFGQkZDIi8+CjxyZWN0IHg9IjIwIiB5PSIyMCIgd2lkdGg9IjIwMCIgaGVpZ2h0PSI3NjAiIGZpbGw9IiNGRkZGRkYiIHJ4PSIxMCIvPgo8cmVjdCB4PSIyNDAiIHk9IjIwIiB3aWR0aD0iOTQwIiBoZWlnaHQ9Ijc2MCIgZmlsbD0iI0ZGRkZGRiIgcng9IjEwIi8+Cjx0ZXh0IHg9IjI2MCIgeT0iNzAiIGZpbGw9IiMxQTIwMkMiIGZvbnQtZmFtaWx5PSJJbnRlciIgZm9udC1zaXplPSIzMiIgZm9udC13ZWlnaHQ9IjcwMCI+S3VuZGVuPC90ZXh0Pgo8cmVjdCB4PSIyNjAiIHk9IjEyMCIgd2lkdGg9IjI4MCIgaGVpZ2h0PSIxNDAiIGZpbGw9IiNGOEZBRkMiIHJ4PSIxMCIvPgo8dGV4dCB4PSIyODAiIHk9IjE1MCIgZmlsbD0iIzcxODA5NiIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjE0Ij5HZXNhbXQgS3VuZGVuPC90ZXh0Pgo8dGV4dCB4PSIyODAiIHk9IjE5MCIgZmlsbD0iIzFBMjAyQyIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjQ4IiBmb250LXdlaWdodD0iNzAwIj4zPC90ZXh0Pgo8dGV4dCB4PSIyODAiIHk9IjIyMCIgZmlsbD0iI0Y1OTAwMyIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjEyIj5BbGxlIHJlZ2lzdHJpZXJ0ZW4gS3VuZGVuPC90ZXh0Pgo8cmVjdCB4PSI1NjAiIHk9IjEyMCIgd2lkdGg9IjI4MCIgaGVpZ2h0PSIxNDAiIGZpbGw9IiNGOEZBRkMiIHJ4PSIxMCIvPgo8dGV4dCB4PSI1ODAiIHk9IjE1MCIgZmlsbD0iIzcxODA5NiIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjE0Ij5Qcml2YXRrdW5kZW48L3RleHQ+Cjx0ZXh0IHg9IjU4MCIgeT0iMTkwIiBmaWxsPSIjMUEyMDJDIiBmb250LWZhbWlseT0iSW50ZXIiIGZvbnQtc2l6ZT0iNDgiIGZvbnQtd2VpZ2h0PSI3MDAiPjI8L3RleHQ+Cjx0ZXh0IHg9IjU4MCIgeT0iMjIwIiBmaWxsPSIjMzMzM0ZGIiBmb250LWZhbWlseT0iSW50ZXIiIGZvbnQtc2l6ZT0iMTIiPjY2LjclIGFsbGVyIEt1bmRlbjwvdGV4dD4KPHJlY3QgeD0iODYwIiB5PSIxMjAiIHdpZHRoPSIyODAiIGhlaWdodD0iMTQwIiBmaWxsPSIjRjhGQUZDIiByeD0iMTAiLz4KPHRleHQgeD0iODgwIiB5PSIxNTAiIGZpbGw9IiM3MTgwOTYiIGZvbnQtZmFtaWx5PSJJbnRlciIgZm9udC1zaXplPSIxNCI+RmlybWVua3VuZGVuPC90ZXh0Pgo8dGV4dCB4PSI4ODAiIHk9IjE5MCIgZmlsbD0iIzFBMjAyQyIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjQ4IiBmb250LXdlaWdodD0iNzAwIj4xPC90ZXh0Pgo8dGV4dCB4PSI4ODAiIHk9IjIyMCIgZmlsbD0iIzAwQkE4OCIgZm9udC1mYW1pbHk9IkludGVyIiBmb250LXNpemU9IjEyIj4zMy4zJSBhbGxlciBLdW5kZW48L3RleHQ+CjxyZWN0IHg9IjI2MCIgeT0iMzAwIiB3aWR0aD0iNDQwIiBoZWlnaHQ9IjI2MCIgZmlsbD0iI0Y4RkFGQyIgcng9IjEwIi8+Cjx0ZXh0IHg9IjI4MCIgeT0iMzMwIiBmaWxsPSIjMUEyMDJDIiBmb250LWZhbWlseT0iSW50ZXIiIGZvbnQtc2l6ZT0iMTYiIGZvbnQtd2VpZ2h0PSI2MDAiPkt1bmRlbndhaHNzdHVtIC0gQWt0aXYgdnMuIEluYWt0aXY8L3RleHQ+CjxsaW5lIHgxPSIzMDAiIHkxPSI1MDAiIHgyPSI2NjAiIHkyPSI0MDAiIHN0cm9rZT0iIzAwQkE4OCIgc3Ryb2tlLXdpZHRoPSIzIi8+CjxyZWN0IHg9IjcyMCIgeT0iMzAwIiB3aWR0aD0iNDIwIiBoZWlnaHQ9IjI2MCIgZmlsbD0iI0Y4RkFGQyIgcng9IjEwIi8+Cjx0ZXh0IHg9Ijc0MCIgeT0iMzMwIiBmaWxsPSIjMUEyMDJDIiBmb250LWZhbWlseT0iSW50ZXIiIGZvbnQtc2l6ZT0iMTYiIGZvbnQtd2VpZ2h0PSI2MDAiPkt1bmRlbnZlcnRlaWx1bmcgbmFjaCBNb25hdGVuPC90ZXh0Pgo8cmVjdCB4PSI5NDAiIHk9IjQ2MCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjMDBCQTg4Ii8+CjxyZWN0IHg9IjEwMDAiIHk9IjQ4MCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjU5MDAzIi8+CjwvZz4KPC9zdmc+" alt="VoltMaster Kundenverwaltung Dashboard" style="width: 100%; height: auto; border-radius: 15px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Showcase Section -->
        <section class="product-showcase">
            <div class="container">
                <div class="showcase-content">
                    <div class="showcase-text">
                        <h2>Alles in einem Dashboard</h2>
                        <p>
                            Verwalten Sie Ihre gesamte Solarenergie-Infrastruktur von einem zentralen Dashboard aus. 
                            Übersichtliche Darstellung aller wichtigen Kennzahlen und Funktionen.
                        </p>
                        <ul class="showcase-features">
                            <li>Echtzeit-Überwachung aller Anlagen</li>
                            <li>Automatisierte Berichte und Analysen</li>
                            <li>Integrierte Kommunikationstools</li>
                            <li>Mobile App für unterwegs</li>
                            <li>API-Integration für Drittsysteme</li>
                        </ul>
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Dashboard erkunden
                        </a>
                    </div>
                    <div class="showcase-visual">
                        <div class="dashboard-preview">
                            <div class="dashboard-header">
                                VoltMaster Dashboard
                            </div>
                            <div class="dashboard-content">
                                <div class="dashboard-card">
                                    <h4>Gesamtleistung</h4>
                                    <div class="value">2.4 MW</div>
                                </div>
                                <div class="dashboard-card">
                                    <h4>Aktive Anlagen</h4>
                                    <div class="value">127</div>
                                </div>
                                <div class="dashboard-card">
                                    <h4>Heute erzeugt</h4>
                                    <div class="value">18.5 MWh</div>
                                </div>
                                <div class="dashboard-card">
                                    <h4>Effizienz</h4>
                                    <div class="value">94.2%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <div class="container">
                <h2 class="section-title" style="color: white; margin-bottom: 4rem;">Vertrauen Sie auf Erfahrung</h2>
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

        <!-- Testimonials Section -->
        <section class="testimonials">
            <div class="container">
                <h2 class="section-title scroll-animate">Was unsere Kunden sagen</h2>
                <p class="section-subtitle scroll-animate">Erfahrungen von Unternehmen, die bereits auf VoltMaster vertrauen</p>
                
                <div class="testimonials-grid">
                    <div class="testimonial-card scroll-animate">
                        <p class="testimonial-quote">
                            "VoltMaster hat unsere Solarenergie-Verwaltung revolutioniert. Die Effizienzsteigerung von 25% 
                            hat sich bereits nach wenigen Monaten bezahlt gemacht."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">MS</div>
                            <div class="author-info">
                                <h4>Michael Schmidt</h4>
                                <p>Geschäftsführer, SolarTech GmbH</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card scroll-animate">
                        <p class="testimonial-quote">
                            "Die automatisierte Abrechnung spart uns wöchentlich 20 Stunden Arbeitszeit. 
                            Das Dashboard ist intuitiv und bietet alle Informationen auf einen Blick."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">AK</div>
                            <div class="author-info">
                                <h4>Anna Krüger</h4>
                                <p>Projektleiterin, GreenEnergy Solutions</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card scroll-animate">
                        <p class="testimonial-quote">
                            "Dank der präzisen Vorhersagen können wir unsere Wartungszyklen optimal planen 
                            und ungeplante Ausfälle um 90% reduzieren."
                        </p>
                        <div class="testimonial-author">
                            <div class="author-avatar">TW</div>
                            <div class="author-info">
                                <h4>Thomas Weber</h4>
                                <p>Technischer Leiter, Renewable Power AG</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Bereit für die Zukunft?</h2>
                    <p>
                        Starten Sie noch heute mit VoltMaster und revolutionieren Sie Ihr Solarenergie-Management. 
                        Kostenlose Demo verfügbar.
                    </p>
                    <div class="cta-buttons">
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Kostenlos testen
                        </a>
                        <a href="mailto:info@voltmaster.de" class="btn btn-secondary">
                            Demo vereinbaren
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
                        <h3>VoltMaster</h3>
                        <p>
                            Die führende Plattform für intelligentes Solarenergie-Management. 
                            Maximieren Sie Ihre Effizienz und minimieren Sie Ihre Kosten.
                        </p>
                    </div>
                    <div class="footer-section">
                        <h3>Funktionen</h3>
                        <p><a href="#features">Echtzeit-Monitoring</a></p>
                        <p><a href="#features">Intelligente Analytik</a></p>
                        <p><a href="#features">Automatisierte Abrechnung</a></p>
                        <p><a href="#features">Projektmanagement</a></p>
                    </div>
                    <div class="footer-section">
                        <h3>Unternehmen</h3>
                        <p><a href="#">Über uns</a></p>
                        <p><a href="#">Karriere</a></p>
                        <p><a href="#">Presse</a></p>
                        <p><a href="#">Partner</a></p>
                    </div>
                    <div class="footer-section">
                        <h3>Support</h3>
                        <p><a href="#">Dokumentation</a></p>
                        <p><a href="#">API</a></p>
                        <p><a href="#">Status</a></p>
                        <p><a href="mailto:support@voltmaster.de">Kontakt</a></p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; {{ date('Y') }} VoltMaster. Alle Rechte vorbehalten. | 
                    <a href="#">Datenschutz</a> | <a href="#">Impressum</a> | <a href="#">AGB</a></p>
                </div>
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

            // Cool Mouse Animation - Interactive Cursor with Particles
            class InteractiveCursor {
                constructor() {
                    this.cursor = null;
                    this.cursorInner = null;
                    this.particles = [];
                    this.mouseX = 0;
                    this.mouseY = 0;
                    this.isMoving = false;
                    this.init();
                }

                init() {
                    // Create custom cursor
                    this.cursor = document.createElement('div');
                    this.cursor.className = 'custom-cursor';
                    document.body.appendChild(this.cursor);

                    this.cursorInner = document.createElement('div');
                    this.cursorInner.className = 'custom-cursor-inner';
                    document.body.appendChild(this.cursorInner);

                    // Hide default cursor
                    document.body.style.cursor = 'none';

                    // Add event listeners
                    document.addEventListener('mousemove', this.onMouseMove.bind(this));
                    document.addEventListener('mousedown', this.onMouseDown.bind(this));
                    document.addEventListener('mouseup', this.onMouseUp.bind(this));

                    // Add hover effects for interactive elements
                    const interactiveElements = document.querySelectorAll('a, button, .btn');
                    interactiveElements.forEach(el => {
                        el.addEventListener('mouseenter', () => this.onHover(true));
                        el.addEventListener('mouseleave', () => this.onHover(false));
                    });

                    // Start animation loop
                    this.animate();
                }

                onMouseMove(e) {
                    this.mouseX = e.clientX;
                    this.mouseY = e.clientY;
                    this.isMoving = true;

                    // Create particle trail
                    if (Math.random() < 0.3) {
                        this.createParticle(this.mouseX, this.mouseY);
                    }

                    // Clear moving timeout
                    clearTimeout(this.movingTimeout);
                    this.movingTimeout = setTimeout(() => {
                        this.isMoving = false;
                    }, 100);
                }

                onMouseDown() {
                    this.cursor.classList.add('clicking');
                    this.cursorInner.classList.add('clicking');
                    
                    // Create burst of particles
                    for (let i = 0; i < 8; i++) {
                        this.createParticle(this.mouseX, this.mouseY, true);
                    }
                }

                onMouseUp() {
                    this.cursor.classList.remove('clicking');
                    this.cursorInner.classList.remove('clicking');
                }

                onHover(isHovering) {
                    if (isHovering) {
                        this.cursor.classList.add('hovering');
                        this.cursorInner.classList.add('hovering');
                    } else {
                        this.cursor.classList.remove('hovering');
                        this.cursorInner.classList.remove('hovering');
                    }
                }

                createParticle(x, y, burst = false) {
                    const particle = document.createElement('div');
                    particle.className = 'cursor-particle';
                    
                    const size = Math.random() * 4 + 2;
                    const angle = burst ? (Math.random() * 360) : (Math.random() * 60 - 30);
                    const velocity = burst ? (Math.random() * 3 + 2) : (Math.random() * 1 + 0.5);
                    const life = burst ? 60 : 30;

                    particle.style.cssText = `
                        position: fixed;
                        left: ${x}px;
                        top: ${y}px;
                        width: ${size}px;
                        height: ${size}px;
                        background: radial-gradient(circle, #ffd700, #ffed4e);
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9999;
                        box-shadow: 0 0 ${size * 2}px rgba(255, 215, 0, 0.6);
                    `;

                    document.body.appendChild(particle);

                    this.particles.push({
                        element: particle,
                        x: x,
                        y: y,
                        vx: Math.cos(angle * Math.PI / 180) * velocity,
                        vy: Math.sin(angle * Math.PI / 180) * velocity,
                        life: life,
                        maxLife: life,
                        size: size
                    });
                }

                animate() {
                    // Update cursor position
                    this.cursor.style.left = this.mouseX + 'px';
                    this.cursor.style.top = this.mouseY + 'px';
                    this.cursorInner.style.left = this.mouseX + 'px';
                    this.cursorInner.style.top = this.mouseY + 'px';

                    // Update particles
                    this.particles.forEach((particle, index) => {
                        particle.life--;
                        particle.x += particle.vx;
                        particle.y += particle.vy;
                        particle.vy += 0.1; // gravity

                        const opacity = particle.life / particle.maxLife;
                        const scale = opacity;

                        particle.element.style.left = particle.x + 'px';
                        particle.element.style.top = particle.y + 'px';
                        particle.element.style.opacity = opacity;
                        particle.element.style.transform = `scale(${scale})`;

                        if (particle.life <= 0) {
                            particle.element.remove();
                            this.particles.splice(index, 1);
                        }
                    });

                    requestAnimationFrame(this.animate.bind(this));
                }
            }

            // Initialize cursor when page loads
            window.addEventListener('load', () => {
                new InteractiveCursor();
            });
        </script>
    </body>
</html>
