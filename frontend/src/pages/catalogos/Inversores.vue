<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Inversores</h1>
        <p class="text-gray-600">Analise o desempenho e compatibilidade dos inversores</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
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
              v-model="tipoSelecionado"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos os tipos</option>
              <option value="string">String</option>
              <option value="central">Central</option>
              <option value="microinversor">Microinversor</option>
            </select>
            <select
              v-model="ordenacao"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="potencia_desc">Potência (maior)</option>
              <option value="potencia_asc">Potência (menor)</option>
              <option value="eficiencia_desc">Eficiência (maior)</option>
              <option value="modelo">Modelo A-Z</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div
          v-for="inversor in inversoresFiltrados"
          :key="inversor.id"
          class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ inversor.modelo }}</h3>
                <p class="text-sm text-gray-600">{{ getNomeFabricante(inversor.fabricante_id) }}</p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-blue-600">{{ (inversor.potencia_ac_nominal / 1000).toFixed(1) }}kW</div>
                <div class="text-xs text-gray-500">{{ inversor.eficiencia_max }}% eficiência</div>
              </div>
            </div>

            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Especificações DC</h4>
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-xs text-gray-600 mb-1">Tensão Máxima</div>
                  <div class="text-lg font-semibold text-gray-900">{{ inversor.tensao_dc_max }}V</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                  <div class="text-xs text-gray-600 mb-1">Corrente Máxima</div>
                  <div class="text-lg font-semibold text-gray-900">{{ inversor.corrente_dc_max }}A</div>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Janela MPPT</h4>
              <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm text-blue-800">{{ inversor.tensao_dc_min }}V</span>
                  <span class="text-sm text-blue-800">{{ inversor.tensao_dc_max }}V</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2">
                  <div class="bg-blue-600 h-2 rounded-full w-full"></div>
                </div>
                <div class="text-center mt-2">
                  <span class="text-xs text-blue-700">
                    Faixa: {{ inversor.tensao_dc_max - inversor.tensao_dc_min }}V
                  </span>
                </div>
              </div>
            </div>

            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-900 mb-3">
                MPPTs ({{ inversor.num_mppts }})
              </h4>
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="n in inversor.num_mppts"
                  :key="n"
                  class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center"
                >
                  <div class="text-xs text-yellow-700 mb-1">MPPT {{ n }}</div>
                  <div class="text-sm font-medium text-yellow-900">
                    {{ (inversor.corrente_dc_max / inversor.num_mppts).toFixed(1) }}A
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h4 class="text-sm font-medium text-gray-900 mb-3">Curva de Eficiência</h4>
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-end justify-between h-20">
                  <div
                    v-for="(ponto, index) in gerarCurva(inversor)"
                    :key="index"
                    class="bg-green-500 rounded-t"
                    :style="{ 
                      width: '12%', 
                      height: `${ponto}%`,
                      marginRight: '2px'
                    }"
                  ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-600 mt-2">
                  <span>10%</span>
                  <span>25%</span>
                  <span>50%</span>
                  <span>75%</span>
                  <span>100%</span>
                </div>
                <div class="text-center text-xs text-gray-500 mt-1">
                  % da Potência Nominal
                </div>
              </div>
            </div>

            <div class="border-t pt-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Eficiência</span>
                <span
                  :class="getClasseEficiencia(inversor.eficiencia_max)"
                  class="px-2 py-1 rounded text-xs"
                >
                  {{ getTextoEficiencia(inversor.eficiencia_max) }}
                </span>
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
const tipoSelecionado = ref('')
const ordenacao = ref('potencia_desc')

const fabricantesDisponiveis = computed(() => {
  return store.fabricantes.filter(f => 
    store.inversores.some(i => i.fabricante_id === f.id)
  )
})

const inversoresFiltrados = computed(() => {
  let resultado = store.inversores

  if (busca.value) {
    const termo = busca.value.toLowerCase()
    resultado = resultado.filter(i => 
      i.modelo.toLowerCase().includes(termo) || 
      getNomeFabricante(i.fabricante_id).toLowerCase().includes(termo)
    )
  }

  if (fabricanteSelecionado.value) {
    resultado = resultado.filter(i => i.fabricante_id == fabricanteSelecionado.value)
  }

  if (tipoSelecionado.value) {
    resultado = resultado.filter(i => i.tipo === tipoSelecionado.value)
  }

  return resultado.sort((a, b) => {
    switch (ordenacao.value) {
      case 'potencia_asc': return a.potencia_ac_nominal - b.potencia_ac_nominal
      case 'eficiencia_desc': return b.eficiencia_max - a.eficiencia_max
      case 'modelo': return a.modelo.localeCompare(b.modelo)
      default: return b.potencia_ac_nominal - a.potencia_ac_nominal
    }
  })
})

const getNomeFabricante = (fabricanteId) => {
  const fabricante = store.fabricantes.find(f => f.id === fabricanteId)
  return fabricante ? fabricante.nome : 'N/A'
}

const gerarCurva = (inversor) => {
  const base = inversor.eficiencia_max
  return [base * 0.85, base * 0.92, base * 0.98, base * 0.99, base * 1.0]
}

const getClasseEficiencia = (eficiencia) => {
  if (eficiencia >= 97) return 'bg-green-100 text-green-800'
  if (eficiencia >= 95) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getTextoEficiencia = (eficiencia) => {
  if (eficiencia >= 97) return 'Excelente'
  if (eficiencia >= 95) return 'Boa'
  return 'Regular'
}

onMounted(() => {
  store.carregarTodos()
})
</script>
