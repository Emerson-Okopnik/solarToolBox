<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Catálogos</h1>
      <p class="mt-2 text-gray-600">Gerencie fabricantes, módulos, inversores e dados climáticos</p>
    </div>

    <!-- Catalog Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <router-link
        to="/catalogos/fabricantes"
        class="card hover:shadow-lg transition-shadow duration-200 group"
      >
        <div class="card-body text-center">
          <div class="mx-auto h-12 w-12 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors">
            <BuildingOfficeIcon class="h-6 w-6 text-primary-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Fabricantes</h3>
          <p class="mt-2 text-sm text-gray-500">Gerencie fabricantes de módulos e inversores</p>
          <div class="mt-4 flex items-center justify-center text-sm text-primary-600">
            <span>{{ stats.fabricantes || 0 }} fabricantes</span>
            <ArrowRightIcon class="ml-2 h-4 w-4" />
          </div>
        </div>
      </router-link>

      <router-link
        to="/catalogos/modulos"
        class="card hover:shadow-lg transition-shadow duration-200 group"
      >
        <div class="card-body text-center">
          <div class="mx-auto h-12 w-12 bg-solar-100 rounded-lg flex items-center justify-center group-hover:bg-solar-200 transition-colors">
            <RectangleStackIcon class="h-6 w-6 text-solar-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Módulos</h3>
          <p class="mt-2 text-sm text-gray-500">Catálogo de módulos fotovoltaicos</p>
          <div class="mt-4 flex items-center justify-center text-sm text-primary-600">
            <span>{{ stats.modulos || 0 }} módulos</span>
            <ArrowRightIcon class="ml-2 h-4 w-4" />
          </div>
        </div>
      </router-link>

      <router-link
        to="/catalogos/inversores"
        class="card hover:shadow-lg transition-shadow duration-200 group"
      >
        <div class="card-body text-center">
          <div class="mx-auto h-12 w-12 bg-success-100 rounded-lg flex items-center justify-center group-hover:bg-success-200 transition-colors">
            <CpuChipIcon class="h-6 w-6 text-success-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Inversores</h3>
          <p class="mt-2 text-sm text-gray-500">Catálogo de inversores solares</p>
          <div class="mt-4 flex items-center justify-center text-sm text-primary-600">
            <span>{{ stats.inversores || 0 }} inversores</span>
            <ArrowRightIcon class="ml-2 h-4 w-4" />
          </div>
        </div>
      </router-link>

      <router-link
        to="/catalogos/climas"
        class="card hover:shadow-lg transition-shadow duration-200 group"
      >
        <div class="card-body text-center">
          <div class="mx-auto h-12 w-12 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors">
            <CloudIcon class="h-6 w-6 text-primary-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Climas</h3>
          <p class="mt-2 text-sm text-gray-500">Dados climáticos por localização</p>
          <div class="mt-4 flex items-center justify-center text-sm text-primary-600">
            <span>{{ stats.climas || 0 }} localizações</span>
            <ArrowRightIcon class="ml-2 h-4 w-4" />
          </div>
        </div>
      </router-link>
    </div>

    <!-- Recent Activity -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-lg font-medium text-gray-900">Atividade Recente</h3>
      </div>
      <div class="card-body">
        <div v-if="loading" class="flex justify-center py-4">
          <LoadingSpinner />
        </div>
        <div v-else-if="recentActivity.length === 0" class="text-center py-8">
          <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma atividade recente</h3>
          <p class="mt-1 text-sm text-gray-500">As alterações nos catálogos aparecerão aqui</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg"
          >
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center">
                <component :is="getActivityIcon(activity.type)" class="h-4 w-4 text-gray-600" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
              <p class="text-sm text-gray-500">{{ formatDate(activity.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import {
  BuildingOfficeIcon,
  RectangleStackIcon,
  CpuChipIcon,
  CloudIcon,
  ArrowRightIcon,
  ClockIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'

const loading = ref(true)
const stats = ref({})
const recentActivity = ref([])

const loadCatalogData = async () => {
  try {
    // Simular carregamento de dados
    await new Promise(resolve => setTimeout(resolve, 800))
    
    stats.value = {
      fabricantes: 25,
      modulos: 150,
      inversores: 45,
      climas: 12
    }
    
    recentActivity.value = [
      {
        id: 1,
        type: 'create',
        description: 'Novo módulo "Canadian Solar CS3W-400P" adicionado',
        created_at: new Date()
      },
      {
        id: 2,
        type: 'update',
        description: 'Inversor "Fronius Primo 5.0-1" atualizado',
        created_at: new Date(Date.now() - 3600000)
      },
      {
        id: 3,
        type: 'create',
        description: 'Fabricante "Jinko Solar" adicionado',
        created_at: new Date(Date.now() - 7200000)
      }
    ]
  } catch (error) {
    console.error('Erro ao carregar dados dos catálogos:', error)
  } finally {
    loading.value = false
  }
}

const getActivityIcon = (type) => {
  const icons = {
    create: PlusIcon,
    update: PencilIcon,
    delete: TrashIcon
  }
  return icons[type] || PlusIcon
}

const formatDate = (date) => {
  if (!date) return ''
  const parsed = new Date(date)
  return isNaN(parsed.getTime())
    ? ''
    : format(parsed, 'dd/MM/yyyy HH:mm', { locale: ptBR })
}

onMounted(() => {
  loadCatalogData()
})
</script>
