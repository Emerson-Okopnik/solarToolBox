<template>
  <div class="container">
    <div class="mb-6">
      <h1 class="heading-1 mb-2">Dashboard</h1>
      <p class="text-muted">Visão geral dos seus projetos</p>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-8">
      <div class="card card-hover">
        <div class="card-body">
          <div class="flex items-center">
            <div class="stat-icon bg-primary">
              <FolderIcon class="nav-icon" />
            </div>
            <div>
              <p class="text-sm text-muted mb-1">Projetos</p>
              <p class="heading-4">{{ stats.projetos || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-hover">
        <div class="card-body">
          <div class="flex items-center">
            <div class="stat-icon bg-warning">
              <CpuChipIcon class="nav-icon" />
            </div>
            <div>
              <p class="text-sm text-muted mb-1">Arranjos</p>
              <p class="heading-4">{{ stats.arranjos || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-hover">
        <div class="card-body">
          <div class="flex items-center">
            <div class="stat-icon bg-success">
              <CheckCircleIcon class="nav-icon" />
            </div>
            <div>
              <p class="text-sm text-muted mb-1">Análises</p>
              <p class="heading-4">{{ stats.execucoes || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card card-hover">
        <div class="card-body">
          <div class="flex items-center">
            <div class="stat-icon bg-danger">
              <ExclamationTriangleIcon class="nav-icon" />
            </div>
            <div>
              <p class="text-sm text-muted mb-1">Problemas</p>
              <p class="heading-4">{{ stats.problemas || 0 }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
      <div class="card">
        <div class="card-header">
          <div class="flex items-center justify-between">
            <h5 class="heading-5">Projetos Recentes</h5>
            <router-link to="/projetos" class="text-primary text-sm font-medium">
              Ver todos
            </router-link>
          </div>
        </div>
        <div class="card-body">
          <div v-if="loading" class="text-center py-8">
            <LoadingSpinner />
          </div>
          <div v-else-if="recentProjects.length === 0" class="empty-state">
            <FolderIcon class="empty-icon" />
            <h6 class="heading-5 mb-2">Nenhum projeto</h6>
            <p class="text-muted text-sm mb-4">Comece criando seu primeiro projeto</p>
            <router-link to="/projetos/novo" class="btn btn-primary btn-sm">
              Novo Projeto
            </router-link>
          </div>
          <div v-else class="project-list">
            <div
              v-for="projeto in recentProjects"
              :key="projeto.id"
              class="project-item"
            >
              <div>
                <h6 class="heading-5 mb-1">{{ projeto.nome }}</h6>
                <p class="text-muted text-sm mb-1">{{ projeto.cliente }}</p>
                <p class="text-muted text-sm">{{ formatDate(projeto.created_at) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                  {{ getStatusLabel(projeto.status) }}
                </span>
                <router-link
                  :to="`/projetos/${projeto.id}`"
                  class="btn btn-secondary btn-sm"
                >
                  <ArrowRightIcon class="nav-icon" />
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h5 class="heading-5">Ações Rápidas</h5>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <router-link
              to="/projetos/novo"
              class="quick-action-item"
            >
              <div class="stat-icon bg-primary">
                <PlusIcon class="nav-icon" />
              </div>
              <div>
                <p class="font-semibold text-dark mb-1">Novo Projeto</p>
                <p class="text-muted text-sm">Criar um novo projeto de análise</p>
              </div>
            </router-link>

            <router-link
              to="/catalogos"
              class="quick-action-item"
            >
              <div class="stat-icon bg-warning">
                <BookOpenIcon class="nav-icon" />
              </div>
              <div>
                <p class="font-semibold text-dark mb-1">Gerenciar Catálogos</p>
                <p class="text-muted text-sm">Módulos, inversores e fabricantes</p>
              </div>
            </router-link>
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
        rascunho: 'badge-secondary',
        em_analise: 'badge-warning',
        aprovado: 'badge-success',
        rejeitado: 'badge-danger'
      }
      return classes[status] || 'badge-secondary'
    }
  },
  async mounted() {
    await this.loadDashboardData()
  }
})
</script>

<style scoped>
.stat-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: var(--space-3);
  color: var(--white);
}

.project-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.project-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-3);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius);
  transition: all 0.2s ease;
}

.project-item:hover {
  background-color: var(--gray-50);
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.quick-action-item {
  display: flex;
  align-items: center;
  padding: var(--space-3);
  border: 2px dashed var(--gray-300);
  border-radius: var(--radius);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
}

.quick-action-item:hover {
  border-color: var(--primary);
  background-color: var(--gray-50);
  color: inherit;
}

@media (max-width: 768px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
