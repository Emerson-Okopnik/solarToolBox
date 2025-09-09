<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Fabricantes</h1>
        <p class="text-gray-600">Gerencie os fabricantes de equipamentos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <input
              v-model="busca"
              type="text"
              placeholder="Buscar por nome ou país..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div class="flex gap-2">
            <select
              v-model="paisSelecionado"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Todos os países</option>
              <option v-for="pais in paises" :key="pais" :value="pais">
                {{ pais }}
              </option>
            </select>
            <select
              v-model="ordenacao"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="nome">Nome A-Z</option>
              <option value="nome_desc">Nome Z-A</option>
              <option value="pais">País A-Z</option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full max-w-4xl mx-auto divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">País</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Website</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produtos</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="fabricante in listaFiltrada" :key="fabricante.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ fabricante.id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ fabricante.nome }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ fabricante.pais }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a
                    v-if="fabricante.website"
                    :href="fabricante.website"
                    target="_blank"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                  >
                    {{ fabricante.website }}
                  </a>
                  <span v-else class="text-gray-400 text-sm">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="fabricante.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ fabricante.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="flex gap-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                      {{ contarModulos(fabricante.id) }} módulos
                    </span>
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                      {{ contarInversores(fabricante.id) }} inversores
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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
const paisSelecionado = ref('')
const ordenacao = ref('nome')

const paises = computed(() => {
  const lista = store.fabricantes.map(f => f.pais).filter(Boolean)
  return [...new Set(lista)].sort()
})

const listaFiltrada = computed(() => {
  let resultado = store.fabricantes

  if (busca.value) {
    const termo = busca.value.toLowerCase()
    resultado = resultado.filter(f => 
      f.nome.toLowerCase().includes(termo) || 
      (f.pais && f.pais.toLowerCase().includes(termo))
    )
  }

  if (paisSelecionado.value) {
    resultado = resultado.filter(f => f.pais === paisSelecionado.value)
  }

  return resultado.sort((a, b) => {
    switch (ordenacao.value) {
      case 'nome_desc': return b.nome.localeCompare(a.nome)
      case 'pais': return (a.pais || '').localeCompare(b.pais || '')
      default: return a.nome.localeCompare(b.nome)
    }
  })
})

const contarModulos = (fabricanteId) => {
  return store.modulos.filter(m => m.fabricante_id === fabricanteId).length
}

const contarInversores = (fabricanteId) => {
  return store.inversores.filter(i => i.fabricante_id === fabricanteId).length
}

onMounted(() => {
  store.carregarTodos()
})
</script>
