<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
      <p class="mt-2 text-gray-600">Visão geral dos seus projetos e atividades recentes</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-primary-100 rounded-lg flex items-center justify-center">
                <FolderIcon class="h-5 w-5 text-primary-600" />
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Projetos</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.projetos || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-solar-100 rounded-lg flex items-center justify-center">
                <CpuChipIcon class="h-5 w-5 text-solar-600" />
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Arranjos</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.arranjos || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-success-100 rounded-lg flex items-center justify-center">
                <CheckCircleIcon class="h-5 w-5 text-success-600" />
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Análises</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.execucoes || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 bg-danger-100 rounded-lg flex items-center justify-center">
                <ExclamationTriangleIcon class="h-5 w-5 text-danger-600" />
              </div>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-500">Problemas</p>
              <p class="text-2xl font-semibold text-gray-900">{{ stats.problemas || 0 }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Projects -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <div class="card">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Projetos Recentes</h3>
            <router-link to="/projetos" class="text-sm text-primary-600 hover:text-primary-500">
              Ver todos
            </router-link>
          </div>
        </div>
        <div class="card-body">
          <div v-if="loading" class="flex justify-center py-4">
            <LoadingSpinner />
          </div>
          <div v-else-if="recentProjects.length === 0" class="text-center py-8">
            <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum projeto</h3>
            <p class="mt-1 text-sm text-gray-500">Comece criando seu primeiro projeto</p>
            <div class="mt-6">
              <router-link to="/projetos/novo" class="btn-primary">
                Novo Projeto
              </router-link>
            </div>
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="projeto in recentProjects"
              :key="projeto.id"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div>
                <h4 class="text-sm font-medium text-gray-900">{{ projeto.nome }}</h4>
                <p class="text-sm text-gray-500">{{ projeto.cliente }}</p>
                <p class="text-xs text-gray-400">{{ formatDate(projeto.created_at) }}</p>
              </div>
              <div class="flex items-center space-x-2">
                <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                  {{ getStatusLabel(projeto.status) }}
                </span>
                <router-link
                  :to="`/projetos/${projeto.id}`"
                  class="text-primary-600 hover:text-primary-500"
                >
                  <ArrowRightIcon class="h-4 w-4" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card">
        <div class="card-header">
          <h3 class="text-lg font-medium text-gray-900">Ações Rápidas</h3>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 gap-4">
            <router-link
              to="/projetos/novo"
              class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-300 hover:bg-primary-50 transition-colors group"
            >
              <div class="flex-shrink-0">
                <PlusIcon class="h-6 w-6 text-gray-400 group-hover:text-primary-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Novo Projeto</p>
                <p class="text-sm text-gray-500">Criar um novo projeto de análise</p>
              </div>
            </router-link>

            <router-link
              to="/catalogos"
              class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-300 hover:bg-primary-50 transition-colors group"
            >
              <div class="flex-shrink-0">
                <BookOpenIcon class="h-6 w-6 text-gray-400 group-hover:text-primary-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">Gerenciar Catálogos</p>
                <p class="text-sm text-gray-500">Módulos, inversores e fabricantes</p>
              </div>
            </router-link>
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
  FolderIcon,
  CpuChipIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ArrowRightIcon,
  PlusIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import api from '@/services/api'

const loading = ref(true)
const stats = ref({})
const recentProjects = ref([])

const loadDashboardData = async () => {
  try {
    // Simular carregamento de dados
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Aqui você faria as chamadas reais para a API
    stats.value = {
      projetos: 12,
      arranjos: 45,
      execucoes: 28,
      problemas: 3
    }
    
    recentProjects.value = [
      {
        id: 1,
        nome: 'Projeto Residencial - Casa Silva',
        cliente: 'João Silva',
        status: 'aprovado',
        created_at: new Date()
      },
      {
        id: 2,
        nome: 'Sistema Comercial - Loja ABC',
        cliente: 'Empresa ABC Ltda',
        status: 'em_analise',
        created_at: new Date(Date.now() - 86400000)
      }
    ]
  } catch (error) {
    console.error('Erro ao carregar dados do dashboard:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (date) => {
  return format(new Date(date), 'dd/MM/yyyy', { locale: ptBR })
}

const getStatusLabel = (status) => {
  const labels = {
    rascunho: 'Rascunho',
    em_analise: 'Em Análise',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    rascunho: 'badge-info',
    em_analise: 'badge-warning',
    aprovado: 'badge-success',
    rejeitado: 'badge-danger'
  }
  return classes[status] || 'badge-info'
}

onMounted(() => {
  loadDashboardData()
})
</script>
