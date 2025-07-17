<template>
  <f7-app :params="f7params">
    <!-- Main Framework7 App component -->
    
    <!-- Main View -->
    <f7-view
      main
      class="safe-areas"
      url="/app/"
      :browser-history="true"
      :browser-history-separator="''"
    ></f7-view>

    <!-- Login Screen -->
    <f7-login-screen id="login-screen" :opened="!isLoggedIn">
      <f7-view>
        <f7-page login-screen class="login-screen-bg">
          <f7-login-screen-title class="login-screen-content">
            <div class="app-logo">SB</div>
            <h1 class="text-white text-2xl font-bold mb-2">SunnyBill</h1>
            <p class="text-white/80 text-sm mb-8">Solar Management System</p>
          </f7-login-screen-title>
          
          <f7-list class="login-screen-content mx-4" form>
            <f7-list-input
              label="E-Mail"
              type="email"
              placeholder="Ihre E-Mail-Adresse"
              v-model:value="loginForm.email"
              class="mb-4"
            ></f7-list-input>
            
            <f7-list-input
              label="Passwort"
              type="password"
              placeholder="Ihr Passwort"
              v-model:value="loginForm.password"
              class="mb-6"
            ></f7-list-input>
          </f7-list>
          
          <f7-list class="login-screen-content mx-4">
            <f7-list-button
              title="Anmelden"
              @click="login"
              :class="['btn-primary', { 'opacity-50': loading }]"
              :disabled="loading"
            ></f7-list-button>
          </f7-list>
          
          <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
          </div>
          
          <div class="login-screen-content mx-4 mt-4 text-center">
            <p class="text-white/60 text-xs">
              Demo-Zugangsdaten:<br>
              E-Mail: demo@voltmaster.cloud<br>
              Passwort: demo123
            </p>
          </div>
        </f7-page>
      </f7-view>
    </f7-login-screen>
  </f7-app>
</template>

<script>
export default {
  name: 'MobileApp',
  data() {
    return {
      f7params: {
        name: 'SunnyBill Mobile',
        theme: 'auto',
        id: 'io.sunnybill.mobile',
      },
      loginForm: {
        email: 'demo@voltmaster.cloud',
        password: 'demo123'
      },
      loading: false
    };
  },
  computed: {
    isLoggedIn() {
      return this.$store.getters.isLoggedIn.value;
    }
  },
  methods: {
    async login() {
      if (this.loading) return;
      
      this.loading = true;
      this.$store.dispatch('setLoading', true);
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Demo login - in real app, this would be an API call
        if (this.loginForm.email === 'demo@voltmaster.cloud' && this.loginForm.password === 'demo123') {
          const user = {
            id: 1,
            name: 'Demo User',
            email: 'demo@voltmaster.cloud',
            role: 'admin'
          };
          
          // Store auth token
          localStorage.setItem('auth_token', 'demo_token_123');
          
          // Set user in store
          this.$store.dispatch('setUser', user);
          
          // Load initial stats
          await this.loadStats();
          
          // Close login screen
          this.$f7.loginScreen.close('#login-screen');
          
          // Navigate to dashboard
          this.$f7router.navigate('/app/dashboard/');
          
          // Show success notification
          this.$f7.toast.create({
            text: 'Erfolgreich angemeldet!',
            position: 'top',
            closeTimeout: 2000,
          }).open();
        } else {
          // Show error
          this.$f7.toast.create({
            text: 'Ungültige Anmeldedaten',
            position: 'top',
            closeTimeout: 3000,
          }).open();
        }
      } catch (error) {
        console.error('Login error:', error);
        this.$f7.toast.create({
          text: 'Anmeldung fehlgeschlagen',
          position: 'top',
          closeTimeout: 3000,
        }).open();
      } finally {
        this.loading = false;
        this.$store.dispatch('setLoading', false);
      }
    },
    
    async loadStats() {
      try {
        // Simulate API call to load dashboard stats
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        const stats = {
          solarPlants: 12,
          customers: 45,
          invoices: 128,
          revenue: 125430.50
        };
        
        this.$store.dispatch('setStats', stats);
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    }
  },
  
  mounted() {
    this.$f7ready(() => {
      // Check if user is already logged in
      const token = localStorage.getItem('auth_token');
      if (token) {
        // In a real app, validate the token with the server
        const user = {
          id: 1,
          name: 'Demo User',
          email: 'demo@voltmaster.cloud',
          role: 'admin'
        };
        
        this.$store.dispatch('setUser', user);
        this.loadStats();
      }
    });
  }
};
</script>

<style scoped>
.login-screen-bg {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.app-logo {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  font-weight: bold;
  color: white;
  margin: 0 auto 20px;
  backdrop-filter: blur(10px);
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.btn-primary {
  background: linear-gradient(45deg, #667eea, #764ba2) !important;
  color: white !important;
  border: none !important;
}
</style>