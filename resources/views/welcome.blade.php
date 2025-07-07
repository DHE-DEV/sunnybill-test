<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>VoltMaster - Solarenergie Management</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/three@0.152.2/build/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three-globe@2.27.3/dist/three-globe.min.js"></script>
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

            /* Technology Overview Section */
            .technology-overview {
                padding: 10rem 2rem 4rem;
                background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
                position: relative;
            }

            .tech-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 3rem;
                margin-top: 4rem;
            }

            .tech-card {
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

            .tech-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(135deg, #64748b, #94a3b8);
                transform: scaleX(0);
                transition: transform 0.4s ease;
            }

            .tech-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12);
            }

            .tech-card:hover::before {
                transform: scaleX(1);
            }

            .tech-icon {
                font-size: 3.5rem;
                margin-bottom: 1.5rem;
                display: block;
            }

            .tech-card h3 {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                color: #1a202c;
            }

            .tech-card p {
                color: #4a5568;
                line-height: 1.7;
                font-size: 1rem;
            }

            /* Features Section - Stripe Style */
            .features {
                padding: 8rem 2rem;
                background: linear-gradient(to right, #1a202c, rgba(26, 32, 44, 0.3));
                position: relative;
                overflow: hidden;
            }

            .features .globe-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
                #opacity: 0.2;
            }

            .features .globe-loading {
                color: rgba(26, 32, 44, 0.6);
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

            .features .section-title {
                color: white !important;
                background: none !important;
                -webkit-background-clip: unset !important;
                -webkit-text-fill-color: white !important;
                background-clip: unset !important;
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

            .section-subtitle-white {
                text-align: center;
                font-size: 1.2rem;
                color:rgb(255, 255, 255);
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
                padding: 6rem 2rem;
                background: linear-gradient(rgba(248, 250, 252, 0.9), rgba(226, 232, 240, 0.9)), 
                           url('') center/cover no-repeat;
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

            /* Billing System Section */
            .billing-system {
                padding: 6rem 2rem;
                background: linear-gradient(rgba(226, 232, 240, 0.9), rgba(248, 250, 252, 0.9));
                position: relative;
            }

            .billing-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                position: relative;
                z-index: 2;
            }

            .billing-visual {
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

            .billing-text h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                line-height: 1.2;
                color: #1a202c;
            }

            .billing-text p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                color: #4a5568;
                line-height: 1.7;
            }

            .billing-features {
                list-style: none;
                margin-bottom: 2rem;
            }

            .billing-features li {
                padding: 0.5rem 0;
                display: flex;
                align-items: center;
                font-size: 1rem;
                color: #4a5568;
            }

            .billing-features li::before {
                content: '✓';
                color: #00ba88;
                font-weight: bold;
                margin-right: 1rem;
                font-size: 1.2rem;
            }

            /* Participation Management Section */
            .participation-management {
                padding: 6rem 2rem;
                background: linear-gradient(rgba(248, 250, 252, 0.9), rgba(226, 232, 240, 0.9));
                position: relative;
            }

            .participation-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 4rem;
                align-items: center;
                position: relative;
                z-index: 2;
            }

            .participation-visual {
                position: relative;
                height: 550px;
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

            .participation-text h2 {
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                line-height: 1.2;
                color: #1a202c;
            }

            .participation-text p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
                color: #4a5568;
                line-height: 1.7;
            }

            .participation-features {
                list-style: none;
                margin-bottom: 2rem;
            }

            .participation-features li {
                padding: 0.5rem 0;
                display: flex;
                align-items: center;
                font-size: 1rem;
                color: #4a5568;
            }

            .participation-features li::before {
                content: '✓';
                color: #00ba88;
                font-weight: bold;
                margin-right: 1rem;
                font-size: 1.2rem;
            }

            /* Product Showcase Section */
            .product-showcase {
                padding: 8rem 2rem;
                background: linear-gradient(to right, rgba(102, 126, 234, 0.95), rgba(118, 75, 162, 0.6), rgba(118, 75, 162, 0.1)), 
                           url('https://images.unsplash.com/photo-1677442136019-21780ecad995?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
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

            /* Globe Container */
            .globe-container {
                width: 100%;
                height: 100%;
                position: relative;
                #border-radius: 20px;
                overflow: hidden;
            }

            #globe-canvas {
                width: 100%;
                height: 100%;
                display: block;
                #border-radius: 20px;
            }

            /* Globe Loading Animation */
            .globe-loading {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: rgba(255, 255, 255, 0.8);
                font-size: 1.1rem;
                font-weight: 500;
                z-index: 10;
            }

            .globe-loading::after {
                content: '';
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top-color: #ffd700;
                animation: spin 1s ease-in-out infinite;
                margin-left: 10px;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            /* Globe Points Animation */
            .globe-point {
                position: absolute;
                width: 4px;
                height: 4px;
                background: #ffd700;
                border-radius: 50%;
                box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
                animation: pulse 2s ease-in-out infinite;
            }

            @keyframes pulse {
                0%, 100% { 
                    opacity: 0.6;
                    transform: scale(1);
                }
                50% { 
                    opacity: 1;
                    transform: scale(1.5);
                }
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
                <p class="tagline">KI-gestütztes Solarenergie-Management</p>
                <p class="description">
                    Revolutionäre Plattform mit automatischer Abrechnung, intelligenter Dokumentenverwaltung 
                    und KI-gestützter Kostenaufteilung für maximale Effizienz in Ihrem Solarenergie-Business.
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

        <!-- Technology Overview Section -->
        <section class="technology-overview">
            <div class="container">
                <h2 class="section-title scroll-animate">Modernste Technologie für Ihr Business</h2>
                <p class="section-subtitle scroll-animate">
                    VoltMaster kombiniert bewährte Technologien mit innovativen KI-Algorithmen für maximale Effizienz
                </p>
                
                <div class="tech-grid">
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">🔒</div>
                        <h3>Enterprise Security</h3>
                        <p>
                            Bank-Level Verschlüsselung und mehrstufige Authentifizierung schützen Ihre sensiblen Daten. 
                            DSGVO-konforme Datenhaltung in deutschen Rechenzentren.
                        </p>
                    </div>
                    
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">⚡</div>
                        <h3>High Performance</h3>
                        <p>
                            Cloud-native Architektur mit automatischer Skalierung. 99.9% Verfügbarkeit und 
                            Antwortzeiten unter 200ms für optimale Benutzererfahrung.
                        </p>
                    </div>
                    
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">🔄</div>
                        <h3>Nahtlose Integration</h3>
                        <p>
                            REST-APIs und Webhooks für einfache Integration in bestehende Systeme. 
                            Unterstützung für SAP, Lexoffice, DATEV und weitere ERP-Systeme.
                        </p>
                    </div>
                    
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">📊</div>
                        <h3>Real-time Analytics</h3>
                        <p>
                            Live-Dashboards mit Machine Learning-basierten Vorhersagen. 
                            Automatische Anomalie-Erkennung und proaktive Wartungsempfehlungen.
                        </p>
                    </div>
                    
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">🌍</div>
                        <h3>Global verfügbar</h3>
                        <p>
                            Multi-Region Deployment mit lokaler Datenhaltung. 
                            24/7 Support in deutscher Sprache und weltweite Verfügbarkeit.
                        </p>
                    </div>
                    
                    <div class="tech-card scroll-animate">
                        <div class="tech-icon">🚀</div>
                        <h3>Continuous Innovation</h3>
                        <p>
                            Regelmäßige Updates mit neuen Features. KI-Modelle werden kontinuierlich 
                            mit Ihren Daten trainiert für bessere Vorhersagen.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="globe-container">
                <div class="globe-loading">Lade Weltkugel...</div>
                <canvas id="globe-canvas"></canvas>
            </div>
            <div class="container" style="position: relative; z-index: 2;">
                <h2 class="section-title scroll-animate" style="color: white;">Kernfunktionen</h2>
                <p class="section-subtitle scroll-animate" style="color: white;">Professionelle Lösungen für Ihr Solarenergie-Management</p>
                
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
                            <img src="/storage/customers/customer_stat_4.png" alt="Kundenstatistik Screenshot">
                    </div>
                </div>
            </div>
        </section>

        <!-- Billing System Section -->
        <section class="billing-system">
            <div class="container">
                <div class="billing-content">
                    <div class="billing-visual">
                        <div class="dashboard-screenshot">
                            <img src="/storage/customers/ki_billing_1.png" alt="Abrechnungssystem Screenshot">
                        </div>
                    </div>
                    <div class="billing-text">
                        <h2>KI-gestütztes Abrechnungssystem</h2>
                        <p>
                            Revolutionieren Sie Ihre Abrechnungsprozesse mit unserem intelligenten System. 
                            Automatische Kostenaufteilung, präzise Positionsgenerierung und nahtlose Integration 
                            in Ihre bestehenden Workflows.
                        </p>
                        <ul class="billing-features">
                            <li>Automatische Kostenaufteilung durch KI-Algorithmen</li>
                            <li>Intelligente Positionsgenerierung mit bis zu 6 Nachkommastellen</li>
                            <li>ZUGFeRD-konforme Rechnungsstellung</li>
                            <li>Automatische Gutschriftenerstellung</li>
                            <li>Echtzeit-Abrechnungsübersichten und Analysen</li>
                        </ul>
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Abrechnungssystem testen
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Participation Management Section -->
        <section class="participation-management">
            <div class="container">
                <div class="participation-content">
                    <div class="participation-text">
                        <h2>Intelligentes Beteiligungsmanagement</h2>
                        <p>
                            Verwalten Sie mühelos alle Beteiligungen an Ihren Solaranlagen. Automatische Ertragsverteilung, 
                            transparente Abrechnungen und präzise Verwaltung von Firmen- und Privatkundenbeteiligungen 
                            in einem zentralen System.
                        </p>
                        <ul class="participation-features">
                            <li>Automatische Ertragsverteilung nach Beteiligungsquoten</li>
                            <li>Separate Verwaltung von Firmen- und Privatkundenbeteiligungen</li>
                            <li>Transparente Abrechnungsübersichten für alle Beteiligten</li>
                            <li>Flexible Beteiligungsstrukturen und Anpassungen</li>
                            <li>Integrierte Dokumentenverwaltung für Beteiligungsverträge</li>
                        </ul>
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Beteiligungsmanagement erkunden
                        </a>
                    </div>
                    <div class="participation-visual">
                        <div class="dashboard-screenshot">
                            <img src="/storage/customers/beteiligung_2.png" alt="Beteiligungsmanagement Screenshot">
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
                            <li>KI-gestützte Kostenaufteilung und Positionsgenerierung</li>
                            <li>Automatisches Abrechnungsmanagement mit KI-Unterstützung</li>
                            <li>Beteiligungsverwaltung für Firmen und Privatkunden</li>
                            <li>Integriertes Aufgaben- und Dokumentenmanagement</li>
                            <li>Präzise Artikelverwaltung bis zu 6 Nachkommastellen</li>
                        </ul>
                        <a href="{{ config('app.url') }}/admin" class="btn btn-primary">
                            Dashboard erkunden
                        </a>
                    </div>
                    <div class="_showcase-visual">
                        
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <div class="container">
                <div class="cta-content">
                    <h2 class="section-title">Warum VoltMaster?</h2>
                    <p class="section-subtitle-white">
                        Über 50 Unternehmen vertrauen auf unsere Plattform für professionelles Solarenergie-Management.
                    </p>
                </div>

                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">65+</div>
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

                    // Create particle trail - öfter und mit mehr Bewegung
                    if (Math.random() < 0.7) {
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
                    
                    const size = Math.random() * 6 + 3;
                    // Deutlich weiter von links nach rechts - größerer Winkelbereich
                    const angle = burst ? (Math.random() * 360) : (Math.random() * 120 - 60);
                    // Höhere Geschwindigkeit für weitere Bewegung
                    const velocity = burst ? (Math.random() * 5 + 3) : (Math.random() * 3 + 1.5);
                    const life = burst ? 80 : 50;

                    particle.style.cssText = `
                        position: fixed;
                        left: ${x}px;
                        top: ${y}px;
                        width: ${size}px;
                        height: ${size}px;
                        background: radial-gradient(circle, #ffff00, #fff700);
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9999;
                        box-shadow: 0 0 ${size * 3}px rgba(255, 255, 0, 0.8);
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
                initGlobe();
            });

            // 3D Globe Animation - Professional Three-Globe Implementation
            function initGlobe() {
                const canvas = document.getElementById('globe-canvas');
                const loadingElement = document.querySelector('.globe-loading');
                
                if (!canvas) {
                    console.error('Globe canvas not found');
                    return;
                }

                if (!window.THREE) {
                    console.error('Three.js not loaded');
                    if (loadingElement) loadingElement.textContent = 'Fehler: Three.js nicht geladen';
                    return;
                }

                if (!window.ThreeGlobe) {
                    console.error('ThreeGlobe not loaded');
                    if (loadingElement) loadingElement.textContent = 'Fehler: ThreeGlobe nicht geladen';
                    return;
                }


                // Scene setup
                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(60, canvas.offsetWidth / canvas.offsetHeight, 0.1, 1000);
                camera.position.set(-100, -30, 200);

                const renderer = new THREE.WebGLRenderer({ 
                    canvas: canvas, 
                    antialias: true, 
                    alpha: true 
                });
                renderer.setSize(canvas.offsetWidth, canvas.offsetHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

                // Lighting
                const ambientLight = new THREE.AmbientLight(0xffffff, 1);
                scene.add(ambientLight);

                // Globe with Earth texture and many animated arcs in bright yellow
                const Globe = new ThreeGlobe()
                    .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-night.jpg')
                    .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
                    .arcsData([
                        // Viele Arcs von links nach rechts - Amerika nach Europa/Afrika
                        { startLat: 40.7128, startLng: -74.0060, endLat: 52.5200, endLng: 13.4050, color: '#ffd700' }, // NY -> Berlin
                        { startLat: 34.0522, startLng: -118.2437, endLat: 48.8566, endLng: 2.3522, color: '#ffd700' }, // LA -> Paris
                        { startLat: 41.8781, startLng: -87.6298, endLat: 51.5074, endLng: -0.1278, color: '#ffd700' }, // Chicago -> London
                        { startLat: 25.7617, startLng: -80.1918, endLat: 41.9028, endLng: 12.4964, color: '#ffd700' }, // Miami -> Rome
                        { startLat: 37.7749, startLng: -122.4194, endLat: 40.4168, endLng: -3.7038, color: '#ffd700' }, // SF -> Madrid
                        { startLat: 45.5017, startLng: -73.5673, endLat: 52.3676, endLng: 4.9041, color: '#ffd700' }, // Montreal -> Amsterdam
                        { startLat: 43.6532, startLng: -79.3832, endLat: 50.1109, endLng: 8.6821, color: '#ffd700' }, // Toronto -> Frankfurt
                        { startLat: 39.2904, startLng: -76.6122, endLat: 47.3769, endLng: 8.5417, color: '#ffd700' }, // Baltimore -> Zurich
                        
                        // Weitere Arcs von Westamerika nach Europa
                        { startLat: 47.6062, startLng: -122.3321, endLat: 59.9139, endLng: 10.7522, color: '#ffd700' }, // Seattle -> Oslo
                        { startLat: 36.1627, startLng: -86.7816, endLat: 55.6761, endLng: 12.5683, color: '#ffd700' }, // Nashville -> Copenhagen
                        { startLat: 32.7767, startLng: -96.7970, endLat: 52.2297, endLng: 21.0122, color: '#ffd700' }, // Dallas -> Warsaw
                        { startLat: 29.7604, startLng: -95.3698, endLat: 50.0755, endLng: 14.4378, color: '#ffd700' }, // Houston -> Prague
                        { startLat: 33.4484, startLng: -112.0740, endLat: 47.4979, endLng: 19.0402, color: '#ffd700' }, // Phoenix -> Budapest
                        { startLat: 39.7392, startLng: -104.9903, endLat: 44.4268, endLng: 26.1025, color: '#ffd700' }, // Denver -> Bucharest
                        
                        // Südamerika nach Afrika/Europa
                        { startLat: -23.5505, startLng: -46.6333, endLat: -26.2041, endLng: 28.0473, color: '#ffd700' }, // São Paulo -> Johannesburg
                        { startLat: -34.6037, startLng: -58.3816, endLat: -33.9249, endLng: 18.4241, color: '#ffd700' }, // Buenos Aires -> Cape Town
                        { startLat: -12.0464, startLng: -77.0428, endLat: 6.5244, endLng: 3.3792, color: '#ffd700' }, // Lima -> Lagos
                        { startLat: 4.7110, startLng: -74.0721, endLat: 30.0444, endLng: 31.2357, color: '#ffd700' }, // Bogotá -> Cairo
                        
                        // Pazifik nach Asien
                        { startLat: 21.3099, startLng: -157.8581, endLat: 35.6895, endLng: 139.6917, color: '#ffd700' }, // Honolulu -> Tokyo
                        { startLat: 37.7749, startLng: -122.4194, endLat: 37.5665, endLng: 126.9780, color: '#ffd700' }, // SF -> Seoul
                        { startLat: 34.0522, startLng: -118.2437, endLat: 31.2304, endLng: 121.4737, color: '#ffd700' }, // LA -> Shanghai
                        { startLat: 47.6062, startLng: -122.3321, endLat: 39.9042, endLng: 116.4074, color: '#ffd700' }, // Seattle -> Beijing
                        
                        // Atlantik-Überquerungen
                        { startLat: 40.7128, startLng: -74.0060, endLat: 38.7223, endLng: -9.1393, color: '#ffd700' }, // NY -> Lisbon
                        { startLat: 42.3601, startLng: -71.0589, endLat: 53.3498, endLng: -6.2603, color: '#ffd700' }, // Boston -> Dublin
                        { startLat: 25.7617, startLng: -80.1918, endLat: 28.0339, endLng: -15.4151, color: '#ffd700' }, // Miami -> Tenerife
                    ])
                    .arcColor('color')
                    .arcAltitude(0.3)
                    .arcStroke(2)
                    .arcDashLength(0.2)
                    .arcDashGap(1)
                    .arcDashInitialGap(() => Math.random() * 3)
                    .arcDashAnimateTime(1600); // 50% langsamer (800 * 2)

                scene.add(Globe);

                // Manual rotation without OrbitControls
                let rotationSpeed = 0.00025; // 50% langsamer

                // Animation loop
                function animate() {
                    requestAnimationFrame(animate);
                    
                    // Rotate the globe
                    Globe.rotation.y += rotationSpeed;
                    
                    renderer.render(scene, camera);
                }

                // Handle resize
                function handleResize() {
                    const width = canvas.offsetWidth;
                    const height = canvas.offsetHeight;
                    
                    camera.aspect = width / height;
                    camera.updateProjectionMatrix();
                    renderer.setSize(width, height);
                }

                window.addEventListener('resize', handleResize);

                // Hide loading and start animation
                setTimeout(() => {
                    if (loadingElement) {
                        loadingElement.style.opacity = '0';
                        setTimeout(() => {
                            loadingElement.style.display = 'none';
                        }, 500);
                    }
                    animate();
                }, 1000);

                // Intersection Observer for performance
                const globeObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animate();
                        }
                    });
                }, { threshold: 0.1 });

                globeObserver.observe(canvas);
            }
        </script>
    </body>
</html>
