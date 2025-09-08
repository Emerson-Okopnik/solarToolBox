<template>
  <div class="d-flex flex-column gap-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h1 class="h3 fw-bold mb-0">Projetos</h1>
        <p class="text-muted mb-0">Gerencie seus projetos de análise solar</p>
      </div>
      <router-link to="/projetos/novo" class="btn btn-primary d-flex align-items-center">
        <PlusIcon class="me-2" style="width:1rem;height:1rem" />
        Novo Projeto
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="card-body">
        <div class="filters-grid">
          <div class="form-group">
            <label class="form-label">Buscar</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Nome ou cliente..."
            />
          </div>

          <div class="form-group">
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-select">
              <option value="">Todos</option>
              <option value="rascunho">Rascunho</option>
              <option value="em_analise">Em Análise</option>
              <option value="aprovado">Aprovado</option>
              <option value="rejeitado">Rejeitado</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Ordenar por</label>
            <select v-model="filters.sortBy" class="form-select">
              <option value="created_at">Data de criação</option>
              <option value="nome">Nome</option>
              <option value="cliente">Cliente</option>
            </select>
          </div>

          <div class="form-actions">
            <button @click="clearFilters" type="button" class="btn-link-clear">
              Limpar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Projects List -->
    <div class="card">
      <div v-if="loading" class="card-body">
        <LoadingSpinner center text="Carregando projetos..." />
      </div>
      
      <div v-else-if="projetos.length === 0" class="card-body">
        <EmptyState
          title="Nenhum projeto encontrado"
          description="Você ainda não criou nenhum projeto ou nenhum projeto corresponde aos filtros aplicados."
          :icon="FolderIcon"
        >
          <template #action>
            <router-link to="/projetos/novo" class="btn btn-primary d-inline-flex align-items-center">
              <PlusIcon class="me-2" style="width:1rem;height:1rem" />
              Criar Primeiro Projeto
            </router-link>
          </template>
        </EmptyState>
      </div>

      <div v-else>
        <div class="table-responsive projects-table-wrap">
          <table class="table table-hover align-middle projects-table">
            <colgroup>
              <col class="col-projeto" />
              <col class="col-cliente" />
              <col class="col-status" />
              <col class="col-created" />
              <col class="col-actions" />
            </colgroup>
            <thead>
              <tr>
                <th>Projeto</th>
                <th>Cliente</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="projeto in projetos" :key="projeto.id">
                <td> 
                  <div class="cell-projeto">
                    <router-link :to="`/projetos/${projeto.id}`" class="action-link list">
                      <div class="name">{{ projeto.nome }}</div>
                      <div class="desc text-truncate-2">{{ projeto.descricao }}</div>
                    </router-link>
                  </div>
                </td>
                <td class="text-truncate">{{ projeto.cliente }}</td>
                <td>
                  <span class="badge rounded-pill" :class="getStatusBadgeClass(projeto.status)">
                    {{ getStatusLabel(projeto.status) }}
                  </span>
                </td>
                <td class="text-muted text-nowrap">{{ formatDate(projeto.created_at) }}</td>
                <td class="actions">
                  <router-link :to="`/projetos/${projeto.id}`" class="action-link view">Ver</router-link>
                  <router-link :to="`/projetos/${projeto.id}/editar`" class="action-link edit">Editar</router-link>
                  <button @click="confirmDelete(projeto)" class="action-link delete" type="button">Excluir</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div class="small text-muted">
            Mostrando {{ (currentPage - 1) * perPage + 1 }} a {{ Math.min(currentPage * perPage, totalItems) }} de {{ totalItems }} resultados
          </div>
          <div class="d-flex gap-2">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              class="btn btn-outline-secondary"
            >
              Anterior
            </button>
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="btn btn-outline-secondary"
            >
              Próximo
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { PlusIcon, FolderIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useToast } from 'vue-toastification'
import api from '@/services/api'

const toast = useToast()

const loading = ref(true)
const projetos = ref([])
const currentPage = ref(1)
const perPage = ref(15)
const totalItems = ref(0)
const totalPages = ref(0)

const filters = reactive({
  search: '',
  status: '',
  sortBy: 'created_at'
})

const loadProjetos = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      sort_by: filters.sortBy
    }
    const response = await api.get('/projetos', { params })
    const paginated = response.data.data
    projetos.value = paginated.data
    totalItems.value = paginated.total
    totalPages.value = paginated.last_page
  } catch (error) {
    toast.error('Erro ao carregar projetos')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.sortBy = 'created_at'
}

const confirmDelete = (projeto) => {
  if (confirm(`Tem certeza que deseja excluir o projeto "${projeto.nome}"?`)) {
    deleteProjeto(projeto.id)
  }
}

const deleteProjeto = async (id) => {
  try {
    await api.delete(`/projetos/${id}`)
    projetos.value = projetos.value.filter(p => p.id !== id)
    toast.success('Projeto excluído com sucesso')
  } catch (error) {
    toast.error('Erro ao excluir projeto')
  }
}

const formatDate = (date) => {
  if (!date) return ''
  const parsed = new Date(date)
  return isNaN(parsed.getTime())
    ? ''
    : format(parsed, 'dd/MM/yyyy', { locale: ptBR })
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
    rascunho: 'text-bg-info',
    em_analise: 'text-bg-warning',
    aprovado: 'text-bg-success',
    rejeitado: 'text-bg-danger'
  }
  return classes[status] || 'text-bg-secondary'
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    loadProjetos()
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    loadProjetos()
  }
}

// Watch filters for auto-search
watch(filters, () => {
  currentPage.value = 1
  loadProjetos()
}, { deep: true })

onMounted(() => {
  loadProjetos()
})
</script>

<style scoped>
/* Grid dos filtros */
.filters-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr auto; /* buscar um pouco maior */
  column-gap: 16px;
  row-gap: 12px;
  align-items: end; /* alinha todos pela base */
}

/* Responsivo: 2 colunas em tablets, 1 no mobile */
@media (max-width: 992px) {
  .filters-grid {
    grid-template-columns: 1fr 1fr;
  }
  .filters-grid .form-actions {
    justify-self: end;
  }
}
@media (max-width: 576px) {
  .filters-grid {
    grid-template-columns: 1fr;
  }
}

/* Força altura igual em inputs/selects */
.filters-grid .form-control,
.filters-grid .form-select {
  height: 42px;
}

/* Rótulos com hierarquia sutil */
.filters-grid .form-label {
  font-weight: 600;
  color: #495057;
}

/* Ação de limpar como link, sem “puxar” layout */
.btn-link-clear {
  background: none;
  border: 0;
  color: #0d6efd;           /* azul Bootstrap */
  font-weight: 500;
  padding: 0;
  cursor: pointer;
  white-space: nowrap;       /* mantém em uma linha */
}
.btn-link-clear:hover {
  text-decoration: underline;
}

/* Garante alinhamento vertical perfeito do bloco de ações */
.form-actions {
  display: flex;
  align-items: flex-end;
  height: 100%;
}

/* Deixe a coluna de ações mais estreita e previsível */
.projects-table col.col-actions { width: 160px; } /* ajuste fino: 140–180px */

/* Centralizar header e conteúdo da coluna Ações */
.projects-table th:last-child,
.projects-table td.actions {
  text-align: center;
}

/* Não permitir quebra de linha dentro das ações */
.projects-table td.actions {
  white-space: nowrap;
}

/* Links de ação enxutos (sem estilos de botão) */
.action-link {
  display: inline-block;
  font-size: 0.9rem;
  font-weight: 500;
  text-decoration: none;
  background: none;
  border: 0;
  padding: 0;
  margin: 0 6px;
  cursor: pointer;
}

/* Cores */
.action-link.view,
.action-link.edit { color: #0d6efd; }
.action-link.delete { color: #dc3545; }
.action-link.list { color: #000000; }
</style>