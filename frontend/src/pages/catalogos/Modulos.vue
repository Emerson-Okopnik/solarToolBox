<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Módulos Fotovoltaicos</h1>
        <p class="text-gray-600">Gerencie módulos solares</p>
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
            <div class="mb-2">
              <h3 class="text-sm font-semibold text-gray-900">{{ modulo.modelo }}</h3>
              <p class="text-sm text-gray-600">{{ getNomeFabricante(modulo.fabricante_id) }}</p>
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

onMounted(() => {
  store.carregarTodos()
})
</script>
