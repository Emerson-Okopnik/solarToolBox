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
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Buscar</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Nome ou cliente..."
            />
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-select">
              <option value="">Todos</option>
              <option value="rascunho">Rascunho</option>
              <option value="em_analise">Em Análise</option>
              <option value="aprovado">Aprovado</option>
              <option value="rejeitado">Rejeitado</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Ordenar por</label>
            <select v-model="filters.sortBy" class="form-select">
              <option value="created_at">Data de criação</option>
              <option value="nome">Nome</option>
              <option value="cliente">Cliente</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button @click="clearFilters" class="btn btn-outline-secondary w-100">
              Limpar Filtros
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
        <div class="table-responsive">
          <table class="table">
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
                  <div>
                    <div class="fw-medium">{{ projeto.nome }}</div>
                    <div class="text-muted small">{{ projeto.descricao }}</div>
                  </div>
                </td>
                <td>{{ projeto.cliente }}</td>
                <td>
                  <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                    {{ getStatusLabel(projeto.status) }}
                  </span>
                </td>
                <td class="text-muted">{{ formatDate(projeto.created_at) }}</td>
                <td class="d-flex gap-2">
                  <router-link
                    :to="`/projetos/${projeto.id}`"
                    class="btn btn-link p-0"
                  >
                    Ver
                  </router-link>
                  <router-link
                    :to="`/projetos/${projeto.id}/editar`"
                    class="btn btn-link p-0"
                  >
                    Editar
                  </router-link>
                  <button
                    @click="confirmDelete(projeto)"
                    class="btn btn-link text-danger p-0"
                  >
                    Excluir
                  </button>
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
    rascunho: 'bg-info text-dark',
    em_analise: 'bg-warning text-dark',
    aprovado: 'bg-success',
    rejeitado: 'bg-danger'
  }
   return classes[status] || 'bg-secondary'
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
