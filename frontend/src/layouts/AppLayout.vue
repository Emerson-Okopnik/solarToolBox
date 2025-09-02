<template>
  <div class="app-layout d-flex">
    <!-- Sidebar -->
    <div class="sidebar bg-light border-end " :class="{ 'open': sidebarOpen }">
      <div class="sidebar-header">
        <h1 class="h4 m-0">Solar Toolbox</h1>
      </div>
      
      <nav class="sidebar-nav nav flex-column">
      <router-link
        v-for="item in navigation"
        :key="item.name"
        :to="item.to"
        class="nav-link d-flex align-items-center gap-1"
        :class="{ active: $route.path.startsWith(item.to) }"
      >
        <component :is="item.icon" class="nav-icon" />
        {{ item.label }}
      </router-link>
      </nav>
    </div>

    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="mobile-overlay"
      @click="toggleSidebar"
    ></div>

    <!-- Conteúdo principal -->
    <div class="main-content flex-grow-1">
      <div class="top-bar d-flex justify-content-end align-items-center p-2 border-bottom">
        <button
          @click="toggleSidebar"
          class="btn btn-secondary mobile-menu-btn"
          type="button"
        >
          <Bars3Icon class="nav-icon" />
        </button>

        <div class="user-menu ms-3">
          <button
            class="btn btn-secondary user-btn"
            type="button"
            @click="toggleUserMenu"
          >
            <div class="user-avatar me-2">
              <span class="font-medium text-sm">{{ userInitials }}</span>
            </div>
            <span class="user-name">{{ userName }}</span>
          </button>

          <div v-if="userMenuOpen" class="user-dropdown">
            <a href="#" class="dropdown-item">Perfil</a>
            <a href="#" class="dropdown-item">Configurações</a>
            <div class="dropdown-divider"></div>
            <button @click="handleLogout" class="dropdown-item" type="button">
              Sair
            </button>
          </div>
        </div>
      </div>

      <main class="page-content p-3">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Bars3Icon,
  HomeIcon,
  FolderIcon,
  BookOpenIcon,
} from '@heroicons/vue/24/outline'

export default defineComponent({
  name: 'AppLayout',
  components: {
    Bars3Icon,
    HomeIcon,
    FolderIcon,
    BookOpenIcon,
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    return {
      router,
      authStore
    }
  },
  data() {
    return {
      sidebarOpen: false,
      userMenuOpen: false,
      navigation: [
        { name: 'catalogos', label: 'Catálogos', to: '/catalogos', icon: 'BookOpenIcon' },
        { name: 'projetos', label: 'Projetos', to: '/projetos', icon: 'FolderIcon' },
      ]
    }
  },
  computed: {
    userInitials() {
      const name = this.authStore.user?.name || ''
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    },
    userName() {
      return this.authStore.user?.name || ''
    }
  },
  methods: {
    async handleLogout() {
      try {
        await this.authStore.logout()
        this.router.push('/auth/login')
      } catch (error) {
        console.error('Erro ao fazer logout:', error)
      }
    },
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen
    },
    toggleUserMenu() {
      this.userMenuOpen = !this.userMenuOpen
    },
  },
})
</script>

<style scoped>

.sidebar-header {
  background-color: #1a73e8;
  color: #fff;
  padding: 1.1rem;
  margin: 0;
}

.sidebar .nav-link {
  color: var(--bs-body-color);
  border-radius: 0.25rem;
  transition: background-color 0.2s ease;
}

.sidebar .nav-link:hover {
  background-color: var(--bs-light);
}

.sidebar .nav-link.active {
  background-color: var(--bs-secondary-bg-subtle);
}

.nav-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.sidebar .nav-link.active {
  background-color: var(--bs-secondary-bg-subtle);
}

.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 999;
}

.mobile-menu-btn {
  display: none;
}

.user-menu {
  position: relative;
}

.user-btn {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  background: transparent;
  border: none;
  color: var(--gray-700);
}

.user-avatar {
  width: 2rem;
  height: 2rem;
  background-color: var(--primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
}

.user-name {
  font-weight: 500;
}

.user-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: var(--space-2);
  background-color: var(--white);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  min-width: 12rem;
  z-index: 1000;
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: var(--space-2) var(--space-4);
  color: var(--gray-700);
  text-decoration: none;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.dropdown-item:hover {
  background-color: var(--gray-50);
  color: var(--gray-900);
}

.dropdown-divider {
  height: 1px;
  background-color: var(--gray-200);
  margin: var(--space-2) 0;
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: flex;
  }
  
  .user-name {
    display: none;
  }
}
</style>
