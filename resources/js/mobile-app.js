import { createApp } from 'vue';

// Simple mobile app without Framework7 for now
const app = createApp({
  data() {
    return {
      isLoggedIn: false,
      user: null,
      loginForm: {
        email: 'demo@sunnybill.de',
        password: 'demo123'
      },
      loading: false,
      currentPage: 'login',
      stats: {
        solarPlants: 12,
        customers: 45,
        invoices: 128,
        revenue: 125430.50
      }
    };
  },
  computed: {
    userInitials() {
      if (!this.user) return 'DU';
      return this.user.name
        .split(' ')
        .map(name => name.charAt(0))
        .join('')
        .toUpperCase();
    }
  },
  methods: {
    async login() {
      if (this.loading) return;
      
      this.loading = true;
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        if (this.loginForm.email === 'demo@sunnybill.de' && this.loginForm.password === 'demo123') {
          this.user = {
            id: 1,
            name: 'Demo User',
            email: 'demo@sunnybill.de',
            role: 'admin'
          };
          
          localStorage.setItem('auth_token', 'demo_token_123');
          this.isLoggedIn = true;
          this.currentPage = 'dashboard';
          
          this.showToast('Erfolgreich angemeldet!');
        } else {
          this.showToast('Ungültige Anmeldedaten');
        }
      } catch (error) {
        console.error('Login error:', error);
        this.showToast('Anmeldung fehlgeschlagen');
      } finally {
        this.loading = false;
      }
    },
    
    logout() {
      if (confirm('Möchten Sie sich wirklich abmelden?')) {
        localStorage.removeItem('auth_token');
        this.user = null;
        this.isLoggedIn = false;
        this.currentPage = 'login';
        this.showToast('Erfolgreich abgemeldet');
      }
    },
    
    navigateTo(page) {
      this.currentPage = page;
    },
    
    showToast(message) {
      // Simple toast implementation
      const toast = document.createElement('div');
      toast.className = 'toast';
      toast.textContent = message;
      toast.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s;
      `;
      
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.opacity = '1';
      }, 100);
      
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
          document.body.removeChild(toast);
        }, 300);
      }, 2000);
    }
  },
  
  mounted() {
    // Check if user is already logged in
    const token = localStorage.getItem('auth_token');
    if (token) {
      this.user = {
        id: 1,
        name: 'Demo User',
        email: 'demo@sunnybill.de',
        role: 'admin'
      };
      this.isLoggedIn = true;
      this.currentPage = 'dashboard';
    }
  },
  
  template: `
    <div id="mobile-app" class="min-h-screen bg-gray-50">
      <!-- Login Screen -->
      <div v-if="!isLoggedIn" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
        <div class="w-full max-w-md p-8">
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
              <span class="text-white text-3xl font-bold">SB</span>
            </div>
            <h1 class="text-white text-3xl font-bold mb-2">SunnyBill</h1>
            <p class="text-white text-opacity-80">Solar Management System</p>
          </div>
          
          <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl p-6">
            <div class="mb-4">
              <label class="block text-white text-sm font-medium mb-2">E-Mail</label>
              <input 
                v-model="loginForm.email"
                type="email" 
                class="w-full px-4 py-3 rounded-lg bg-white bg-opacity-20 text-white placeholder-white placeholder-opacity-70 border border-white border-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                placeholder="Ihre E-Mail-Adresse"
              />
            </div>
            
            <div class="mb-6">
              <label class="block text-white text-sm font-medium mb-2">Passwort</label>
              <input 
                v-model="loginForm.password"
                type="password" 
                class="w-full px-4 py-3 rounded-lg bg-white bg-opacity-20 text-white placeholder-white placeholder-opacity-70 border border-white border-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                placeholder="Ihr Passwort"
                @keyup.enter="login"
              />
            </div>
            
            <button 
              @click="login"
              :disabled="loading"
              class="w-full bg-white text-blue-600 font-semibold py-3 px-4 rounded-lg hover:bg-opacity-90 transition duration-200 disabled:opacity-50"
            >
              <span v-if="loading">Anmelden...</span>
              <span v-else>Anmelden</span>
            </button>
            
            <div class="mt-4 text-center">
              <p class="text-white text-opacity-60 text-sm">
                Demo-Zugangsdaten:<br>
                E-Mail: demo@sunnybill.de<br>
                Passwort: demo123
              </p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Main App -->
      <div v-else class="min-h-screen pb-20">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
          <div class="px-4 py-4 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">
              <span v-if="currentPage === 'dashboard'">Dashboard</span>
              <span v-else-if="currentPage === 'solar-plants'">Solaranlagen</span>
              <span v-else-if="currentPage === 'customers'">Kunden</span>
              <span v-else-if="currentPage === 'invoices'">Rechnungen</span>
              <span v-else-if="currentPage === 'profile'">Profil</span>
              <span v-else>SunnyBill</span>
            </h1>
            <button @click="logout" class="text-red-600 hover:text-red-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Content -->
        <div class="p-4">
          <!-- Dashboard -->
          <div v-if="currentPage === 'dashboard'">
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-blue-600">{{ stats.solarPlants }}</div>
                <div class="text-sm text-gray-600">Solaranlagen</div>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-green-600">{{ stats.customers }}</div>
                <div class="text-sm text-gray-600">Kunden</div>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-purple-600">{{ stats.invoices }}</div>
                <div class="text-sm text-gray-600">Rechnungen</div>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-orange-600">€{{ stats.revenue.toLocaleString() }}</div>
                <div class="text-sm text-gray-600">Umsatz</div>
              </div>
            </div>
            
            <div class="bg-white rounded-lg p-4 shadow-sm">
              <h3 class="font-semibold text-gray-800 mb-3">Letzte Aktivitäten</h3>
              <div class="space-y-3">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span class="text-sm text-gray-600">Neue Rechnung erstellt</span>
                </div>
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  <span class="text-sm text-gray-600">Kunde hinzugefügt</span>
                </div>
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                  <span class="text-sm text-gray-600">Solaranlage aktualisiert</span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Solar Plants -->
          <div v-else-if="currentPage === 'solar-plants'">
            <div class="space-y-4">
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-semibold">Anlage München Nord</h3>
                  <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Aktiv</span>
                </div>
                <p class="text-sm text-gray-600">Kapazität: 50 kWp</p>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-semibold">Anlage Berlin Süd</h3>
                  <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Wartung</span>
                </div>
                <p class="text-sm text-gray-600">Kapazität: 75 kWp</p>
              </div>
            </div>
          </div>
          
          <!-- Customers -->
          <div v-else-if="currentPage === 'customers'">
            <div class="space-y-4">
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <h3 class="font-semibold">Max Mustermann</h3>
                <p class="text-sm text-gray-600">max@example.com</p>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <h3 class="font-semibold">Anna Schmidt</h3>
                <p class="text-sm text-gray-600">anna@example.com</p>
              </div>
            </div>
          </div>
          
          <!-- Invoices -->
          <div v-else-if="currentPage === 'invoices'">
            <div class="space-y-4">
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-semibold">Rechnung #2024-001</h3>
                  <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Bezahlt</span>
                </div>
                <p class="text-sm text-gray-600">€1.250,00</p>
              </div>
              <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex justify-between items-center mb-2">
                  <h3 class="font-semibold">Rechnung #2024-002</h3>
                  <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Offen</span>
                </div>
                <p class="text-sm text-gray-600">€890,00</p>
              </div>
            </div>
          </div>
          
          <!-- Profile -->
          <div v-else-if="currentPage === 'profile'">
            <div class="bg-white rounded-lg p-6 shadow-sm">
              <div class="text-center mb-6">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <span class="text-blue-600 font-bold text-xl">{{ userInitials }}</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ user.name }}</h2>
                <p class="text-gray-600">{{ user.email }}</p>
              </div>
              
              <div class="space-y-4">
                <div class="border-t pt-4">
                  <h3 class="font-semibold text-gray-800 mb-2">App Information</h3>
                  <p class="text-sm text-gray-600">Version: 1.0.0</p>
                  <p class="text-sm text-gray-600">SunnyBill Mobile</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
          <div class="grid grid-cols-5 py-2">
            <button @click="navigateTo('dashboard')" :class="['flex flex-col items-center py-2 px-1', currentPage === 'dashboard' ? 'text-blue-600' : 'text-gray-400']">
              <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
              </svg>
              <span class="text-xs">Dashboard</span>
            </button>
            
            <button @click="navigateTo('solar-plants')" :class="['flex flex-col items-center py-2 px-1', currentPage === 'solar-plants' ? 'text-blue-600' : 'text-gray-400']">
              <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5zM10 18l-7-5V7l7-5 7 5v6l-7 5z" clip-rule="evenodd"></path>
              </svg>
              <span class="text-xs">Anlagen</span>
            </button>
            
            <button @click="navigateTo('customers')" :class="['flex flex-col items-center py-2 px-1', currentPage === 'customers' ? 'text-blue-600' : 'text-gray-400']">
              <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
              </svg>
              <span class="text-xs">Kunden</span>
            </button>
            
            <button @click="navigateTo('invoices')" :class="['flex flex-col items-center py-2 px-1', currentPage === 'invoices' ? 'text-blue-600' : 'text-gray-400']">
              <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
              </svg>
              <span class="text-xs">Rechnungen</span>
            </button>
            
            <button @click="navigateTo('profile')" :class="['flex flex-col items-center py-2 px-1', currentPage === 'profile' ? 'text-blue-600' : 'text-gray-400']">
              <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
              </svg>
              <span class="text-xs">Profil</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  `
});

app.mount('#app');

console.log('SunnyBill Mobile App initialized (Simple Version)');