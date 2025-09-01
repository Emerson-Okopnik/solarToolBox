<template>
  <div class="container-fluid">
    <!-- Header simplificado -->
    <div class="row mb-4">
      <div class="col">
        <h1 class="h2 fw-bold text-dark mb-1">Dashboard</h1>
        <p class="text-muted">Visão geral dos seus projetos</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="card card-custom h-100">
          <div class="card-body-custom">
            <div class="d-flex align-items-center">
              <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                   style="width: 2.5rem; height: 2.5rem; background-color: var(--primary-color); color: white;">
                <FolderIcon style="width: 1.25rem; height: 1.25rem;" />
              </div>
              <div>
                <p class="small text-muted mb-1">Projetos</p>
                <p class="h4 fw-bold text-dark mb-0">{{ stats.projetos || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card card-custom h-100">
          <div class="card-body-custom">
            <div class="d-flex align-items-center">
              <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                   style="width: 2.5rem; height: 2.5rem; background-color: var(--solar-color); color: white;">
                <CpuChipIcon style="width: 1.25rem; height: 1.25rem;" />
              </div>
              <div>
                <p class="small text-muted mb-1">Arranjos</p>
                <p class="h4 fw-bold text-dark mb-0">{{ stats.arranjos || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card card-custom h-100">
          <div class="card-body-custom">
            <div class="d-flex align-items-center">
              <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                   style="width: 2.5rem; height: 2.5rem; background-color: var(--success-color); color: white;">
                <CheckCircleIcon style="width: 1.25rem; height: 1.25rem;" />
              </div>
              <div>
                <p class="small text-muted mb-1">Análises</p>
                <p class="h4 fw-bold text-dark mb-0">{{ stats.execucoes || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-lg-3">
        <div class="card card-custom h-100">
          <div class="card-body-custom">
            <div class="d-flex align-items-center">
              <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                   style="width: 2.5rem; height: 2.5rem; background-color: var(--danger-color); color: white;">
                <ExclamationTriangleIcon style="width: 1.25rem; height: 1.25rem;" />
              </div>
              <div>
                <p class="small text-muted mb-1">Problemas</p>
                <p class="h4 fw-bold text-dark mb-0">{{ stats.problemas || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Projects -->
    <div class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="card card-custom h-100">
          <div class="card-header-custom">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="fw-semibold mb-0">Projetos Recentes</h5>
              <router-link to="/projetos" class="btn btn-link btn-sm p-0">
                Ver todos
              </router-link>
            </div>
          </div>
          <div class="card-body-custom">
            <div v-if="loading" class="text-center py-4">
              <LoadingSpinner />
            </div>
            <div v-else-if="recentProjects.length === 0" class="text-center py-4">
              <FolderIcon class="mx-auto text-muted mb-3" style="width: 3rem; height: 3rem; opacity: 0.5;" />
              <h6 class="fw-semibold mb-2">Nenhum projeto</h6>
              <p class="text-muted small mb-3">Comece criando seu primeiro projeto</p>
              <router-link to="/projetos/novo" class="btn btn-primary btn-sm">
                Novo Projeto
              </router-link>
            </div>
            <div v-else class="d-flex flex-column gap-3">
              <div
                v-for="projeto in recentProjects"
                :key="projeto.id"
                class="d-flex align-items-center justify-content-between p-3 border rounded-2"
              >
                <div>
                  <h6 class="fw-semibold mb-1">{{ projeto.nome }}</h6>
                  <p class="text-muted small mb-1">{{ projeto.cliente }}</p>
                  <p class="text-muted" style="font-size: 0.75rem;">{{ formatDate(projeto.created_at) }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                    {{ getStatusLabel(projeto.status) }}
                  </span>
                  <router-link
                    :to="`/projetos/${projeto.id}`"
                    class="btn btn-link btn-sm p-1"
                  >
                    <ArrowRightIcon style="width: 1rem; height: 1rem;" />
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-12 col-lg-6">
        <div class="card card-custom h-100">
          <div class="card-header-custom">
            <h5 class="fw-semibold mb-0">Ações Rápidas</h5>
          </div>
          <div class="card-body-custom">
            <div class="d-flex flex-column gap-3">
              <router-link
                to="/projetos/novo"
                class="d-flex align-items-center p-3 border-2 border-dashed rounded-2 text-decoration-none"
                style="border-color: var(--gray-300);"
              >
                <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                     style="width: 2.5rem; height: 2.5rem; background-color: var(--primary-color); color: white;">
                  <PlusIcon style="width: 1.25rem; height: 1.25rem;" />
                </div>
                <div>
                  <p class="fw-semibold text-dark mb-1">Novo Projeto</p>
                  <p class="text-muted small mb-0">Criar um novo projeto de análise</p>
                </div>
              </router-link>

              <router-link
                to="/catalogos"
                class="d-flex align-items-center p-3 border-2 border-dashed rounded-2 text-decoration-none"
                style="border-color: var(--gray-300);"
              >
                <div class="rounded-2 d-flex align-items-center justify-content-center me-3" 
                     style="width: 2.5rem; height: 2.5rem; background-color: var(--solar-color); color: white;">
                  <BookOpenIcon style="width: 1.25rem; height: 1.25rem;" />
                </div>
                <div>
                  <p class="fw-semibold text-dark mb-2">Gerenciar Catálogos</p>
                  <p class="text-muted small mb-0">Módulos, inversores e fabricantes</p>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
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

export default defineComponent({
  name: 'Dashboard',
  components: {
    LoadingSpinner,
    FolderIcon,
    CpuChipIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowRightIcon,
    PlusIcon,
    BookOpenIcon
  },
  data() {
    return {
      loading: true,
      stats: {},
      recentProjects: []
    }
  },
  methods: {
    async loadDashboardData() {
      try {
        // Simular carregamento de dados
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Aqui você faria as chamadas reais para a API
        this.stats = {
          projetos: 12,
          arranjos: 45,
          execucoes: 28,
          problemas: 3
        }
        
        this.recentProjects = [
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
        this.loading = false
      }
    },
    formatDate(date) {
      if (!date) return ''
      const parsed = new Date(date)
      return isNaN(parsed.getTime())
        ? ''
        : format(parsed, 'dd/MM/yyyy', { locale: ptBR })
    },
    getStatusLabel(status) {
      const labels = {
        rascunho: 'Rascunho',
        em_analise: 'Em Análise',
        aprovado: 'Aprovado',
        rejeitado: 'Rejeitado'
      }
      return labels[status] || status
    },
    getStatusBadgeClass(status) {
      const classes = {
        rascunho: 'bg-secondary text-white',
        em_analise: 'badge-solar',
        aprovado: 'bg-success text-white',
        rejeitado: 'bg-danger text-white'
      }
      return classes[status] || 'bg-secondary text-white'
    }
  },
  async mounted() {
    await this.loadDashboardData()
  }
})
</script>
