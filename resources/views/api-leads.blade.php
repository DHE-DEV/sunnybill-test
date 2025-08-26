<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VoltMaster - Lead API Dokumentation</title>
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

            /* Hero Section */
            .hero {
                min-height: 60vh;
                background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), 
                           url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
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
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .tagline {
                font-size: 1.3rem;
                font-weight: 300;
                margin-bottom: 1rem;
                opacity: 0.95;
            }

            .version-info {
                background: rgba(255, 255, 255, 0.1);
                padding: 15px 25px;
                border-radius: 25px;
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                display: inline-block;
                margin-top: 1rem;
            }

            .version-info strong {
                color: #ffd700;
            }

            /* Documentation Content */
            .documentation {
                padding: 4rem 2rem;
                background: #f8fafc;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .doc-container {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
                overflow: hidden;
            }

            .doc-content {
                padding: 3rem;
            }

            /* Typography */
            h1, h2, h3 { 
                color: #1a202c; 
                margin-top: 2em;
                margin-bottom: 1em;
            }
            
            h1 { 
                font-size: 2.5em; 
                border-bottom: 3px solid #f53003; 
                padding-bottom: 15px;
                margin-top: 0;
            }
            h2 { 
                font-size: 2em; 
                border-bottom: 2px solid #f53003; 
                padding-bottom: 10px; 
            }
            h3 { 
                font-size: 1.5em; 
                color: #2d3748;
                margin-top: 1.5em;
            }

            /* Table of Contents */
            .toc {
                background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
                padding: 2rem;
                border-radius: 15px;
                border-left: 5px solid #f53003;
                margin: 2rem 0;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            }
            
            .toc h2 { 
                margin-top: 0; 
                border: none; 
                color: #1a202c;
                font-size: 1.5em;
            }
            .toc ol { margin: 0; }
            .toc li { margin: 8px 0; }
            .toc a { 
                color: #f53003; 
                text-decoration: none; 
                font-weight: 500;
                transition: color 0.3s ease;
            }
            .toc a:hover { 
                color: #d42a02;
                text-decoration: underline; 
            }

            /* Feature Cards */
            .feature-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin: 2rem 0;
            }
            
            .feature-card {
                background: linear-gradient(135deg, #f53003, #ff6b35);
                color: white;
                padding: 1.5rem;
                border-radius: 15px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(245, 48, 3, 0.2);
                transition: transform 0.3s ease;
            }

            .feature-card:hover {
                transform: translateY(-5px);
            }
            
            .feature-card h3 { 
                color: white; 
                margin: 0 0 0.5rem 0; 
                font-size: 1.2em;
            }

            .feature-card p {
                margin: 0;
                opacity: 0.9;
            }

            /* Endpoints */
            .endpoint {
                background: #f8fafc;
                border-left: 5px solid #00ba88;
                padding: 1.5rem;
                margin: 1.5rem 0;
                border-radius: 0 15px 15px 0;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            }
            
            .method {
                display: inline-block;
                padding: 6px 12px;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                margin-right: 10px;
                font-size: 0.9em;
                text-transform: uppercase;
            }
            
            .method-post { background: #00ba88; }
            .method-get { background: #0066cc; }
            .method-put { background: #ff9500; color: #333; }
            .method-delete { background: #ff3b30; }
            .method-patch { background: #8e44ad; }

            /* Code Blocks */
            code {
                background: #f1f5f9;
                padding: 3px 8px;
                border-radius: 6px;
                font-family: "SF Mono", Monaco, Consolas, "Roboto Mono", monospace;
                font-size: 0.9em;
                color: #f53003;
                font-weight: 500;
            }
            
            pre {
                background: #1a202c;
                color: #e2e8f0;
                padding: 1.5rem;
                border-radius: 10px;
                overflow-x: auto;
                margin: 1.5rem 0;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            
            pre code {
                background: none;
                color: inherit;
                padding: 0;
                font-size: 0.9em;
            }

            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 1.5rem 0;
                background: white;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                border-radius: 10px;
                overflow: hidden;
            }
            
            th, td {
                padding: 15px 20px;
                text-align: left;
                border-bottom: 1px solid #e2e8f0;
            }
            
            th {
                background: #f53003;
                color: white;
                font-weight: 600;
            }
            
            tr:nth-child(even) { background: #f8fafc; }
            tr:hover { background: #fff5f5; }

            /* Alert Boxes */
            .warning, .info, .success {
                padding: 1.5rem;
                margin: 1.5rem 0;
                border-radius: 10px;
                border-left: 5px solid;
            }
            
            .warning {
                background: #fff8e1;
                border-left-color: #ff9500;
            }
            
            .info {
                background: #e3f2fd;
                border-left-color: #0066cc;
            }
            
            .success {
                background: #e8f5e8;
                border-left-color: #00ba88;
            }

            /* Lists */
            ul, ol { 
                padding-left: 2em; 
                margin: 16px 0; 
            }
            li { 
                margin: 8px 0; 
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
                    font-size: 2rem;
                }

                .tagline {
                    font-size: 1.1rem;
                }

                .doc-content {
                    padding: 2rem;
                }

                .feature-grid {
                    grid-template-columns: 1fr;
                }

                h1 { font-size: 2rem; }
                h2 { font-size: 1.5rem; }
                
                table {
                    font-size: 0.9rem;
                }
                
                th, td {
                    padding: 10px;
                }
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
        </style>
    </head>
    <body>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="logo">VoltMaster Lead API</h1>
                <p class="tagline">Integrationsleitfaden für externe Anbieter</p>
                <div class="version-info">
                    <strong>Version 1.0</strong> | August 2025<br>
                    Base URL: <code style="background: rgba(255,255,255,0.2); color: #ffd700;">https://voltmaster.cloud/api/app</code>
                </div>
            </div>
        </section>

        <!-- Documentation Content -->
        <section class="documentation">
            <div class="container">
                <div class="doc-container">
                    <div class="doc-content">
                        <div class="toc scroll-animate">
                            <h2>Inhaltsverzeichnis</h2>
                            <ol>
                                <li><a href="#overview">Übersicht</a></li>
                                <li><a href="#auth">Authentifizierung</a></li>
                                <li><a href="#endpoints">API-Endpoints</a></li>
                                <li><a href="#data">Datenstrukturen</a></li>
                                <li><a href="#examples">Beispiele</a></li>
                                <li><a href="#errors">Fehlerbehandlung</a></li>
                                <li><a href="#limits">Rate Limiting</a></li>
                                <li><a href="#support">Support & Kontakt</a></li>
                            </ol>
                        </div>

                        <h2 id="overview" class="scroll-animate">Übersicht</h2>
                        
                        <p class="scroll-animate">Die VoltMaster Lead-Management API ermöglicht es externen Systemen, Leads zu erstellen, zu verwalten und zu verfolgen. Alle Leads werden sicher in unserem System gespeichert und können über das Admin-Panel verwaltet werden.</p>

                        <div class="feature-grid scroll-animate">
                            <div class="feature-card">
                                <h3><i class="fas fa-plus-circle" style="margin-right: 8px;"></i>Lead-Erstellung</h3>
                                <p>Sichere Erstellung neuer Leads</p>
                            </div>
                            <div class="feature-card">
                                <h3><i class="fas fa-cogs" style="margin-right: 8px;"></i>CRUD-Operationen</h3>
                                <p>Vollständige Verwaltung</p>
                            </div>
                            <div class="feature-card">
                                <h3><i class="fas fa-star" style="margin-right: 8px;"></i>A-E Qualifizierung</h3>
                                <p>Lead-Ranking System</p>
                            </div>
                            <div class="feature-card">
                                <h3><i class="fas fa-exchange-alt" style="margin-right: 8px;"></i>Konvertierung</h3>
                                <p>Lead zu Kunde</p>
                            </div>
                        </div>

                        <h2 id="auth" class="scroll-animate">Authentifizierung</h2>

                        <div class="info scroll-animate">
                            <strong><i class="fas fa-key" style="margin-right: 8px;"></i>API-Token erforderlich:</strong> Um die API zu nutzen, benötigen Sie einen API-Token mit entsprechenden Berechtigungen.
                        </div>

                        <h3 class="scroll-animate">Token anfordern</h3>
                        <p class="scroll-animate"><strong>Kontaktieren Sie uns zur Token-Erstellung:</strong></p>
                        <ul class="scroll-animate">
                            <li>E-Mail: <code>voltmaster.saas@gmail.com</code></li>
                        </ul>

                        <h3 class="scroll-animate">Token verwenden</h3>
                        <pre class="scroll-animate"><code>Authorization: Bearer YOUR_API_TOKEN_HERE
Content-Type: application/json</code></pre>

                        <h3 class="scroll-animate">Verfügbare Berechtigungen</h3>
                        <table class="scroll-animate">
                            <tr><th>Berechtigung</th><th>Beschreibung</th></tr>
                            <tr><td><code>leads:create</code></td><td>Leads erstellen</td></tr>
                            <tr><td><code>leads:read</code></td><td>Leads anzeigen/auflisten</td></tr>
                            <tr><td><code>leads:update</code></td><td>Leads bearbeiten</td></tr>
                            <tr><td><code>leads:delete</code></td><td>Leads löschen</td></tr>
                            <tr><td><code>leads:status</code></td><td>Lead-Status ändern</td></tr>
                            <tr><td><code>leads:convert</code></td><td>Leads zu Kunden konvertieren</td></tr>
                        </table>

                        <h2 id="endpoints" class="scroll-animate">API-Endpoints</h2>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-post">POST</span>/leads</h3>
                            <p><strong>Beschreibung:</strong> Erstellt einen neuen Lead im System.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:create</code></p>
                            
                            <h4>Request Body (Minimum):</h4>
                            <pre><code>{
  "name": "Firmenname"
}</code></pre>

                            <h4>Request Body (Vollständig):</h4>
                            <pre><code>{
  "name": "Beispiel GmbH & Co. KG",
  "contact_person": "Max Mustermann",
  "department": "Geschäftsführung",
  "email": "kontakt@beispiel.de",
  "phone": "+49 30 12345678",
  "website": "https://www.beispiel.de",
  "street": "Musterstraße 123",
  "address_line_2": "2. OG, Büro 42",
  "postal_code": "10115",
  "city": "Berlin",
  "state": "Berlin",
  "country": "Deutschland",
  "country_code": "DE",
  "ranking": "A",
  "notes": "Interessanter Lead mit großem Potenzial",
  "is_active": true
}</code></pre>
                        </div>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-get">GET</span>/leads</h3>
                            <p><strong>Beschreibung:</strong> Ruft eine paginierte Liste aller Leads ab.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:read</code></p>
                            
                            <h4>Query Parameter:</h4>
                            <ul>
                                <li><code>page</code> (int): Seitennummer (Standard: 1)</li>
                                <li><code>per_page</code> (int): Einträge pro Seite (Max: 100, Standard: 15)</li>
                                <li><code>search</code> (string): Suchbegriff</li>
                                <li><code>ranking</code> (string): Filter nach Ranking (A, B, C, D, E)</li>
                                <li><code>city</code> (string): Filter nach Stadt</li>
                                <li><code>is_active</code> (boolean): Filter nach Status</li>
                            </ul>
                        </div>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-get">GET</span>/leads/{id}</h3>
                            <p><strong>Beschreibung:</strong> Ruft Details eines spezifischen Leads ab.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:read</code></p>
                        </div>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-put">PUT</span>/leads/{id}</h3>
                            <p><strong>Beschreibung:</strong> Aktualisiert die Daten eines bestehenden Leads.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:update</code></p>
                        </div>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-delete">DELETE</span>/leads/{id}</h3>
                            <p><strong>Beschreibung:</strong> Löscht einen Lead aus dem System.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:delete</code></p>
                        </div>

                        <div class="endpoint scroll-animate">
                            <h3><span class="method method-patch">PATCH</span>/leads/{id}/convert-to-customer</h3>
                            <p><strong>Beschreibung:</strong> Konvertiert einen Lead in einen Kunden.</p>
                            <p><strong>Erforderliche Berechtigung:</strong> <code>leads:convert</code></p>
                        </div>

                        <h2 id="data" class="scroll-animate">Datenstrukturen</h2>

                        <h3 class="scroll-animate">Lead-Rankings</h3>
                        <table class="scroll-animate">
                            <tr><th>Code</th><th>Beschreibung</th><th>Bedeutung</th></tr>
                            <tr><td><strong>A</strong></td><td>Heißer Lead</td><td>Sehr interessiert, hohe Abschlusswahrscheinlichkeit</td></tr>
                            <tr><td><strong>B</strong></td><td>Warmer Lead</td><td>Interessiert, mittlere Abschlusswahrscheinlichkeit</td></tr>
                            <tr><td><strong>C</strong></td><td>Kalter Lead</td><td>Wenig Interesse, niedrige Priorität</td></tr>
                            <tr><td><strong>D</strong></td><td>Unqualifiziert</td><td>Lead muss noch qualifiziert werden</td></tr>
                            <tr><td><strong>E</strong></td><td>Nicht interessiert</td><td>Kein Interesse, Follow-up nicht empfohlen</td></tr>
                        </table>

                        <h2 id="examples" class="scroll-animate">Beispiele</h2>

                        <h3 class="scroll-animate">Beispiel 1: Minimaler Lead</h3>
                        <pre class="scroll-animate"><code>curl -X POST https://voltmaster.cloud/api/app/leads \
  -H "Authorization: Bearer sb_abc123..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Acme Corporation"}'</code></pre>

                        <h3 class="scroll-animate">Beispiel 2: Vollständiger Lead</h3>
                        <pre class="scroll-animate"><code>curl -X POST https://voltmaster.cloud/api/app/leads \
  -H "Authorization: Bearer sb_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tech Solutions GmbH",
    "contact_person": "Anna Schmidt",
    "department": "Einkauf",
    "email": "anna.schmidt@tech-solutions.de",
    "phone": "+49 40 987654321",
    "website": "https://www.tech-solutions.de",
    "street": "Innovationsallee 42",
    "postal_code": "20095",
    "city": "Hamburg",
    "state": "Hamburg",
    "country": "Deutschland",
    "country_code": "DE",
    "ranking": "A",
    "notes": "Großes Potenzial für Solaranlagen-Projekt",
    "is_active": true
  }'</code></pre>

                        <h2 id="errors" class="scroll-animate">Fehlerbehandlung</h2>

                        <h3 class="scroll-animate">Standard HTTP-Statuscodes</h3>
                        <table class="scroll-animate">
                            <tr><th>Code</th><th>Beschreibung</th></tr>
                            <tr><td><code>200</code></td><td>Erfolgreich</td></tr>
                            <tr><td><code>201</code></td><td>Erfolgreich erstellt</td></tr>
                            <tr><td><code>400</code></td><td>Fehlerhafte Anfrage</td></tr>
                            <tr><td><code>401</code></td><td>Nicht authentifiziert</td></tr>
                            <tr><td><code>403</code></td><td>Berechtigung fehlt</td></tr>
                            <tr><td><code>404</code></td><td>Ressource nicht gefunden</td></tr>
                            <tr><td><code>422</code></td><td>Validierungsfehler</td></tr>
                            <tr><td><code>500</code></td><td>Serverfehler</td></tr>
                        </table>

                        <h3 class="scroll-animate">Fehler-Response Format</h3>
                        <pre class="scroll-animate"><code>{
  "success": false,
  "message": "Beschreibung des Fehlers",
  "errors": {
    "field_name": ["Spezifische Fehlermeldung"]
  }
}</code></pre>

                        <h2 id="limits" class="scroll-animate">Rate Limiting</h2>

                        <div class="warning scroll-animate">
                            <p><strong><i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>Rate Limits:</strong></p>
                            <ul>
                                <li><strong>Standard-Token:</strong> 1000 Anfragen pro Stunde</li>
                                <li><strong>Premium-Token:</strong> 5000 Anfragen pro Stunde</li>
                            </ul>
                            <p>Bei Überschreitung: Status <code>429 Too Many Requests</code></p>
                        </div>

                        <h2 id="support" class="scroll-animate">Support & Kontakt</h2>

                        <div class="success scroll-animate">
                            <h3><i class="fas fa-life-ring" style="margin-right: 8px;"></i>Technischer Support</h3>
                            <ul>
                                <li><strong>E-Mail:</strong> voltmaster.saas@gmail.com</li>
                                <li><strong>Support-Zeiten:</strong> Mo-Fr, 9:00-17:00 Uhr</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>VoltMaster API</h3>
                        <p>
                            Professionelle Lead-Management API für externe Integrationen. 
                            Maximieren Sie Ihre Effizienz mit unserer sicheren und zuverlässigen Plattform.
                        </p>
                    </div>
                    <div class="footer-section">
                        <h3>Funktionen</h3>
                        <p><a href="#overview">API Übersicht</a></p>
                        <p><a href="#auth">Authentifizierung</a></p>
                        <p><a href="#endpoints">Endpoints</a></p>
                        <p><a href="#examples">Beispiele</a></p>
                    </div>
                    <div class="footer-section">
                        <h3>Unternehmen</h3>
                        <p><a href="{{ url('/') }}">Startseite</a></p>
                        <p><a href="{{ config('app.url') }}/admin">Admin Panel</a></p>
                        <p><a href="mailto:voltmaster.saas@gmail.com">Kontakt</a></p>
                    </div>
                    <div class="footer-section">
                        <h3>Support</h3>
                        <p><a href="mailto:voltmaster.saas@gmail.com">Technischer Support</a></p>
                        <p><a href="#support">Support Zeiten</a></p>
                        <p><a href="#limits">Rate Limits</a></p>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; {{ date('Y') }} VoltMaster - dhe, Daniel Henninger. Alle Rechte vorbehalten.</p>
                    <p><em>Diese Dokumentation unterliegt der Verschwiegenheit und ist ausschließlich für autorisierte Partner bestimmt.</em></p>
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

            // Copy code block functionality
            document.querySelectorAll('pre').forEach(pre => {
                const button = document.createElement('button');
                button.innerHTML = '<i class="fas fa-copy"></i>';
                button.style.cssText = `
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: rgba(255,255,255,0.1);
                    color: #e2e8f0;
                    border: 1px solid rgba(255,255,255,0.2);
                    border-radius: 4px;
                    padding: 8px 12px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                `;
                
                button.addEventListener('click', () => {
                    const code = pre.querySelector('code').textContent;
                    navigator.clipboard.writeText(code).then(() => {
                        button.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-copy"></i>';
                        }, 2000);
                    });
                });
                
                button.addEventListener('mouseenter', () => {
                    button.style.background = 'rgba(255,255,255,0.2)';
                });
                
                button.addEventListener('mouseleave', () => {
                    button.style.background = 'rgba(255,255,255,0.1)';
                });
                
                pre.style.position = 'relative';
                pre.appendChild(button);
            });
        </script>
    </body>
</html>
