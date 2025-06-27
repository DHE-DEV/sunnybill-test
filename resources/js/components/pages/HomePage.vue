<template>
  <f7-page name="home">
    <f7-navbar title="SunnyBill" class="navbar-large">
      <f7-link slot="right" icon-f7="person_circle" href="/app/profile/"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- Welcome Section -->
      <f7-block class="text-center py-8">
        <div class="app-logo mx-auto mb-4"></div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Willkommen bei SunnyBill</h1>
        <p class="text-gray-600">Ihr Solar Management System</p>
      </f7-block>

      <!-- Quick Stats -->
      <f7-block-title>Übersicht</f7-block-title>
      <f7-row>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon solar">
              <i class="f7-icons text-white">sun_max</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.solarPlants }}</h3>
            <p class="text-gray-600 text-sm">Solaranlagen</p>
          </div>
        </f7-col>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon customer">
              <i class="f7-icons text-white">person_2</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.customers }}</h3>
            <p class="text-gray-600 text-sm">Kunden</p>
          </div>
        </f7-col>
      </f7-row>
      
      <f7-row>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon invoice">
              <i class="f7-icons text-white">doc_text</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.invoices }}</h3>
            <p class="text-gray-600 text-sm">Rechnungen</p>
          </div>
        </f7-col>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon revenue">
              <i class="f7-icons text-white">euros</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ formattedRevenue }}</h3>
            <p class="text-gray-600 text-sm">Umsatz</p>
          </div>
        </f7-col>
      </f7-row>

      <!-- Quick Actions -->
      <f7-block-title>Schnellzugriff</f7-block-title>
      <f7-list>
        <f7-list-item
          link="/app/dashboard/"
          title="Dashboard"
          after=">"
          class="list-item-custom"
        >
          <i slot="media" class="f7-icons">chart_bar</i>
        </f7-list-item>
        <f7-list-item
          link="/app/solar-plants/"
          title="Solaranlagen"
          after=">"
          class="list-item-custom"
        >
          <i slot="media" class="f7-icons">sun_max</i>
        </f7-list-item>
        <f7-list-item
          link="/app/customers/"
          title="Kunden"
          after=">"
          class="list-item-custom"
        >
          <i slot="media" class="f7-icons">person_2</i>
        </f7-list-item>
        <f7-list-item
          link="/app/invoices/"
          title="Rechnungen"
          after=">"
          class="list-item-custom"
        >
          <i slot="media" class="f7-icons">doc_text</i>
        </f7-list-item>
      </f7-list>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link="#tab-home" tab-link-active icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link href="/app/dashboard/" icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="/app/solar-plants/" icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="/app/customers/" icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="/app/profile/" icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'HomePage',
  computed: {
    stats() {
      return this.$store.getters.stats;
    },
    formattedRevenue() {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(this.stats.revenue);
    }
  },
  mounted() {
    // Load stats if not already loaded
    if (this.stats.solarPlants === 0) {
      this.loadStats();
    }
  },
  methods: {
    async loadStats() {
      try {
        // Simulate API call
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

<style scoped>
.list-item-custom {
  background: white;
  margin-bottom: 8px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>