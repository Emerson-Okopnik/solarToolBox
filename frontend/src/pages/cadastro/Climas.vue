<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold mb-4">Cadastro de Climas</h1>
      <form @submit.prevent="salvar" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Nome</label>
          <input v-model="form.nome" class="w-full border rounded px-3 py-2" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Cidade</label>
            <input v-model="form.cidade" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Estado</label>
            <input v-model="form.estado" class="w-full border rounded px-3 py-2" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Temp. Média Anual (°C)</label>
            <input v-model="form.temp_media_anual" type="number" step="0.1" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Irradiação Global Horizontal (kWh/m²/dia)</label>
            <input v-model="form.irradiacao_global_horizontal" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
          </div>
        </div>
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="clima in store.climas" :key="clima.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">{{ clima.nome }}</td>
              <td class="px-6 py-4">{{ clima.cidade }} - {{ clima.estado }}</td>
              <td class="px-6 py-4">
                <button @click="editar(clima)" class="text-blue-600 hover:underline mr-3">Editar</button>
                <button @click="remover(clima.id)" class="text-red-600 hover:underline">Excluir</button>
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

const form = ref({ nome: '', cidade: '', estado: '', temp_media_anual: '', irradiacao_global_horizontal: '' })
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarClimas()
})

function reset() {
  form.value = { nome: '', cidade: '', estado: '', temp_media_anual: '', irradiacao_global_horizontal: '' }
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
    temp_media_anual: clima.temp_media_anual,
    irradiacao_global_horizontal: clima.irradiacao_global_horizontal,
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