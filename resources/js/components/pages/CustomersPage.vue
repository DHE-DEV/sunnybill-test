<template>
  <f7-page name="customers">
    <f7-navbar title="Kunden" back-link="Zurück">
      <f7-link slot="right" icon-f7="plus" @click="addCustomer"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- Search -->
      <f7-block>
        <f7-list>
          <f7-list-input
            type="text"
            placeholder="Kunden suchen..."
            :value="searchQuery"
            @input="searchQuery = $event.target.value"
            clear-button
          >
            <i slot="media" class="f7-icons">search</i>
          </f7-list-input>
        </f7-list>
      </f7-block>

      <!-- Customer Stats -->
      <f7-row>
        <f7-col width="50">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ filteredCustomers.length }}</h3>
            <p class="text-gray-600 text-sm">Kunden</p>
          </div>
        </f7-col>
        <f7-col width="50">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ activeCustomers }}</h3>
            <p class="text-gray-600 text-sm">Aktiv</p>
          </div>
        </f7-col>
      </f7-row>

      <!-- Customers List -->
      <f7-block-title>Kundenliste</f7-block-title>
      <f7-list>
        <f7-list-item
          v-for="customer in filteredCustomers"
          :key="customer.id"
          :title="customer.name"
          :subtitle="customer.email"
          :after="customer.totalInvoices + ' Rechnungen'"
          @click="viewCustomer(customer)"
          link
        >
          <div slot="media" class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
            <span class="text-blue-600 font-semibold">{{ customer.initials }}</span>
          </div>
          <div slot="after-title">
            <span class="badge" :class="customer.isActive ? 'color-green' : 'color-gray'">
              {{ customer.isActive ? 'Aktiv' : 'Inaktiv' }}
            </span>
          </div>
          <div slot="after-subtitle" class="text-xs text-gray-500">
            {{ customer.city }} • {{ formatCurrency(customer.totalRevenue) }}
          </div>
        </f7-list-item>
      </f7-list>

      <!-- Empty State -->
      <f7-block v-if="filteredCustomers.length === 0" class="text-center py-8">
        <i class="f7-icons text-6xl text-gray-300 mb-4">person_2</i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">
          {{ searchQuery ? 'Keine Kunden gefunden' : 'Keine Kunden' }}
        </h3>
        <p class="text-gray-500 mb-4">
          {{ searchQuery ? 'Versuchen Sie einen anderen Suchbegriff' : 'Fügen Sie Ihren ersten Kunden hinzu' }}
        </p>
        <f7-button v-if="!searchQuery" fill class="btn-primary" @click="addCustomer">
          <i class="f7-icons mr-2">plus</i>
          Kunde hinzufügen
        </f7-button>
      </f7-block>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link href="/app/" icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link href="/app/dashboard/" icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="/app/solar-plants/" icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="#tab-customers" tab-link-active icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="/app/profile/" icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'CustomersPage',
  data() {
    return {
      searchQuery: '',
      customers: [
        {
          id: 1,
          name: 'Max Mustermann',
          email: 'max.mustermann@email.de',
          city: 'Berlin',
          isActive: true,
          totalInvoices: 12,
          totalRevenue: 15420.50,
          initials: 'MM'
        },
        {
          id: 2,
          name: 'Schmidt GmbH',
          email: 'info@schmidt-gmbh.de',
          city: 'München',
          isActive: true,
          totalInvoices: 8,
          totalRevenue: 24680.00,
          initials: 'SG'
        },
        {
          id: 3,
          name: 'Anna Weber',
          email: 'anna.weber@email.de',
          city: 'Hamburg',
          isActive: true,
          totalInvoices: 15,
          totalRevenue: 18750.25,
          initials: 'AW'
        },
        {
          id: 4,
          name: 'Müller & Partner',
          email: 'kontakt@mueller-partner.de',
          city: 'Dresden',
          isActive: false,
          totalInvoices: 3,
          totalRevenue: 4250.00,
          initials: 'MP'
        },
        {
          id: 5,
          name: 'Lisa Hoffmann',
          email: 'lisa.hoffmann@email.de',
          city: 'Leipzig',
          isActive: true,
          totalInvoices: 6,
          totalRevenue: 9840.75,
          initials: 'LH'
        }
      ]
    };
  },
  computed: {
    filteredCustomers() {
      if (!this.searchQuery) {
        return this.customers;
      }
      
      const query = this.searchQuery.toLowerCase();
      return this.customers.filter(customer =>
        customer.name.toLowerCase().includes(query) ||
        customer.email.toLowerCase().includes(query) ||
        customer.city.toLowerCase().includes(query)
      );
    },
    
    activeCustomers() {
      return this.customers.filter(customer => customer.isActive).length;
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount);
    },
    
    viewCustomer(customer) {
      this.$f7.dialog.alert(
        `Kunde: ${customer.name}\n\nE-Mail: ${customer.email}\nStadt: ${customer.city}\nStatus: ${customer.isActive ? 'Aktiv' : 'Inaktiv'}\nRechnungen: ${customer.totalInvoices}\nGesamtumsatz: ${this.formatCurrency(customer.totalRevenue)}`,
        'Kundendetails'
      );
    },
    
    addCustomer() {
      this.$f7.dialog.alert('Neuen Kunden hinzufügen - Feature kommt bald!', 'Info');
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