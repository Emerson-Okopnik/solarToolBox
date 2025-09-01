<template>
  <div class="min-vh-100 bg-light">
    <!-- Sidebar -->
    <div class="sidebar" :class="{ 'show': sidebarOpen }">
      <div class="sidebar-header">
        <h1 class="h5 fw-bold text-white mb-0">Solar Toolbox</h1>
      </div>
      
      <nav class="sidebar-nav">
        <div class="px-3">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.to"
            class="nav-link"
            :class="{ 'active': $route.name === item.name || $route.path.startsWith(item.to) }"
          >
            <component :is="item.icon" />
            {{ item.label }}
          </router-link>
        </div>
      </nav>
    </div>

    <!-- Mobile sidebar overlay -->
    <div 
      v-if="sidebarOpen" 
      class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-lg-none" 
      style="z-index: 1040;" 
      @click="toggleSidebar"
    ></div>

    <!-- Main content -->
    <div class="main-content" :class="{ 'sidebar-open': sidebarOpen }">
      <!-- Top bar -->
      <div class="top-bar d-flex align-items-center justify-content-between px-3 px-lg-4">
        <button
          @click="toggleSidebar"
          class="btn btn-link d-lg-none p-2 text-secondary"
          type="button"
        >
          <Bars3Icon class="w-6 h-6" style="width: 1.5rem; height: 1.5rem;" />
        </button>

        <div class="d-flex align-items-center">
          <!-- User menu -->
          <div class="dropdown">
            <button
              class="btn btn-link d-flex align-items-center text-decoration-none dropdown-toggle"
              type="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 2rem; height: 2rem;">
                <span class="text-white fw-medium small">{{ userInitials }}</span>
              </div>
              <span class="d-none d-md-block text-dark fw-medium">{{ userName }}</span>
            </button>

            <!-- User dropdown -->
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#">Perfil</a></li>
              <li><a class="dropdown-item" href="#">Configurações</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <button @click="handleLogout" class="dropdown-item" type="button">
                  Sair
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Page content -->
      <main class="p-3 p-lg-4">
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
      navigation: [
        { name: 'dashboard', label: 'Dashboard', to: '/', icon: HomeIcon },
        { name: 'projetos', label: 'Projetos', to: '/projetos', icon: FolderIcon },
        { name: 'catalogos', label: 'Catálogos', to: '/catalogos', icon: BookOpenIcon },
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
    async initializeBootstrap() {
      try {
        await this.$nextTick()
        
        const { Dropdown } = await import('bootstrap')
        const dropdownElementList = document.querySelectorAll('.dropdown-toggle')
        dropdownElementList.forEach(dropdownToggleEl => {
          new Dropdown(dropdownToggleEl)
        })
      } catch (error) {
        console.error('Erro ao inicializar componentes Bootstrap:', error)
      }
    }
  },
  async mounted() {
    await this.initializeBootstrap()
  }
})
</script>
