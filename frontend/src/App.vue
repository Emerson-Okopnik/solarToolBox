<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <router-view />
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { onMounted } from 'vue'

const authStore = useAuthStore()

onMounted(() => {
  authStore.checkAuth()
})
</script>
