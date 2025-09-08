<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Módulos Fotovoltaicos</h1>
        <p class="text-gray-600">Compare e gerencie módulos solares</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex flex-col lg:flex-row gap-4 mb-4">
          <div class="flex-1">
            <input
              v-model="busca"
              type="text"
              placeholder="Buscar por modelo ou fabricante..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="flex gap-2">
            <select
              v-model="fabricanteSelecionado"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos os fabricantes</option>
              <option v-for="fab in fabricantesDisponiveis" :key="fab.id" :value="fab.id">
                {{ fab.nome }}
              </option>
            </select>
            <select
              v-model="tecnologiaSelecionada"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todas as tecnologias</option>
              <option value="monocristalino">Monocristalino</option>
              <option value="policristalino">Policristalino</option>
              <option value="filme_fino">Filme Fino</option>
            </select>
          </div>
        </div>

        <div v-if="modulosComparacao.length > 0" class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-medium text-gray-900">
              Comparação ({{ modulosComparacao.length }}/3)
            </h3>
            <button
              @click="modulosComparacao = []"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              Limpar comparação
            </button>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
              v-for="modulo in modulosComparacao"
              :key="modulo.id"
              class="bg-blue-50 border border-blue-200 rounded-lg p-4"
            >
              <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-blue-900">{{ modulo.modelo }}</h4>
                <button
                  @click="removerComparacao(modulo.id)"
                  class="text-blue-600 hover:text-blue-800"
                >
                  ×
                </button>
              </div>
              <div class="space-y-1 text-sm text-blue-800">
                <div>Potência: {{ modulo.potencia_nominal }}W</div>
                <div>Voc: {{ modulo.voc }}V</div>
                <div>Isc: {{ modulo.isc }}A</div>
                <div>Eficiência: {{ calcularEficiencia(modulo) }}%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="modulo in modulosFiltrados"
          :key="modulo.id"
          class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
        >
          <div class="p-4">
            <div class="flex justify-between items-start mb-2">
              <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ modulo.modelo }}</h3>
                <p class="text-sm text-gray-600">{{ getNomeFabricante(modulo.fabricante_id) }}</p>
              </div>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :checked="moduloSelecionado(modulo.id)"
                  @change="toggleComparacao(modulo)"
                  :disabled="!moduloSelecionado(modulo.id) && modulosComparacao.length >= 3"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-xs text-gray-500">Comparar</span>
              </div>
            </div>

            <div class="space-y-1 mb-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Potência</span>
                <span class="text-sm font-medium text-gray-900">{{ modulo.potencia_nominal }}W</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Tecnologia</span>
                <span class="text-sm font-medium text-gray-900 capitalize">{{ modulo.tecnologia }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Eficiência</span>
                <span class="text-sm font-medium text-gray-900">{{ calcularEficiencia(modulo) }}%</span>
              </div>
            </div>

            <div class="border-t pt-2">
              <h4 class="text-sm font-medium text-gray-900 mb-2">Especificações Elétricas</h4>
              <div class="grid grid-cols-2 gap-1 text-xs">
                <div>
                  <span class="text-gray-600">Voc:</span>
                  <span class="font-medium ml-1">{{ modulo.voc }}V</span>
                </div>
                <div>
                  <span class="text-gray-600">Vmp:</span>
                  <span class="font-medium ml-1">{{ modulo.vmp }}V</span>
                </div>
                <div>
                  <span class="text-gray-600">Isc:</span>
                  <span class="font-medium ml-1">{{ modulo.isc }}A</span>
                </div>
                <div>
                  <span class="text-gray-600">Imp:</span>
                  <span class="font-medium ml-1">{{ modulo.imp }}A</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCatalogosStore } from '@/stores/catalogos'

const store = useCatalogosStore()
const busca = ref('')
const fabricanteSelecionado = ref('')
const tecnologiaSelecionada = ref('')
const modulosComparacao = ref([])

const fabricantesDisponiveis = computed(() => {
  return store.fabricantes.filter(f => 
    store.modulos.some(m => m.fabricante_id === f.id)
  )
})

const modulosFiltrados = computed(() => {
  let resultado = store.modulos

  if (busca.value) {
    const termo = busca.value.toLowerCase()
    resultado = resultado.filter(m => 
      m.modelo.toLowerCase().includes(termo) || 
      getNomeFabricante(m.fabricante_id).toLowerCase().includes(termo)
    )
  }

  if (fabricanteSelecionado.value) {
    resultado = resultado.filter(m => m.fabricante_id == fabricanteSelecionado.value)
  }

  if (tecnologiaSelecionada.value) {
    resultado = resultado.filter(m => m.tecnologia === tecnologiaSelecionada.value)
  }

  return resultado.sort((a, b) => b.potencia_nominal - a.potencia_nominal)
})

const getNomeFabricante = (fabricanteId) => {
  const fabricante = store.fabricantes.find(f => f.id === fabricanteId)
  return fabricante ? fabricante.nome : 'N/A'
}

const calcularEficiencia = (modulo) => {
  const areaAproximada = 2
  const eficiencia = (modulo.potencia_nominal / (1000 * areaAproximada)) * 100
  return eficiencia.toFixed(1)
}

const moduloSelecionado = (moduloId) => {
  return modulosComparacao.value.some(m => m.id === moduloId)
}

const toggleComparacao = (modulo) => {
  const index = modulosComparacao.value.findIndex(m => m.id === modulo.id)
  if (index >= 0) {
    modulosComparacao.value.splice(index, 1)
  } else if (modulosComparacao.value.length < 3) {
    modulosComparacao.value.push(modulo)
  }
}

const removerComparacao = (moduloId) => {
  const index = modulosComparacao.value.findIndex(m => m.id === moduloId)
  if (index >= 0) {
    modulosComparacao.value.splice(index, 1)
  }
}

onMounted(() => {
  store.carregarTodos()
})
</script>
