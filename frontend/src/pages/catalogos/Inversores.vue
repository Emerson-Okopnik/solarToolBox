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
                <div class="text-xs text-gray-500">{{ parseEficiencia(inversor.eficiencia_max).toFixed(2) }}% eficiência</div>
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
                <div class="p-3">
                  <canvas :ref="el => setChartRef(el, inversor.id)"></canvas>
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
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue'
import Chart from 'chart.js/auto'
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
      case 'eficiencia_desc':
        return parseEficiencia(b.eficiencia_max) - parseEficiencia(a.eficiencia_max)
      case 'modelo': return a.modelo.localeCompare(b.modelo)
      default: return b.potencia_ac_nominal - a.potencia_ac_nominal
    }
  })
})

const getNomeFabricante = (fabricanteId) => {
  const fabricante = store.fabricantes.find(f => f.id === fabricanteId)
  return fabricante ? fabricante.nome : 'N/A'
}

const parseEficiencia = (valor) => {
  const numero = parseFloat(String(valor).replace(',', '.'))
  if (Number.isNaN(numero)) return 0
  return numero > 1 ? numero : numero * 100
}

const gerarCurva = (inversor) => {
  const base = parseEficiencia(inversor.eficiencia_max)
  if (!base) return [0, 0, 0, 0, 0]
  const fatores = [0.85, 0.92, 0.98, 0.99, 1]
  return fatores.map(f => {
    const valor = base * f
    return parseFloat(Math.min(100, valor).toFixed(2))
  })
}

onMounted(() => {
  store.carregarTodos()
})

const chartRefs = ref({})
const chartInstances = ref({})

const setChartRef = (el, id) => {
  if (el) chartRefs.value[id] = el
}

const renderCharts = () => {
  nextTick(() => {
    inversoresFiltrados.value.forEach(inv => {
      const ctx = chartRefs.value[inv.id]
      if (!ctx) return
      if (chartInstances.value[inv.id]) {
        chartInstances.value[inv.id].destroy()
      }
      chartInstances.value[inv.id] = new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['10%', '25%', '50%', '75%', '100%'],
          datasets: [{
            data: gerarCurva(inv),
            borderColor: '#16a34a',
            backgroundColor: 'rgba(34,197,94,0.2)',
            tension: 0.4,
            fill: true,
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: {
            x: { grid: { display: false } },
            y: {
              beginAtZero: true,
              max: 100,
              grid: { display: false },
              ticks: { display: false }
            }
          }
        }
      })
    })
  })
}

watch(inversoresFiltrados, () => {
  renderCharts()
}, { deep: true })

onUnmounted(() => {
  Object.values(chartInstances.value).forEach(ch => ch.destroy())
})
</script>
