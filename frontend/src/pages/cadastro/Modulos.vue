<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold mb-4">Cadastro de Módulos</h1>
      <form @submit.prevent="salvar" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Modelo</label>
          <input v-model="form.modelo" class="w-full border rounded px-3 py-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Potência Nominal (W)</label>
          <input v-model.number="form.potencia_nominal" type="number" class="w-full border rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Fabricante</label>
          <select v-model="form.fabricante_id" class="w-full border rounded px-3 py-2">
            <option v-for="fab in store.fabricantes" :key="fab.id" :value="fab.id">{{ fab.nome }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Voc (V)</label>
            <input v-model.number="form.voc" type="number" step="0.01" class="w-full border rounded px-3 py-2"/>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Vmp (V)</label>
            <input v-model.number="form.vmp" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Isc (A)</label>
            <input v-model.number="form.isc" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Imp (A)</label>
            <input v-model.number="form.imp" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
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
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Potência</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="mod in store.modulos" :key="mod.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">{{ mod.modelo }}</td>
              <td class="px-6 py-4">{{ mod.potencia_nominal }}W</td>
              <td class="px-6 py-4">
                <button @click="editar(mod)" class="text-blue-600 hover:underline mr-3">Editar</button>
                <button @click="remover(mod.id)" class="text-red-600 hover:underline">Excluir</button>
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

const form = ref({ modelo: '', potencia_nominal: '', fabricante_id: '', voc: '', vmp: '', isc: '', imp: '' })
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarModulos()
  store.carregarFabricantes()
})

function reset() {
  form.value = { modelo: '', potencia_nominal: '', fabricante_id: '', voc: '', vmp: '', isc: '', imp: '' }
  editando.value = false
  editId.value = null
}

async function salvar() {
  if (editando.value) {
    await store.atualizarModulo(editId.value, form.value)
  } else {
    await store.criarModulo(form.value)
  }
  reset()
}

function editar(modulo) {
  form.value = {
    modelo: modulo.modelo,
    potencia_nominal: modulo.potencia_nominal,
    fabricante_id: modulo.fabricante_id,
    voc: modulo.voc,
    vmp: modulo.vmp,
    isc: modulo.isc,
    imp: modulo.imp,
  }
  editId.value = modulo.id
  editando.value = true
}

async function remover(id) {
  if (confirm('Deseja excluir este módulo?')) {
    await store.removerModulo(id)
  }
}

function cancelar() {
  reset()
}
</script>