<template>
  <div id="app" class="min-vh-100 bg-light">
    <router-view />
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { useAuthStore } from '@/stores/auth'

export default defineComponent({
  name: 'App',
  setup() {
    const authStore = useAuthStore()
    
    return {
      authStore
    }
  },
  async mounted() {
    try {
      await this.authStore.checkAuth()
    } catch (error) {
      console.error('Erro ao verificar autenticação:', error)
    }
  }
})
</script>
