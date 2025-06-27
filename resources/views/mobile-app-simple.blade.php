<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#007aff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SunnyBill Mobile</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f2f2f7;
            color: #000;
            overflow-x: hidden;
        }
        
        /* Login Screen */
        .login-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .login-form {
            width: 100%;
            max-width: 320px;
            padding: 20px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .logo-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }
        
        .app-title {
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .app-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: white;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 16px;
            backdrop-filter: blur(20px);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.25);
        }
        
        .btn-primary {
            width: 100%;
            padding: 16px;
            background: white;
            color: #007aff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .demo-credentials {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .demo-credentials h4 {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .demo-credentials p {
            color: white;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 14px;
            margin: 4px 0;
        }
        
        /* Main App */
        .main-app {
            display: none;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        
        /* Header */
        .header {
            background: white;
            border-bottom: 1px solid #e5e5ea;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #007aff 0%, #5856d6 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header-icon svg {
            width: 18px;
            height: 18px;
            color: white;
        }
        
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        
        .header-subtitle {
            font-size: 14px;
            color: #8e8e93;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: #ff3b30;
            font-size: 14px;
            cursor: pointer;
            padding: 8px;
        }
        
        /* Content */
        .content {
            padding: 20px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 16px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            border-radius: 16px;
            padding: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #007aff 0%, #0056cc 100%);
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #34c759 0%, #248a3d 100%);
        }
        
        .stat-card.purple {
            background: linear-gradient(135deg, #5856d6 0%, #3634a3 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #ff9500 0%, #cc7700 100%);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Lists */
        .list {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .list-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid #f2f2f7;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item-media {
            margin-right: 16px;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .avatar.primary {
            background: linear-gradient(135deg, #007aff 0%, #0056cc 100%);
        }
        
        .avatar.green {
            background: linear-gradient(135deg, #34c759 0%, #248a3d 100%);
        }
        
        .list-item-content {
            flex: 1;
        }
        
        .list-item-title {
            font-size: 16px;
            font-weight: 600;
            color: #000;
            margin-bottom: 4px;
        }
        
        .list-item-subtitle {
            font-size: 14px;
            color: #8e8e93;
            margin-bottom: 2px;
        }
        
        .list-item-text {
            font-size: 13px;
            color: #8e8e93;
        }
        
        .list-item-after {
            margin-left: 16px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .badge.green {
            background-color: #34c759;
        }
        
        .badge.blue {
            background-color: #007aff;
        }
        
        .badge.yellow {
            background-color: #ff9500;
        }
        
        .badge.red {
            background-color: #ff3b30;
        }
        
        .badge.gray {
            background-color: #8e8e93;
        }
        
        /* Tab Bar */
        .tab-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e5ea;
            padding: 8px 0;
            padding-bottom: env(safe-area-inset-bottom, 8px);
            overflow-x: auto;
            overflow-y: hidden;
        }
        
        .tab-scroll {
            display: flex;
            min-width: max-content;
            padding: 0 8px;
        }
        
        .tab-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 12px;
            text-decoration: none;
            color: #8e8e93;
            transition: color 0.3s ease;
            min-width: 60px;
            flex-shrink: 0;
        }
        
        .tab-item.active {
            color: #007aff;
        }
        
        .tab-icon {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
        }
        
        .tab-label {
            font-size: 10px;
            font-weight: 500;
        }
        
        /* Pages */
        .page {
            display: none;
        }
        
        .page.active {
            display: block;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px 20px;
            color: #8e8e93;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #e5e5ea;
            border-top: 2px solid #007aff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Search */
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #e5e5ea;
            border-radius: 12px;
            background: white;
            font-size: 16px;
            color: #000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007aff;
            box-shadow: 0 2px 8px rgba(0, 122, 255, 0.2);
        }
        
        .search-input::placeholder {
            color: #8e8e93;
        }
        
        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8e8e93;
            font-size: 16px;
            pointer-events: none;
        }
        
        /* Customer Detail */
        .customer-detail-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e5e5ea;
            margin: -20px -20px 20px -20px;
        }
        
        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: none;
            border: none;
            color: #007aff;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 16px;
            padding: 8px 0;
        }
        
        .customer-detail-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .customer-detail-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007aff 0%, #0056cc 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }
        
        .detail-sections {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .detail-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .detail-section-header {
            background: #f8f9fa;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e5ea;
            font-weight: 600;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .detail-section-content {
            padding: 0;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            padding: 16px 20px;
            border-bottom: 1px solid #f2f2f7;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item-label {
            font-weight: 600;
            color: #8e8e93;
            min-width: 120px;
            font-size: 14px;
        }
        
        .detail-item-value {
            flex: 1;
            color: #000;
            font-size: 16px;
        }
        
        .detail-item-value.empty {
            color: #8e8e93;
            font-style: italic;
        }
        
        .section-badge {
            background: #007aff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Login Screen -->
        <div class="login-screen" id="login-screen">
            <div class="login-form">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z"/>
                        </svg>
                    </div>
                    <h1 class="app-title">SunnyBill</h1>
                    <p class="app-subtitle">Solar Management System</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-input" id="login-email" placeholder="demo@sunnybill.de" value="demo@sunnybill.de">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Passwort</label>
                    <input type="password" class="form-input" id="login-password" placeholder="demo123" value="demo123">
                </div>
                
                <button class="btn-primary" id="login-btn">
                    <span id="login-text">Anmelden</span>
                </button>
                
                <div class="demo-credentials">
                    <h4>Demo-Zugangsdaten:</h4>
                    <p>demo@sunnybill.de</p>
                    <p>demo123</p>
                </div>
            </div>
        </div>

        <!-- Main App -->
        <div class="main-app" id="main-app">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <div class="header-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="header-title" id="page-title">Dashboard</div>
                        <div class="header-subtitle" id="user-name">Benutzer</div>
                    </div>
                </div>
                <button class="logout-btn" id="logout-btn">Abmelden</button>
            </div>

            <!-- Dashboard Page -->
            <div class="page active" id="dashboard-page">
                <div class="content">
                    <div class="section-title">Übersicht</div>
                    <div class="stats-grid">
                        <div class="stat-card blue">
                            <div class="stat-value" id="stat-plants">0</div>
                            <div class="stat-label">Solaranlagen</div>
                        </div>
                        <div class="stat-card green">
                            <div class="stat-value" id="stat-customers">0</div>
                            <div class="stat-label">Kunden</div>
                        </div>
                        <div class="stat-card purple">
                            <div class="stat-value" id="stat-invoices">0</div>
                            <div class="stat-label">Rechnungen</div>
                        </div>
                        <div class="stat-card orange">
                            <div class="stat-value" id="stat-revenue">€0</div>
                            <div class="stat-label">Umsatz</div>
                        </div>
                    </div>
                    
                    <div class="section-title">Letzte Aktivitäten</div>
                    <div class="list" id="activities-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            Lade Aktivitäten...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Page -->
            <div class="page" id="customers-page">
                <div class="content">
                    <div class="section-title">Kunden</div>
                    <div class="search-container">
                        <input type="text" id="customers-search" placeholder="Kunden suchen..." class="search-input">
                        <div class="search-icon">🔍</div>
                    </div>
                    <div class="list" id="customers-list">
                        <div class="loading">
                            <div class="spinner"></div>
                            Lade Kunden...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Detail Page -->
            <div class="page" id="customer-detail-page">
                <div class="content">
                    <div class="customer-detail-header">
                        <button class="back-btn" id="back-to-customers">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                            </svg>
                            Zurück
                        </button>
                        <div class="customer-detail-info">
                            <div class="customer-detail-avatar" id="customer-detail-avatar">
                                <span id="customer-detail-initials">--</span>
                            </div>
                            <div>
                                <h2 id="customer-detail-name">Kunde</h2>
                                <p id="customer-detail-type">Kundentyp</p>
                            </div>
                        </div>
                    </div>

                    <div class="detail-sections" id="customer-detail-content">
                        <div class="loading">
                            <div class="spinner"></div>
                            Lade Kundendetails...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="tab-bar">
                <div class="tab-scroll">
                    <a href="#" class="tab-item active" data-page="dashboard">
                        <svg class="tab-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                        </svg>
                        <span class="tab-label">Dashboard</span>
                    </a>
                    <a href="#" class="tab-item" data-page="customers">
                        <svg class="tab-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="tab-label">Kunden</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script>
        // App State
        let isLoggedIn = false;
        let user = {};
        let stats = {};
        let currentPage = 'dashboard';
        let allCustomers = [];

        // Set CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        // Check if user is already logged in
        const token = localStorage.getItem('auth_token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
            checkAuth();
        }

        // Login functionality
        document.getElementById('login-btn').addEventListener('click', async function() {
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            const btn = this;
            const text = document.getElementById('login-text');

            btn.disabled = true;
            text.textContent = 'Anmelden...';

            try {
                const response = await axios.post('/api/mobile/login', {
                    email: email,
                    password: password
                });

                localStorage.setItem('auth_token', response.data.token);
                user = response.data.user;
                
                axios.defaults.headers.common['Authorization'] = 'Bearer ' + response.data.token;
                
                isLoggedIn = true;
                showMainApp();
                
                await loadDashboardData();
                
            } catch (error) {
                console.error('Login error:', error);
                alert(error.response?.data?.message || 'Anmeldung fehlgeschlagen');
            } finally {
                btn.disabled = false;
                text.textContent = 'Anmelden';
            }
        });

        // Logout functionality
        document.getElementById('logout-btn').addEventListener('click', async function() {
            try {
                await axios.post('/api/mobile/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                localStorage.removeItem('auth_token');
                delete axios.defaults.headers.common['Authorization'];
                isLoggedIn = false;
                user = {};
                showLoginScreen();
            }
        });

        // Tab navigation
        document.querySelectorAll('.tab-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                navigateTo(page);
            });
        });

        // Search functionality
        document.getElementById('customers-search').addEventListener('input', function(e) {
            filterCustomers(e.target.value);
        });

        // Back button for customer detail
        document.getElementById('back-to-customers').addEventListener('click', function() {
            navigateTo('customers');
        });

        async function checkAuth() {
            try {
                const response = await axios.get('/api/user');
                user = response.data;
                isLoggedIn = true;
                showMainApp();
                await loadDashboardData();
            } catch (error) {
                console.error('Auth check failed:', error);
                localStorage.removeItem('auth_token');
                delete axios.defaults.headers.common['Authorization'];
                showLoginScreen();
            }
        }

        function showLoginScreen() {
            document.getElementById('login-screen').style.display = 'flex';
            document.getElementById('main-app').style.display = 'none';
        }

        function showMainApp() {
            document.getElementById('login-screen').style.display = 'none';
            document.getElementById('main-app').style.display = 'block';
            updateUserInfo();
        }

        function updateUserInfo() {
            if (user.name) {
                document.getElementById('user-name').textContent = user.name;
            }
        }

        async function navigateTo(page) {
            if (currentPage === page) return;

            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById(page + '-page').classList.add('active');
            
            document.querySelectorAll('.tab-item').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('data-page') === page) {
                    link.classList.add('active');
                }
            });

            const titles = {
                'dashboard': 'Dashboard',
                'customers': 'Kunden'
            };
            document.getElementById('page-title').textContent = titles[page] || 'SunnyBill';

            currentPage = page;

            try {
                switch (page) {
                    case 'dashboard':
                        await loadDashboardData();
                        break;
                    case 'customers':
                        await loadCustomers();
                        break;
                }
            } catch (error) {
                console.error('Error loading ' + page + ':', error);
                alert('Fehler beim Laden der ' + page + ' Daten');
            }
        }

        async function loadDashboardData() {
            try {
                const [statsResponse, activitiesResponse] = await Promise.all([
                    axios.get('/api/mobile/stats'),
                    axios.get('/api/mobile/activities')
                ]);
                
                stats = statsResponse.data;
                const activities = activitiesResponse.data;
                
                document.getElementById('stat-plants').textContent = stats.solarPlants;
                document.getElementById('stat-customers').textContent = stats.customers;
                document.getElementById('stat-invoices').textContent = stats.invoices;
                document.getElementById('stat-revenue').textContent = '€' + formatCurrency(stats.revenue);
                
                updateActivitiesList(activities);
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        async function loadCustomers() {
            try {
                const response = await axios.get('/api/mobile/customers');
                allCustomers = response.data;
                updateCustomersList(allCustomers);
            } catch (error) {
                console.error('Error loading customers:', error);
            }
        }

        function updateActivitiesList(activities) {
            const list = document.getElementById('activities-list');
            
            if (activities.length === 0
) {
                list.innerHTML = '<div class="list-item"><div class="list-item-content"><div class="list-item-title">Keine Aktivitäten gefunden</div></div></div>';
                return;
            }

            let html = '';
            activities.forEach(function(activity) {
                html += '<div class="list-item">' +
                    '<div class="list-item-content">' +
                    '<div class="list-item-title">' + activity.title + '</div>' +
                    '<div class="list-item-subtitle">' + activity.description + '</div>' +
                    '<div class="list-item-text">' + activity.time + '</div>' +
                    '</div></div>';
            });
            list.innerHTML = html;
        }

        function updateCustomersList(customers) {
            const list = document.getElementById('customers-list');
            
            if (customers.length === 0) {
                list.innerHTML = '<div class="list-item"><div class="list-item-content"><div class="list-item-title">Keine Kunden gefunden</div></div></div>';
                return;
            }

            let html = '';
            customers.forEach(function(customer) {
                html += '<div class="list-item" onclick="showCustomerDetail(' + customer.id + ')" style="cursor: pointer;">' +
                    '<div class="list-item-media">' +
                    '<div class="avatar primary">' + customer.initials + '</div>' +
                    '</div>' +
                    '<div class="list-item-content">' +
                    '<div class="list-item-title">' + customer.name + '</div>' +
                    '<div class="list-item-subtitle">' + customer.email + '</div>' +
                    '<div class="list-item-text">' + (customer.phone || '') + (customer.city ? ' • ' + customer.city : '') + '</div>' +
                    '</div>' +
                    '<div class="list-item-after">' +
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="color: #8e8e93;">' +
                    '<path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>' +
                    '</svg>' +
                    '</div>' +
                    '</div>';
            });
            list.innerHTML = html;
        }

        function filterCustomers(searchTerm) {
            const filtered = allCustomers.filter(function(customer) {
                return customer.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    customer.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                    (customer.phone && customer.phone.toLowerCase().includes(searchTerm.toLowerCase())) ||
                    (customer.city && customer.city.toLowerCase().includes(searchTerm.toLowerCase()));
            });
            updateCustomersList(filtered);
        }

        async function showCustomerDetail(customerId) {
            try {
                document.getElementById('customer-detail-content').innerHTML = 
                    '<div class="loading"><div class="spinner"></div>Lade Kundendetails...</div>';

                document.querySelectorAll('.page').forEach(function(p) { p.classList.remove('active'); });
                document.getElementById('customer-detail-page').classList.add('active');
                document.getElementById('page-title').textContent = 'Kundendetails';

                const response = await axios.get('/api/mobile/customers/' + customerId);
                const customer = response.data;

                document.getElementById('customer-detail-name').textContent = customer.name;
                document.getElementById('customer-detail-type').textContent = customer.customer_type || 'Kunde';
                document.getElementById('customer-detail-initials').textContent = customer.initials;

                renderCustomerDetail(customer);

            } catch (error) {
                console.error('Error loading customer detail:', error);
                document.getElementById('customer-detail-content').innerHTML = 
                    '<div class="detail-section"><div class="detail-section-content">' +
                    '<div class="detail-item"><div class="detail-item-value" style="color: #ff3b30; text-align: center;">' +
                    'Fehler beim Laden der Kundendetails</div></div></div></div>';
            }
        }

        function renderCustomerDetail(customer) {
            const content = document.getElementById('customer-detail-content');
            
            let html = '<div class="detail-section">' +
                '<div class="detail-section-header"><span>Grundinformationen</span></div>' +
                '<div class="detail-section-content">' +
                '<div class="detail-item"><div class="detail-item-label">Name:</div><div class="detail-item-value">' + customer.name + '</div></div>' +
                '<div class="detail-item"><div class="detail-item-label">E-Mail:</div><div class="detail-item-value">' + (customer.email || 'Nicht angegeben') + '</div></div>' +
                '<div class="detail-item"><div class="detail-item-label">Kundentyp:</div><div class="detail-item-value">' + (customer.customer_type || 'Nicht angegeben') + '</div></div>' +
                '<div class="detail-item"><div class="detail-item-label">Erstellt:</div><div class="detail-item-value">' + (customer.created_at || 'Unbekannt') + '</div></div>' +
                '</div></div>';

            if (customer.addresses && customer.addresses.length > 0) {
                html += '<div class="detail-section">' +
                    '<div class="detail-section-header"><span>Adressen</span><span class="section-badge">' + customer.addresses.length + '</span></div>' +
                    '<div class="detail-section-content">';
                
                customer.addresses.forEach(function(address) {
                    let addressText = (address.street || '') + ' ' + (address.house_number || '') + '<br>' +
                        (address.postal_code || '') + ' ' + (address.city || '') + '<br>' +
                        (address.country || '');
                    if (address.is_default) {
                        addressText += '<br><small style="color: #007aff;">Standard-Adresse</small>';
                    }
                    
                    html += '<div class="detail-item">' +
                        '<div class="detail-item-label">' + (address.type || 'Adresse') + ':</div>' +
                        '<div class="detail-item-value">' + addressText + '</div>' +
                        '</div>';
                });
                
                html += '</div></div>';
            }

            if (customer.phone_numbers && customer.phone_numbers.length > 0) {
                html += '<div class="detail-section">' +
                    '<div class="detail-section-header"><span>Telefonnummern</span><span class="section-badge">' + customer.phone_numbers.length + '</span></div>' +
                    '<div class="detail-section-content">';
                
                customer.phone_numbers.forEach(function(phone) {
                    let phoneText = phone.number;
                    if (phone.is_default) {
                        phoneText += '<br><small style="color: #007aff;">Standard-Nummer</small>';
                    }
                    
                    html += '<div class="detail-item">' +
                        '<div class="detail-item-label">' + (phone.type || 'Telefon') + ':</div>' +
                        '<div class="detail-item-value">' + phoneText + '</div>' +
                        '</div>';
                });
                
                html += '</div></div>';
            }

            if (customer.invoices && customer.invoices.length > 0) {
                html += '<div class="detail-section">' +
                    '<div class="detail-section-header"><span>Rechnungen</span><span class="section-badge">' + customer.invoices.length + '</span></div>' +
                    '<div class="detail-section-content">';
                
                customer.invoices.forEach(function(invoice) {
                    html += '<div class="detail-item">' +
                        '<div class="detail-item-label">' + invoice.invoice_number + ':</div>' +
                        '<div class="detail-item-value">' + invoice.formatted_total + '<br>' +
                        '<small style="color: #8e8e93;">Erstellt: ' + invoice.created_at + '</small><br>' +
                        '<span class="badge ' + invoice.status_color + '">' + invoice.status_text + '</span>' +
                        '</div></div>';
                });
                
                html += '</div></div>';
            }

            content.innerHTML = html;
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('de-DE', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        // Initialize app
        if (isLoggedIn) {
            showMainApp();
        } else {
            showLoginScreen();
        }
    </script>
</body>
</html>