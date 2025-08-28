<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              {{ projetoData.nome }}
            </h1>
            <p class="text-gray-600 mt-1">
              Cliente: {{ projetoData.cliente }}
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="handleExecutarAnalise"
              :disabled="isLoading"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="isLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Executando...' : 'Executar Análise' }}
            </button>
            <button
              @click="showForm = true"
              class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"
            >
              Novo Arranjo
            </button>
          </div>
        </div>
      </div>

      <!-- Arranjos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div
          v-for="arranjo in arranjosList"
          :key="arranjo.id"
          class="bg-white rounded-lg shadow-sm p-6"
        >
          <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ arranjo.nome }}</h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Módulo:</span>
              <span class="font-medium">{{ getModuloNome(arranjo) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Inversor:</span>
              <span class="font-medium">{{ getInversorNome(arranjo) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Orientação:</span>
              <span class="font-medium">{{ arranjo.azimute }}° / {{ arranjo.inclinacao }}°</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Strings:</span>
              <span class="font-medium">{{ getStringsCount(arranjo) }}</span>
            </div>
          </div>
          
          <!-- Strings -->
          <div v-if="hasStrings(arranjo)" class="mt-4 pt-4 border-t">
            <h4 class="font-medium text-gray-900 mb-2">Strings:</h4>
            <div class="space-y-1">
              <div
                v-for="string in arranjo.strings"
                :key="string.id"
                class="flex justify-between text-sm"
              >
                <span>MPPT {{ string.mppt_id }} - {{ string.conexao }}</span>
                <span>{{ string.ns }}s × {{ string.np }}p</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resultados da Análise -->
      <div v-if="execucaoData" class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Resultados da Análise</h2>
        
        <!-- Status Geral -->
        <div class="mb-6">
          <div class="flex items-center gap-2 mb-2">
            <div
              :class="getStatusClass(execucaoData.status)"
            ></div>
            <span class="font-medium capitalize">{{ formatStatus(execucaoData.status) }}</span>
          </div>
          <p class="text-gray-600">{{ execucaoData.observacoes }}</p>
        </div>

        <!-- Checagens -->
        <div v-if="checagensList.length > 0" class="mb-6">
          <h3 class="font-semibold text-gray-900 mb-3">Checagens:</h3>
          <div class="space-y-2">
            <div
              v-for="checagem in checagensList"
              :key="checagem.id"
              class="flex items-start gap-3 p-3 rounded-lg"
              :class="checagem.passou ? 'bg-green-50' : 'bg-red-50'"
            >
              <div
                :class="getChecagemClass(checagem.passou)"
              ></div>
              <div class="flex-1">
                <p class="font-medium">{{ checagem.tipo }}</p>
                <p class="text-sm text-gray-600">{{ checagem.descricao }}</p>
                <p class="text-sm font-mono">{{ checagem.valor_calculado }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Novo Arranjo -->
    <div v-if="showForm" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Novo Arranjo</h3>
        <form @submit.prevent="handleCriarArranjo">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
              <input
                v-model="formData.nome"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Módulo</label>
              <select
                v-model="formData.modulo_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Selecione um módulo</option>
                <option v-for="modulo in modulosList" :key="modulo.id" :value="modulo.id">
                  {{ modulo.modelo }} - {{ modulo.potencia }}W
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Inversor</label>
              <select
                v-model="formData.inversor_id"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Selecione um inversor</option>
                <option v-for="inversor in inversoresList" :key="inversor.id" :value="inversor.id">
                  {{ inversor.modelo }} - {{ inversor.potencia_nominal }}W
                </option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Azimute (°)</label>
                <input
                  v-model.number="formData.azimute"
                  type="number"
                  min="0"
                  max="360"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Inclinação (°)</label>
                <input
                  v-model.number="formData.inclinacao"
                  type="number"
                  min="0"
                  max="90"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button
              type="submit"
              :disabled="isLoading"
              class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              Criar Arranjo
            </button>
            <button
              type="button"
              @click="showForm = false"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProjetosStore } from '@/stores/projetos'
import { useCatalogosStore } from '@/stores/catalogos'
import { execucoesService } from '@/services/execucoes'

const route = useRoute()
const projetosStore = useProjetosStore()
const catalogosStore = useCatalogosStore()

// State
const isLoading = ref(false)
const showForm = ref(false)
const projetoData = ref({
  nome: '',
  cliente: '',
  arranjos: []
})
const execucaoData = ref(null)
const checagensList = ref([])
const arranjosList = ref([])
const modulosList = ref([])
const inversoresList = ref([])

const formData = ref({
  nome: '',
  modulo_id: '',
  inversor_id: '',
  azimute: 180,
  inclinacao: 20
})

// Methods
function getModuloNome(arranjo) {
  return arranjo.modulo?.modelo || '-'
}

function getInversorNome(arranjo) {
  return arranjo.inversor?.modelo || '-'
}

function getStringsCount(arranjo) {
  return arranjo.strings?.length || 0
}

function hasStrings(arranjo) {
  return arranjo.strings && arranjo.strings.length > 0
}

function getStatusClass(status) {
  const baseClass = 'w-3 h-3 rounded-full '
  if (status === 'aprovado') return baseClass + 'bg-green-500'
  if (status === 'aprovado_com_avisos') return baseClass + 'bg-yellow-500'
  return baseClass + 'bg-red-500'
}

function formatStatus(status) {
  return status?.replace('_', ' ') || ''
}

function getChecagemClass(passou) {
  const baseClass = 'w-2 h-2 rounded-full mt-2 '
  return baseClass + (passou ? 'bg-green-500' : 'bg-red-500')
}

async function loadData() {
  try {
    await projetosStore.buscarProjeto(route.params.id)
    const projeto = projetosStore.projetoAtual
    if (projeto) {
      projetoData.value = {
        nome: projeto.nome || '',
        cliente: projeto.cliente || '',
        arranjos: projeto.arranjos || []
      }
      arranjosList.value = projeto.arranjos || []
    }
    
    await catalogosStore.carregarTodos()
    modulosList.value = catalogosStore.modulos || []
    inversoresList.value = catalogosStore.inversores || []
  } catch (error) {
    console.error('Erro ao carregar dados:', error)
  }
}

async function handleExecutarAnalise() {
  isLoading.value = true
  try {
    const execucao = await projetosStore.executarAnalise(route.params.id)
    execucaoData.value = execucao.data
    

    const checagensRes = await execucoesService.listarChecagens(execucao.data.id)
    checagensList.value = checagensRes.data || []
  } catch (error) {
    console.error('Erro ao executar análise:', error)
  } finally {
    isLoading.value = false
  }
}

async function handleCriarArranjo() {
  isLoading.value = true
  try {
    await projetosStore.criarArranjo(route.params.id, formData.value)
    await loadData() // Recarregar dados
    showForm.value = false
    formData.value = {
      nome: '',
      modulo_id: '',
      inversor_id: '',
      azimute: 180,
      inclinacao: 20
    }
  } catch (error) {
    console.error('Erro ao criar arranjo:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
