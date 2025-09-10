<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Módulo</h1>
        <p class="text-gray-600">Gerencie e cadastre os módulos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <form @submit.prevent="salvar" class="space-y-4">
          <div  class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Modelo</label>
              <input v-model="form.modelo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Potência Nominal (W)</label>
              <input v-model.number="form.potencia_nominal" type="number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Fabricante</label>
              <select v-model="form.fabricante_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option v-for="fab in store.fabricantes" :key="fab.id" :value="fab.id">{{ fab.nome }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Voc (V)</label>
                <input v-model.number="form.voc" type="number" step="0.1" class="w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Vmp (V)</label>
                <input v-model.number="form.vmp" type="number" step="0.1" class="w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Isc (A)</label>
                <input v-model.number="form.isc" type="number" step="0.1" class="w-full border rounded px-3 py-2" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Imp (A)</label>
                <input v-model.number="form.imp" type="number" step="0.1" class="w-full border rounded px-3 py-2" />
              </div>
            </div>
          </div>
          <div class="flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary d-flex align-items-center">
              {{ editando ? 'Atualizar' : 'Adicionar' }}
            </button>
            <button v-if="editando" type="button" @click="cancelar" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-400">
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tecnologia</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Potência</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="mod in store.modulos" :key="mod.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ mod.modelo }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ mod.tecnologia }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ mod.potencia_nominal }}W</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ mod.ativo }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"> 
                  <button @click="editar(mod)" class="btn btn-link btn-sm p-0 me-1">Editar</button> <button @click="remover(mod.id)" class="btn btn-link btn-sm text-danger p-0">Excluir</button> 
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