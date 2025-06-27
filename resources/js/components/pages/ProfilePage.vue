<template>
  <f7-page name="profile">
    <f7-navbar title="Profil" back-link="Zurück">
      <f7-link slot="right" icon-f7="square_and_arrow_up" @click="logout"></f7-link>
    </f7-navbar>
    
    <div class="page-content">
      <!-- User Info -->
      <f7-block class="text-center py-8">
        <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
          <span class="text-blue-600 font-bold text-2xl">{{ userInitials }}</span>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-1">{{ user.name }}</h2>
        <p class="text-gray-600 mb-2">{{ user.email }}</p>
        <span class="badge color-blue">{{ getRoleText(user.role) }}</span>
      </f7-block>

      <!-- Quick Stats -->
      <f7-block-title>Meine Statistiken</f7-block-title>
      <f7-row>
        <f7-col width="50">
          <div class="stats-card text-center">
            <div class="stats-icon invoice mx-auto mb-2">
              <i class="f7-icons text-white">doc_text</i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">{{ userStats.invoicesCreated }}</h3>
            <p class="text-gray-600 text-sm">Rechnungen erstellt</p>
          </div>
        </f7-col>
        <f7-col width="50">
          <div class="stats-card text-center">
            <div class="stats-icon customer mx-auto mb-2">
              <i class="f7-icons text-white">person_add</i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">{{ userStats.customersAdded }}</h3>
            <p class="text-gray-600 text-sm">Kunden hinzugefügt</p>
          </div>
        </f7-col>
      </f7-row>

      <!-- Settings -->
      <f7-block-title>Einstellungen</f7-block-title>
      <f7-list>
        <f7-list-item
          title="Benachrichtigungen"
          after=""
          @click="toggleNotifications"
        >
          <i slot="media" class="f7-icons">bell</i>
          <f7-toggle slot="after" :checked="settings.notifications" @toggle="settings.notifications = !settings.notifications"></f7-toggle>
        </f7-list-item>
        
        <f7-list-item
          title="Dark Mode"
          after=""
          @click="toggleDarkMode"
        >
          <i slot="media" class="f7-icons">moon</i>
          <f7-toggle slot="after" :checked="settings.darkMode" @toggle="toggleDarkMode"></f7-toggle>
        </f7-list-item>
        
        <f7-list-item
          title="Sprache"
          after="Deutsch"
          link
          @click="changeLanguage"
        >
          <i slot="media" class="f7-icons">globe</i>
        </f7-list-item>
      </f7-list>

      <!-- Account Actions -->
      <f7-block-title>Account</f7-block-title>
      <f7-list>
        <f7-list-item
          title="Passwort ändern"
          link
          @click="changePassword"
        >
          <i slot="media" class="f7-icons">lock</i>
        </f7-list-item>
        
        <f7-list-item
          title="Profil bearbeiten"
          link
          @click="editProfile"
        >
          <i slot="media" class="f7-icons">person_crop_circle</i>
        </f7-list-item>
        
        <f7-list-item
          title="Hilfe & Support"
          link
          @click="showHelp"
        >
          <i slot="media" class="f7-icons">questionmark_circle</i>
        </f7-list-item>
      </f7-list>

      <!-- App Info -->
      <f7-block-title>App Information</f7-block-title>
      <f7-list>
        <f7-list-item
          title="Version"
          after="1.0.0"
        >
          <i slot="media" class="f7-icons">info_circle</i>
        </f7-list-item>
        
        <f7-list-item
          title="Über SunnyBill"
          link
          @click="showAbout"
        >
          <i slot="media" class="f7-icons">heart</i>
        </f7-list-item>
      </f7-list>

      <!-- Logout Button -->
      <f7-block class="mt-8">
        <f7-button fill color="red" @click="logout">
          <i class="f7-icons mr-2">square_and_arrow_up</i>
          Abmelden
        </f7-button>
      </f7-block>
    </div>

    <!-- Bottom Toolbar -->
    <f7-toolbar tabbar position="bottom">
      <f7-link tab-link href="/app/" icon-f7="house" text="Home"></f7-link>
      <f7-link tab-link href="/app/dashboard/" icon-f7="chart_bar" text="Dashboard"></f7-link>
      <f7-link tab-link href="/app/solar-plants/" icon-f7="sun_max" text="Anlagen"></f7-link>
      <f7-link tab-link href="/app/customers/" icon-f7="person_2" text="Kunden"></f7-link>
      <f7-link tab-link href="#tab-profile" tab-link-active icon-f7="person_circle" text="Profil"></f7-link>
    </f7-toolbar>
  </f7-page>
</template>

<script>
export default {
  name: 'ProfilePage',
  data() {
    return {
      settings: {
        notifications: true,
        darkMode: false
      },
      userStats: {
        invoicesCreated: 24,
        customersAdded: 8
      }
    };
  },
  computed: {
    user() {
      return this.$store.getters.user || {
        name: 'Demo User',
        email: 'demo@sunnybill.de',
        role: 'admin'
      };
    },
    
    userInitials() {
      return this.user.name
        .split(' ')
        .map(name => name.charAt(0))
        .join('')
        .toUpperCase();
    }
  },
  methods: {
    getRoleText(role) {
      switch (role) {
        case 'admin':
          return 'Administrator';
        case 'user':
          return 'Benutzer';
        case 'manager':
          return 'Manager';
        default:
          return 'Benutzer';
      }
    },
    
    toggleNotifications() {
      this.settings.notifications = !this.settings.notifications;
      this.$f7.toast.create({
        text: `Benachrichtigungen ${this.settings.notifications ? 'aktiviert' : 'deaktiviert'}`,
        position: 'top',
        closeTimeout: 2000,
      }).open();
    },
    
    toggleDarkMode() {
      this.settings.darkMode = !this.settings.darkMode;
      // In a real app, this would change the theme
      this.$f7.toast.create({
        text: `Dark Mode ${this.settings.darkMode ? 'aktiviert' : 'deaktiviert'}`,
        position: 'top',
        closeTimeout: 2000,
      }).open();
    },
    
    changeLanguage() {
      this.$f7.dialog.alert('Spracheinstellungen - Feature kommt bald!', 'Info');
    },
    
    changePassword() {
      this.$f7.dialog.alert('Passwort ändern - Feature kommt bald!', 'Info');
    },
    
    editProfile() {
      this.$f7.dialog.alert('Profil bearbeiten - Feature kommt bald!', 'Info');
    },
    
    showHelp() {
      this.$f7.dialog.alert('Hilfe & Support\n\nBei Fragen wenden Sie sich an:\nsupport@sunnybill.de\n\nTelefon: +49 123 456 789', 'Hilfe & Support');
    },
    
    showAbout() {
      this.$f7.dialog.alert('SunnyBill Mobile v1.0.0\n\nIhr Solar Management System für unterwegs.\n\nEntwickelt mit ❤️ für eine nachhaltige Zukunft.', 'Über SunnyBill');
    },
    
    logout() {
      this.$f7.dialog.confirm(
        'Möchten Sie sich wirklich abmelden?',
        'Abmelden',
        () => {
          // Clear auth token
          localStorage.removeItem('auth_token');
          
          // Clear user from store
          this.$store.dispatch('logout');
          
          // Navigate to login
          this.$f7router.navigate('/app/login/');
          
          this.$f7.toast.create({
            text: 'Erfolgreich abgemeldet',
            position: 'top',
            closeTimeout: 2000,
          }).open();
        }
      );
    }
  }
};
</script>

<style scoped>
.w-24 {
  width: 6rem;
}

.h-24 {
  height: 6rem;
}

.text-2xl {
  font-size: 1.5rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.mb-1 {
  margin-bottom: 0.25rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mt-8 {
  margin-top: 2rem;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}
</style>