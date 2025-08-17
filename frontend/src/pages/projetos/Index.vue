<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Projetos</h1>
        <p class="mt-2 text-gray-600">Gerencie seus projetos de análise solar</p>
      </div>
      <router-link to="/projetos/novo" class="btn-primary">
        <PlusIcon class="h-4 w-4 mr-2" />
        Novo Projeto
      </router-link>
    </div>

    <!-- Filters -->
    <div class="card">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="form-label">Buscar</label>
            <input
              v-model="filters.search"
              type="text"
              class="form-input"
              placeholder="Nome ou cliente..."
            />
          </div>
          <div>
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-input">
              <option value="">Todos</option>
              <option value="rascunho">Rascunho</option>
              <option value="em_analise">Em Análise</option>
              <option value="aprovado">Aprovado</option>
              <option value="rejeitado">Rejeitado</option>
            </select>
          </div>
          <div>
            <label class="form-label">Ordenar por</label>
            <select v-model="filters.sortBy" class="form-input">
              <option value="created_at">Data de criação</option>
              <option value="nome">Nome</option>
              <option value="cliente">Cliente</option>
            </select>
          </div>
          <div class="flex items-end">
            <button @click="clearFilters" class="btn-outline w-full">
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
            <router-link to="/projetos/novo" class="btn-primary">
              <PlusIcon class="h-4 w-4 mr-2" />
              Criar Primeiro Projeto
            </router-link>
          </template>
        </EmptyState>
      </div>

      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Projeto
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Cliente
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Criado em
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="projeto in projetos" :key="projeto.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ projeto.nome }}</div>
                    <div class="text-sm text-gray-500">{{ projeto.descricao }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ projeto.cliente }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                    {{ getStatusLabel(projeto.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(projeto.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <router-link
                    :to="`/projetos/${projeto.id}`"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    Ver
                  </router-link>
                  <router-link
                    :to="`/projetos/${projeto.id}/editar`"
                    class="text-gray-600 hover:text-gray-900"
                  >
                    Editar
                  </router-link>
                  <button
                    @click="confirmDelete(projeto)"
                    class="text-danger-600 hover:text-danger-900"
                  >
                    Excluir
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Mostrando {{ (currentPage - 1) * perPage + 1 }} a {{ Math.min(currentPage * perPage, totalItems) }} de {{ totalItems }} resultados
            </div>
            <div class="flex space-x-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="btn-outline"
                :class="{ 'opacity-50 cursor-not-allowed': currentPage === 1 }"
              >
                Anterior
              </button>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="btn-outline"
                :class="{ 'opacity-50 cursor-not-allowed': currentPage === totalPages }"
              >
                Próximo
              </button>
            </div>
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
    // Simular dados para demonstração
    await new Promise(resolve => setTimeout(resolve, 800))
    
    projetos.value = [
      {
        id: 1,
        nome: 'Projeto Residencial - Casa Silva',
        cliente: 'João Silva',
        descricao: 'Sistema residencial 5kWp',
        status: 'aprovado',
        created_at: new Date()
      },
      {
        id: 2,
        nome: 'Sistema Comercial - Loja ABC',
        cliente: 'Empresa ABC Ltda',
        descricao: 'Sistema comercial 20kWp',
        status: 'em_analise',
        created_at: new Date(Date.now() - 86400000)
      }
    ]
    
    totalItems.value = projetos.value.length
    totalPages.value = Math.ceil(totalItems.value / perPage.value)
  } catch (error) {
    toast.error('Erro ao carregar projetos')
    console.error(error)
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
    // await api.delete(`/projetos/${id}`)
    projetos.value = projetos.value.filter(p => p.id !== id)
    toast.success('Projeto excluído com sucesso')
  } catch (error) {
    toast.error('Erro ao excluir projeto')
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
