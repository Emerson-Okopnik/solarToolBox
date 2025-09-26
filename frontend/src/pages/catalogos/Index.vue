<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="catalog-header">
      <h1>Catálogos</h1>
      <p>Gerencie fabricantes, módulos, inversores e dados climáticos</p>
    </div>
    <div class="catalog-page">
      <!-- Grid de cards -->
      <div class="catalog-grid">
        <router-link to="/catalogos/fabricantes" class="catalog-card">
          <h3>Fabricantes</h3>
          <p>Fabricantes de módulos e inversores</p>
          <span class="card-footer">{{ stats.fabricantes || 0 }} fabricantes →</span>
        </router-link>

        <router-link to="/catalogos/modulos" class="catalog-card">
          <h3>Módulos</h3>
          <p>Catálogo de módulos fotovoltaicos</p>
          <span class="card-footer">{{ stats.modulos || 0 }} módulos →</span>
        </router-link>

        <router-link to="/catalogos/inversores" class="catalog-card">
          <h3>Inversores</h3>
          <p>Catálogo de inversores solares</p>
          <span class="card-footer">{{ stats.inversores || 0 }} inversores →</span>
        </router-link>

        <router-link to="/catalogos/climas" class="catalog-card">
          <h3>Climas</h3>
          <p>Dados climáticos por localização</p>
          <span class="card-footer">{{ stats.climas || 0 }} localizações →</span>
        </router-link>
      </div>
    </div>
    
    <!-- Recent Projects and Quick Actions -->
    <div v-if="isAuthenticated" class="two-col">
      <div class="card">
        <div class="card-header">
          <div class="header-row">
            <h5 class="heading-5">Projetos Recentes</h5>
            <router-link to="/projetos" class="link-small-primary">Ver todos</router-link>
          </div>
        </div>
        <div class="card-body">
          <div v-if="loadingProjects" class="text-center py-8">
            <LoadingSpinner />
          </div>
          <div v-else-if="recentProjects.length === 0" class="empty-state">
            <h6 class="heading-5 mb-2">Nenhum projeto</h6>
            <p class="text-muted text-sm mb-4">Comece criando seu primeiro projeto</p>
            <router-link to="/projetos/novo" class="btn btn-primary btn-sm">
              Novo Projeto
            </router-link>
          </div>
          <div v-else class="project-list">
            <div v-for="projeto in recentProjects" :key="projeto.id" class="project-item" >
              <div>
                <h6 class="heading-5 mb-1">{{ projeto.nome }}</h6>
                <p class="text-muted text-sm mb-1">{{ projeto.cliente }}</p>
                <p class="text-muted text-sm">{{ formatDate(projeto.created_at) }}</p>
              </div>
              <div class="row-gap-2">
                <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                  {{ getStatusLabel(projeto.status) }}
                </span>
                <router-link :to="`/projetos/${projeto.id}`" class="btn btn-secondary btn-sm" >
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
            <router-link to="/projetos/novo" class="quick-action-item">
              <div class="stat-icon bg-primary">
                <PlusIcon/>
              </div>
              <div>
                <p class="font-semibold text-dark mb-1">Novo Projeto</p>
                <p class="text-muted text-sm">Criar um novo projeto de análise</p>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { ArrowRightIcon, PlusIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { projetosService } from '@/services/projetos'
import { catalogosService } from '@/services/catalogos'
import { useAuthStore } from '@/stores/auth'

const stats = ref({})
const recentProjects = ref([])
const loadingProjects = ref(false)
const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)

const loadStats = async () => {
  try {
    stats.value = await catalogosService.obterEstatisticas()
  } catch (error) {
    console.error('Erro ao carregar dados dos catálogos:', error)
    stats.value = {}
  }
}

const loadRecentProjects = async () => {
  loadingProjects.value = true
  try {
    const response = await projetosService.listar({ per_page: 2 })
    recentProjects.value = response.data?.data || []
  } catch (error) {
    console.error('Erro ao carregar projetos recentes:', error)
    recentProjects.value = []
  } finally {
    loadingProjects.value = false
  }
}

const formatDate = date => {
  if (!date) return ''
  const parsed = new Date(date)
  return isNaN(parsed.getTime())
    ? ''
    : format(parsed, 'dd/MM/yyyy', { locale: ptBR })
}

const getStatusLabel = status => {
  const labels = {
    rascunho: 'Rascunho',
    em_analise: 'Em Análise',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado'
  }
  return labels[status] || status
}

const getStatusBadgeClass = status => {
  const classes = {
    rascunho: 'badge-secondary',
    em_analise: 'badge-warning',
    aprovado: 'badge-success',
    rejeitado: 'badge-danger'
  }
  return classes[status] || 'badge-secondary'
}

watch(
  isAuthenticated,
  async autenticado => {
    if (autenticado) {
      await loadRecentProjects()
    } else {
      loadingProjects.value = false
      recentProjects.value = []
    }
  },
  { immediate: true }
)

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.catalog-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.catalog-header h1 {
  font-size: 28px;
  font-weight: bold;
  margin: 0;
  color: #222;
}

.catalog-header p {
  margin-top: 6px;
  color: #555;
}

.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.catalog-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;

  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;

  background: #fff;
  text-decoration: none;
  color: inherit;

  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.catalog-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.catalog-card h3 {
  margin-top: 15px;
  font-size: 18px;
  color: #222;
}

.catalog-card p {
  font-size: 14px;
  color: #666;
  margin: 10px 0 15px;
}

.card-footer {
  font-size: 14px;
  font-weight: 500;
  color: #1a73e8;
}

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

/* Layout 2 colunas responsivo */
.two-col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px; /* equivalente ao gap-6 */
}

/* A partir de ~desktop (ajuste conforme seu breakpoint) */
@media (min-width: 992px) {
  .two-col {
    grid-template-columns: 1fr 1fr;
    align-items: start; /* impede esticar verticalmente */
  }
}

/* Pequenas utilitárias para substituir as classes Tailwind usadas no trecho */
.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.link-small-primary {
  font-size: 0.875rem; /* ~14px */
  font-weight: 500;
  color: var(--primary, #1a73e8);
  text-decoration: none;
}
.link-small-primary:hover {
  text-decoration: underline;
}

.row-gap-2 {
  display: flex;
  align-items: center;
  gap: 8px; /* ~gap-2 */
}

/* (Opcional) garantir que ambas as cards tenham mesma “tensão” visual */
.two-col .card {
  height: 100%;
}
</style>