<template>
  <f7-page name="login" login-screen class="login-screen-bg">
    <f7-navbar title="Anmelden" back-link="Zurück"></f7-navbar>
    
    <div class="page-content login-screen-content">
      <f7-block class="text-center py-8">
        <div class="app-logo mx-auto mb-4"></div>
        <h1 class="text-white text-2xl font-bold mb-2">SunnyBill</h1>
        <p class="text-white/80 text-sm mb-8">Solar Management System</p>
      </f7-block>

      <f7-list form class="mx-4">
        <f7-list-input
          label="E-Mail"
          type="email"
          placeholder="Ihre E-Mail-Adresse"
          :value="loginForm.email"
          @input="loginForm.email = $event.target.value"
          class="mb-4"
        ></f7-list-input>
        
        <f7-list-input
          label="Passwort"
          type="password"
          placeholder="Ihr Passwort"
          :value="loginForm.password"
          @input="loginForm.password = $event.target.value"
          class="mb-6"
        ></f7-list-input>
      </f7-list>
      
      <f7-list class="mx-4">
        <f7-list-button
          title="Anmelden"
          @click="login"
          :class="['btn-primary', { 'opacity-50': loading }]"
          :disabled="loading"
        ></f7-list-button>
      </f7-list>

      <f7-block class="text-center mt-8">
        <p class="text-white/60 text-sm">Demo-Zugangsdaten:</p>
        <p class="text-white/80 text-sm">E-Mail: demo@sunnybill.de</p>
        <p class="text-white/80 text-sm">Passwort: demo123</p>
      </f7-block>
      
      <div v-if="loading" class="loading-overlay">
        <div class="loading-spinner"></div>
      </div>
    </div>
  </f7-page>
</template>

<script>
export default {
  name: 'LoginPage',
  data() {
    return {
      loginForm: {
        email: 'demo@sunnybill.de',
        password: 'demo123'
      },
      loading: false
    };
  },
  methods: {
    async login() {
      if (this.loading) return;
      
      this.loading = true;
      this.$store.dispatch('setLoading', true);
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Demo login
        if (this.loginForm.email === 'demo@sunnybill.de' && this.loginForm.password === 'demo123') {
          const user = {
            id: 1,
            name: 'Demo User',
            email: 'demo@sunnybill.de',
            role: 'admin'
          };
          
          localStorage.setItem('auth_token', 'demo_token_123');
          this.$store.dispatch('setUser', user);
          
          // Load stats
          await this.loadStats();
          
          this.$f7router.navigate('/app/dashboard/');
          
          this.$f7.toast.create({
            text: 'Erfolgreich angemeldet!',
            position: 'top',
            closeTimeout: 2000,
          }).open();
        } else {
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
  }
};
</script>