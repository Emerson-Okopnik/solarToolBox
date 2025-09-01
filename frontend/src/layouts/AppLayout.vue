<template>
  <div class="min-vh-100" style="background-color: var(--gray-50);">
    <!-- Sidebar simplificado -->
    <div class="sidebar" :class="{ 'show': sidebarOpen }">
      <div class="sidebar-header">
        <h1 class="h5 fw-bold mb-0">Solar Toolbox</h1>
      </div>
      
      <nav class="sidebar-nav">
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
      </nav>
    </div>

    <!-- Mobile overlay -->
    <div 
      v-if="sidebarOpen" 
      class="position-fixed top-0 start-0 w-100 h-100 d-lg-none" 
      style="z-index: 1040; background-color: rgba(0,0,0,0.5);" 
      @click="toggleSidebar"
    ></div>

    <!-- Conteúdo principal simplificado -->
    <div class="main-content">
      <div class="top-bar d-flex align-items-center justify-content-between px-4">
        <button
          @click="toggleSidebar"
          class="btn btn-link d-lg-none p-2"
          type="button"
        >
          <Bars3Icon style="width: 1.5rem; height: 1.5rem;" />
        </button>

        <div class="dropdown">
          <button
            class="btn btn-link d-flex align-items-center dropdown-toggle"
            type="button"
            data-bs-toggle="dropdown"
          >
            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" 
                 style="width: 2rem; height: 2rem; background-color: var(--primary-color);">
              <span class="text-white fw-medium small">{{ userInitials }}</span>
            </div>
            <span class="d-none d-md-block fw-medium">{{ userName }}</span>
          </button>

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

      <main class="p-4">
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
      navigation: [
        { name: 'dashboard', label: 'Dashboard', to: '/', icon: 'HomeIcon' },
        { name: 'projetos', label: 'Projetos', to: '/projetos', icon: 'FolderIcon' },
        { name: 'catalogos', label: 'Catálogos', to: '/catalogos', icon: 'BookOpenIcon' },
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
