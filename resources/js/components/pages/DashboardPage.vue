<template>
  <f7-page name="dashboard">
    <f7-navbar title="Dashboard" back-link="Zurück">
      <f7-link slot="right" icon-f7="arrow_clockwise" @click="refreshData"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- Key Metrics -->
      <f7-block-title>Kennzahlen</f7-block-title>
      <f7-row>
        <f7-col width="100">
          <div class="stats-card">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-3xl font-bold text-gray-800">{{ formattedRevenue }}</h3>
                <p class="text-gray-600">Gesamtumsatz</p>
                <span class="text-green-500 text-sm">+12.5% vs. Vormonat</span>
              </div>
              <div class="stats-icon revenue">
                <i class="f7-icons text-white">euros</i>
              </div>
            </div>
          </div>
        </f7-col>
      </f7-row>

      <f7-row>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon solar">
              <i class="f7-icons text-white">sun_max</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.solarPlants }}</h3>
            <p class="text-gray-600 text-sm">Aktive Anlagen</p>
            <span class="text-blue-500 text-xs">{{ totalCapacity }} kW</span>
          </div>
        </f7-col>
        <f7-col width="50">
          <div class="stats-card">
            <div class="stats-icon customer">
              <i class="f7-icons text-white">person_2</i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800">{{ stats.customers }}</h3>
            <p class="text-gray-600 text-sm">Kunden</p>
            <span class="text-green-500 text-xs">+3 diese Woche</span>
          </div>
        </f7-col>
      </f7-row>

      <!-- Recent Activity -->
      <f7-block-title>Letzte Aktivitäten</f7-block-title>
      <f7-list>
        <f7-list-item
          v-for="activity in recentActivities"
          :key="activity.id"
          :title="activity.title"
          :subtitle="activity.description"
          :after="activity.time"
        >
          <i slot="media" :class="`f7-icons ${activity.iconColor}`">{{ activity.icon }}</i>
        </f7-list-item>
      </f7-list>

      <!-- Quick Actions -->
      <f7-block-title>Schnellaktionen</f7-block-title>
      <f7-row>
        <f7-col width="50">
          <f7-button fill class="btn-primary" @click="createInvoice">
            <i class="f7-icons mr-2">plus</i>
            Neue Rechnung
          </f7-button>
        </f7-col>
        <f7-col width="50">
          <f7-button fill class="btn-primary" @click="addCustomer">
            <i class="f7-icons mr-2">person_add</i>
            Neuer Kunde
          </f7-button>
        </f7-col>
      </f7-row>

      <!-- Monthly Performance -->
      <f7-block-title>Monatsleistung</f7-block-title>
      <div class="stats-card">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Energieproduktion</h4>
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-green-500">{{ monthlyProduction.current }}kWh</div>
            <div class="text-sm text-gray-600">Aktueller Monat</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-500">{{ monthlyProduction.previous }}kWh</div>
            <div class="text-sm text-gray-600">Vormonat</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-500">{{ monthlyProduction.change }}%</div>
            <div class="text-sm text-gray-600">Veränderung</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link href="/app/" icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link="#tab-dashboard" tab-link-active icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="/app/solar-plants/" icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="/app/customers/" icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="/app/profile/" icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'DashboardPage',
  data() {
    return {
      totalCapacity: 245.8,
      monthlyProduction: {
        current: 18750,
        previous: 16200,
        change: 15.7
      },
      recentActivities: [
        {
          id: 1,
          title: 'Neue Rechnung erstellt',
          description: 'Rechnung #2024-001 für Kunde Müller',
          time: '2 Min',
          icon: 'doc_text',
          iconColor: 'text-blue-500'
        },
        {
          id: 2,
          title: 'Solaranlage hinzugefügt',
          description: 'Anlage "Sonnendach Nord" registriert',
          time: '1 Std',
          icon: 'sun_max',
          iconColor: 'text-orange-500'
        },
        {
          id: 3,
          title: 'Kunde registriert',
          description: 'Neuer Kunde: Schmidt GmbH',
          time: '3 Std',
          icon: 'person_add',
          iconColor: 'text-green-500'
        },
        {
          id: 4,
          title: 'Zahlung eingegangen',
          description: '€2,450.00 von Kunde Weber',
          time: '5 Std',
          icon: 'euros',
          iconColor: 'text-green-500'
        }
      ]
    };
  },
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
  methods: {
    async refreshData() {
      this.$f7.preloader.show();
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Update stats
        const updatedStats = {
          ...this.stats,
          revenue: this.stats.revenue + Math.random() * 1000
        };
        
        this.$store.dispatch('setStats', updatedStats);
        
        this.$f7.toast.create({
          text: 'Daten aktualisiert',
          position: 'top',
          closeTimeout: 2000,
        }).open();
      } catch (error) {
        console.error('Error refreshing data:', error);
        this.$f7.toast.create({
          text: 'Fehler beim Aktualisieren',
          position: 'top',
          closeTimeout: 3000,
        }).open();
      } finally {
        this.$f7.preloader.hide();
      }
    },
    
    createInvoice() {
      this.$f7.dialog.alert('Neue Rechnung erstellen - Feature kommt bald!', 'Info');
    },
    
    addCustomer() {
      this.$f7.dialog.alert('Neuen Kunden hinzufügen - Feature kommt bald!', 'Info');
    }
  }
};
</script>

<style scoped>
.grid {
  display: grid;
}

.grid-cols-3 {
  grid-template-columns: repeat(3, 1fr);
}

.gap-4 {
  gap: 1rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}
</style>