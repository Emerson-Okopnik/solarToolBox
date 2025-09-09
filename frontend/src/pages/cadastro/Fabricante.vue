<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold mb-4">Cadastro de Fabricantes</h1>
      <form @submit.prevent="salvar" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Nome</label>
          <input v-model="form.nome" class="w-full border rounded px-3 py-2" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">País</label>
            <input v-model="form.pais" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Website</label>
            <input v-model="form.website" class="w-full border rounded px-3 py-2" />
          </div>
        </div>
        <label class="inline-flex items-center">
          <input type="checkbox" v-model="form.ativo" class="mr-2" />
          <span class="text-sm">Ativo</span>
        </label>
        <div class="flex gap-2">
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
            {{ editando ? 'Atualizar' : 'Adicionar' }}
          </button>
          <button v-if="editando" type="button" @click="cancelar" class="px-4 py-2 bg-gray-300 rounded">
            Cancelar
          </button>
        </div>
      </form>

      <div class="mt-8">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">País</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="fab in store.fabricantes" :key="fab.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">{{ fab.nome }}</td>
              <td class="px-6 py-4">{{ fab.pais || '-' }}</td>
              <td class="px-6 py-4">
                <button @click="editar(fab)" class="text-blue-600 hover:underline mr-3">Editar</button>
                <button @click="remover(fab.id)" class="text-red-600 hover:underline">Excluir</button>
              </td>
            </tr>
          </tbody>
        </table>
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
  store.carregarFabricantes()
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
</script>