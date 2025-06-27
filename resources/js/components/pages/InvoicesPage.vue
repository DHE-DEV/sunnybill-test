<template>
  <f7-page name="invoices">
    <f7-navbar title="Rechnungen" back-link="Zurück">
      <f7-link slot="right" icon-f7="plus" @click="createInvoice"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- Invoice Stats -->
      <f7-row>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-gray-800">{{ invoices.length }}</h3>
            <p class="text-gray-600 text-sm">Gesamt</p>
          </div>
        </f7-col>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-green-600">{{ paidInvoices }}</h3>
            <p class="text-gray-600 text-sm">Bezahlt</p>
          </div>
        </f7-col>
        <f7-col width="33">
          <div class="stats-card text-center">
            <h3 class="text-xl font-bold text-orange-600">{{ pendingInvoices }}</h3>
            <p class="text-gray-600 text-sm">Offen</p>
          </div>
        </f7-col>
      </f7-row>

      <!-- Filter Tabs -->
      <f7-block>
        <f7-segmented>
          <f7-button
            :class="{ 'button-active': activeFilter === 'all' }"
            @click="activeFilter = 'all'"
          >
            Alle
          </f7-button>
          <f7-button
            :class="{ 'button-active': activeFilter === 'pending' }"
            @click="activeFilter = 'pending'"
          >
            Offen
          </f7-button>
          <f7-button
            :class="{ 'button-active': activeFilter === 'paid' }"
            @click="activeFilter = 'paid'"
          >
            Bezahlt
          </f7-button>
        </f7-segmented>
      </f7-block>

      <!-- Invoices List -->
      <f7-list>
        <f7-list-item
          v-for="invoice in filteredInvoices"
          :key="invoice.id"
          :title="`Rechnung ${invoice.number}`"
          :subtitle="invoice.customerName"
          :after="formatCurrency(invoice.amount)"
          @click="viewInvoice(invoice)"
          link
        >
          <div slot="media" class="w-12 h-12 rounded-lg flex items-center justify-center"
               :class="getStatusBgColor(invoice.status)">
            <i class="f7-icons" :class="getStatusTextColor(invoice.status)">
              doc_text
            </i>
          </div>
          <div slot="after-title">
            <span class="badge" :class="getStatusColor(invoice.status)">
              {{ getStatusText(invoice.status) }}
            </span>
          </div>
          <div slot="after-subtitle" class="text-xs text-gray-500">
            {{ formatDate(invoice.date) }} • Fällig: {{ formatDate(invoice.dueDate) }}
          </div>
        </f7-list-item>
      </f7-list>

      <!-- Empty State -->
      <f7-block v-if="filteredInvoices.length === 0" class="text-center py-8">
        <i class="f7-icons text-6xl text-gray-300 mb-4">doc_text</i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">Keine Rechnungen</h3>
        <p class="text-gray-500 mb-4">
          {{ activeFilter === 'all' ? 'Erstellen Sie Ihre erste Rechnung' : `Keine ${getFilterText(activeFilter)} Rechnungen` }}
        </p>
        <f7-button v-if="activeFilter === 'all'" fill class="btn-primary" @click="createInvoice">
          <i class="f7-icons mr-2">plus</i>
          Rechnung erstellen
        </f7-button>
      </f7-block>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link href="/app/" icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link href="/app/dashboard/" icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="/app/solar-plants/" icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="/app/customers/" icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="/app/profile/" icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'InvoicesPage',
  data() {
    return {
      activeFilter: 'all',
      invoices: [
        {
          id: 1,
          number: '2024-001',
          customerName: 'Max Mustermann',
          amount: 2450.00,
          status: 'paid',
          date: '2024-01-15',
          dueDate: '2024-02-14'
        },
        {
          id: 2,
          number: '2024-002',
          customerName: 'Schmidt GmbH',
          amount: 3780.50,
          status: 'pending',
          date: '2024-01-20',
          dueDate: '2024-02-19'
        },
        {
          id: 3,
          number: '2024-003',
          customerName: 'Anna Weber',
          amount: 1520.25,
          status: 'paid',
          date: '2024-01-25',
          dueDate: '2024-02-24'
        },
        {
          id: 4,
          number: '2024-004',
          customerName: 'Müller & Partner',
          amount: 5640.00,
          status: 'overdue',
          date: '2024-01-10',
          dueDate: '2024-02-09'
        },
        {
          id: 5,
          number: '2024-005',
          customerName: 'Lisa Hoffmann',
          amount: 2750.75,
          status: 'pending',
          date: '2024-02-01',
          dueDate: '2024-03-02'
        }
      ]
    };
  },
  computed: {
    filteredInvoices() {
      if (this.activeFilter === 'all') {
        return this.invoices;
      }
      return this.invoices.filter(invoice => {
        if (this.activeFilter === 'pending') {
          return invoice.status === 'pending' || invoice.status === 'overdue';
        }
        return invoice.status === this.activeFilter;
      });
    },
    
    paidInvoices() {
      return this.invoices.filter(invoice => invoice.status === 'paid').length;
    },
    
    pendingInvoices() {
      return this.invoices.filter(invoice => invoice.status === 'pending' || invoice.status === 'overdue').length;
    }
  },
  methods: {
    formatCurrency(amount) {
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount);
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('de-DE');
    },
    
    getStatusColor(status) {
      switch (status) {
        case 'paid':
          return 'color-green';
        case 'pending':
          return 'color-orange';
        case 'overdue':
          return 'color-red';
        default:
          return 'color-gray';
      }
    },
    
    getStatusBgColor(status) {
      switch (status) {
        case 'paid':
          return 'bg-green-100';
        case 'pending':
          return 'bg-orange-100';
        case 'overdue':
          return 'bg-red-100';
        default:
          return 'bg-gray-100';
      }
    },
    
    getStatusTextColor(status) {
      switch (status) {
        case 'paid':
          return 'text-green-500';
        case 'pending':
          return 'text-orange-500';
        case 'overdue':
          return 'text-red-500';
        default:
          return 'text-gray-500';
      }
    },
    
    getStatusText(status) {
      switch (status) {
        case 'paid':
          return 'Bezahlt';
        case 'pending':
          return 'Offen';
        case 'overdue':
          return 'Überfällig';
        default:
          return 'Unbekannt';
      }
    },
    
    getFilterText(filter) {
      switch (filter) {
        case 'pending':
          return 'offenen';
        case 'paid':
          return 'bezahlten';
        default:
          return '';
      }
    },
    
    viewInvoice(invoice) {
      this.$f7.dialog.alert(
        `Rechnung: ${invoice.number}\n\nKunde: ${invoice.customerName}\nBetrag: ${this.formatCurrency(invoice.amount)}\nStatus: ${this.getStatusText(invoice.status)}\nDatum: ${this.formatDate(invoice.date)}\nFällig: ${this.formatDate(invoice.dueDate)}`,
        'Rechnungsdetails'
      );
    },
    
    createInvoice() {
      this.$f7.dialog.alert('Neue Rechnung erstellen - Feature kommt bald!', 'Info');
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