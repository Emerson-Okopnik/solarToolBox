<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Fabricantes</h1>
        <p class="text-gray-600">Gerencie os fabricantes de equipamentos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <form @submit.prevent="salvar" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Nome</label>
              <input v-model="form.nome"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">País</label>
              <input v-model="form.pais"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Website</label>
              <input v-model="form.website"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
          </div>
          <label class="inline-flex items-center">
            <input type="checkbox" v-model="form.ativo" class="m-2" />
            <span class="text-sm">Ativo</span>
          </label>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">País</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Website</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produtos</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="fab in store.fabricantes" :key="fab.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ fab.id }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ fab.nome }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ fab.pais }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <a v-if="fab.website" :href="fab.website" target="_blank"
                    class="text-blue-600 hover:text-blue-800 text-sm">
                    {{ fab.website }}
                  </a>
                  <span v-else class="text-gray-400 text-sm">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="fab.ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                    {{ fab.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="flex gap-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs">
                      {{ contarModulos(fab.id) }} módulos
                    </span>
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                      {{ contarInversores(fab.id) }} inversores
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <button @click="editar(fab)" class="btn btn-link btn-sm p-0 me-1" >
                    Editar
                  </button>
                  <button @click="remover(fab.id)" class="btn btn-link btn-sm text-danger p-0">
                    Excluir
                  </button>
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

const form = ref({ nome: '', pais: '', website: '', ativo: true })
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarTodos()
})

function reset() {
  form.value = { nome: '', pais: '', website: '', ativo: true }
  editando.value = false
  editId.value = null
}

async function salvar() {
  if (editando.value) {
    await store.atualizarFabricante(editId.value, form.value)
  } else {
    await store.criarFabricante(form.value)
  }
  reset()
}

function editar(fab) {
  form.value = { nome: fab.nome, pais: fab.pais, website: fab.website, ativo: fab.ativo }
  editId.value = fab.id
  editando.value = true
}

async function remover(id) {
  if (confirm('Deseja excluir este fabricante?')) {
    await store.removerFabricante(id)
  }
}

function cancelar() {
  reset()
}

const contarModulos = (fabricanteId) => {
  return store.modulos.filter(m => m.fabricante_id === fabricanteId).length
}

const contarInversores = (fabricanteId) => {
  return store.inversores.filter(i => i.fabricante_id === fabricanteId).length
}
</script>