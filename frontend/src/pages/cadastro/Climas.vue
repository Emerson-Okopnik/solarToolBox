<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Climas</h1>
        <p class="text-gray-600">Gerencie os climas para cálculos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <form @submit.prevent="salvar" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1">Nome Clima</label>
              <input v-model="form.nome" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Cidade</label>
              <input v-model="form.cidade" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Estado</label>
              <input v-model="form.estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">País</label>
              <input v-model="form.pais" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Latitude</label>
              <input v-model.number="form.latitude" type="number" step="0.000001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Longitude</label>
              <input v-model.number="form.longitude" type="number" step="0.000001" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Altitude (m)</label>
              <input v-model.number="form.altitude" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Média Anual (°C)</label>
              <input v-model.number="form.temp_media_anual" type="number" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Mín. Histórica (°C)</label>
              <input v-model.number="form.temp_min_historica" type="number" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Máx. Histórica (°C)</label>
              <input v-model.number="form.temp_max_historica" type="number" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Irradiação Global Horizontal (kWh/m²/dia)</label>
              <input v-model.number="form.irradiacao_global_horizontal" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Irradiação Direta Normal (kWh/m²/dia)</label>
              <input v-model.number="form.irradiacao_direta_normal" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
          </div>

          <div class="flex items-center gap-1 mt-3">
            <input id="ativo" v-model="form.ativo" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <label for="ativo" class="ml-2 block text-sm font-medium"> Ativo</label>
          </div>

          <div class="flex gap-2 mt-2">
            <button type="submit" class="btn btn-primary d-flex align-items-center">
              {{ editando ? 'Atualizar' : 'Adicionar' }}
            </button>
            <button v-if="editando" type="button" @click="cancelar" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400" >
              Cancelar
            </button>
          </div>
        </form>
      </div>

      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full max-w-4xl mx-auto divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Temp. Média</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Irradiação</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="clima in store.climas" :key="clima.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ clima.nome }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ clima.cidade }} - {{ clima.estado }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ clima.temp_media_anual }}°C
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ clima.irradiacao_global_horizontal }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="clima.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ clima.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <button @click="editar(clima)" class="btn btn-link btn-sm p-0 me-1">Editar</button>
                  <button @click="remover(clima.id)" class="btn btn-link btn-sm text-danger p-0">Excluir</button>
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
import { ref, onMounted } from 'vue'
import { useCatalogosStore } from '@/stores/catalogos'

const store = useCatalogosStore()

const form = ref({
  nome: '',
  cidade: '',
  estado: '',
  pais: '',
  latitude: '',
  longitude: '',
  altitude: '',
  temp_min_historica: '',
  temp_max_historica: '',
  temp_media_anual: '',
  irradiacao_global_horizontal: '',
  irradiacao_direta_normal: '',
  ativo: true,
})
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarClimas()
})

function reset() {
  form.value = {
    nome: '',
    cidade: '',
    estado: '',
    pais: '',
    latitude: '',
    longitude: '',
    altitude: '',
    temp_min_historica: '',
    temp_max_historica: '',
    temp_media_anual: '',
    irradiacao_global_horizontal: '',
    irradiacao_direta_normal: '',
    ativo: true,
  }
  editando.value = false
  editId.value = null
}

async function salvar() {
  if (editando.value) {
    await store.atualizarClima(editId.value, form.value)
  } else {
    await store.criarClima(form.value)
  }
  reset()
}

function editar(clima) {
  form.value = {
    nome: clima.nome,
    cidade: clima.cidade,
    estado: clima.estado,
    pais: clima.pais,
    latitude: clima.latitude,
    longitude: clima.longitude,
    altitude: clima.altitude,
    temp_min_historica: clima.temp_min_historica,
    temp_max_historica: clima.temp_max_historica,
    temp_media_anual: clima.temp_media_anual,
    irradiacao_global_horizontal: clima.irradiacao_global_horizontal,
    irradiacao_direta_normal: clima.irradiacao_direta_normal,
    ativo: clima.ativo,
  }
  editId.value = clima.id
  editando.value = true
}

async function remover(id) {
  if (confirm('Deseja excluir este clima?')) {
    await store.removerClima(id)
  }
}

function cancelar() {
  reset()
}
</script>