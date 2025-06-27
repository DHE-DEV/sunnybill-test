<template>
  <f7-page name="solar-plants">
    <f7-navbar title="Solaranlagen" back-link="Zurück">
      <f7-link slot="right" icon-f7="plus" @click="addPlant"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- Summary Stats -->
      <f7-block-title>Übersicht</f7-block-title>
      <f7-row>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ solarPlants.length }}</h3>
            <p class="text-gray-600 text-sm">Anlagen</p>
          </div>
        </f7-col>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ totalCapacity }}kW</h3>
            <p class="text-gray-600 text-sm">Kapazität</p>
          </div>
        </f7-col>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ activePlants }}</h3>
            <p class="text-gray-600 text-sm">Aktiv</p>
          </div>
        </f7-col>
      </f7-row>

      <!-- Plants List -->
      <f7-block-title>Anlagen</f7-block-title>
      <f7-list>
        <f7-list-item
          v-for="plant in solarPlants"
          :key="plant.id"
          :title="plant.name"
          :subtitle="plant.location"
          :after="plant.capacity + ' kW'"
          @click="viewPlant(plant)"
          link
        >
          <div slot="media" class="w-12 h-12 rounded-lg flex items-center justify-center"
               :class="plant.status === 'active' ? 'bg-green-100' : 'bg-gray-100'">
            <i class="f7-icons" :class="plant.status === 'active' ? 'text-green-500' : 'text-gray-500'">
              sun_max
            </i>
          </div>
          <div slot="after-title">
            <span class="badge" :class="getStatusColor(plant.status)">
              {{ getStatusText(plant.status) }}
            </span>
          </div>
          <div slot="after-subtitle" class="text-xs text-gray-500">
            {{ plant.monthlyProduction }} kWh/Monat
          </div>
        </f7-list-item>
      </f7-list>

      <!-- Empty State -->
      <f7-block v-if="solarPlants.length === 0" class="text-center py-8">
        <i class="f7-icons text-6xl text-gray-300 mb-4">sun_max</i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">Keine Solaranlagen</h3>
        <p class="text-gray-500 mb-4">Fügen Sie Ihre erste Solaranlage hinzu</p>
        <f7-button fill class="btn-primary" @click="addPlant">
          <i class="f7-icons mr-2">plus</i>
          Anlage hinzufügen
        </f7-button>
      </f7-block>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link href="/app/" icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link href="/app/dashboard/" icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="#tab-plants" tab-link-active icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="/app/customers/" icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="/app/profile/" icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'SolarPlantsPage',
  data() {
    return {
      solarPlants: [
        {
          id: 1,
          name: 'Sonnendach Nord',
          location: 'Musterstraße 1, Berlin',
          capacity: '29.92',
          status: 'active',
          monthlyProduction: 2450
        },
        {
          id: 2,
          name: 'Energiepark Süd',
          location: 'Industriestraße 15, München',
          capacity: '45.60',
          status: 'active',
          monthlyProduction: 3780
        },
        {
          id: 3,
          name: 'Dachanlage West',
          location: 'Hauptstraße 8, Hamburg',
          capacity: '18.24',
          status: 'maintenance',
          monthlyProduction: 1520
        },
        {
          id: 4,
          name: 'Solar Campus',
          location: 'Universitätsplatz 2, Dresden',
          capacity: '67.80',
          status: 'active',
          monthlyProduction: 5640
        },
        {
          id: 5,
          name: 'Grüne Energie Ost',
          location: 'Feldweg 12, Leipzig',
          capacity: '33.15',
          status: 'active',
          monthlyProduction: 2750
        }
      ]
    };
  },
  computed: {
    totalCapacity() {
      return this.solarPlants.reduce((sum, plant) => sum + parseFloat(plant.capacity), 0).toFixed(1);
    },
    activePlants() {
      return this.solarPlants.filter(plant => plant.status === 'active').length;
    }
  },
  methods: {
    getStatusColor(status) {
      switch (status) {
        case 'active':
          return 'color-green';
        case 'maintenance':
          return 'color-orange';
        case 'inactive':
          return 'color-red';
        default:
          return 'color-gray';
      }
    },
    
    getStatusText(status) {
      switch (status) {
        case 'active':
          return 'Aktiv';
        case 'maintenance':
          return 'Wartung';
        case 'inactive':
          return 'Inaktiv';
        default:
          return 'Unbekannt';
      }
    },
    
    viewPlant(plant) {
      this.$f7.dialog.alert(`Details für ${plant.name}\n\nStandort: ${plant.location}\nKapazität: ${plant.capacity} kW\nStatus: ${this.getStatusText(plant.status)}\nMonatliche Produktion: ${plant.monthlyProduction} kWh`, 'Anlagendetails');
    },
    
    addPlant() {
      this.$f7.dialog.alert('Neue Solaranlage hinzufügen - Feature kommt bald!', 'Info');
    }
  }
};
</script>

<style scoped>
.mr-2 {
  margin-right: 0.5rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.w-12 {
  width: 3rem;
}

.h-12 {
  height: 3rem;
}

.text-6xl {
  font-size: 3.75rem;
}
</style>