<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold mb-4">Cadastro de Inversores</h1>
      <form @submit.prevent="salvar" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Modelo</label>
          <input v-model="form.modelo" class="w-full border rounded px-3 py-2" required />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Potência AC Nominal (W)</label>
          <input v-model.number="form.potencia_ac_nominal" type="number" class="w-full border rounded px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Fabricante</label>
          <select v-model="form.fabricante_id" class="w-full border rounded px-3 py-2">
            <option v-for="fab in store.fabricantes" :key="fab.id" :value="fab.id">{{ fab.nome }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Eficiência Máxima (%)</label>
            <input v-model.number="form.eficiencia_max" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tipo</label>
            <select v-model="form.tipo" class="w-full border rounded px-3 py-2">
              <option value="string">String</option>
              <option value="microinversor">Microinversor</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Tensão DC Min (V)</label>
            <input v-model.number="form.tensao_dc_min" type="number" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tensão DC Máx (V)</label>
            <input v-model.number="form.tensao_dc_max" type="number" class="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Corrente DC Máx (A)</label>
            <input v-model.number="form.corrente_dc_max" type="number" class="w-full border rounded px-3 py-2" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Número de MPPTs</label>
          <input v-model.number="form.num_mppts" type="number" class="w-full border rounded px-3 py-2" />
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
            <tr v-for="inv in store.inversores" :key="inv.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">{{ inv.modelo }}</td>
              <td class="px-6 py-4">{{ inv.potencia_ac_nominal }}W</td>
              <td class="px-6 py-4">
                <button @click="editar(inv)" class="text-blue-600 hover:underline mr-3">Editar</button>
                <button @click="remover(inv.id)" class="text-red-600 hover:underline">Excluir</button>
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

const form = ref({
  modelo: '',
  potencia_ac_nominal: '',
  fabricante_id: '',
  eficiencia_max: '',
  tipo: 'string',
  tensao_dc_min: '',
  tensao_dc_max: '',
  corrente_dc_max: '',
  num_mppts: '',
})
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarInversores()
  store.carregarFabricantes()
})

function reset() {
  form.value = {
    modelo: '',
    potencia_ac_nominal: '',
    fabricante_id: '',
    eficiencia_max: '',
    tipo: 'string',
    tensao_dc_min: '',
    tensao_dc_max: '',
    corrente_dc_max: '',
    num_mppts: '',
  }
  editando.value = false
  editId.value = null
}

async function salvar() {
  if (editando.value) {
    await store.atualizarInversor(editId.value, form.value)
  } else {
    await store.criarInversor(form.value)
  }
  reset()
}

function editar(inv) {
  form.value = {
    modelo: inv.modelo,
    potencia_ac_nominal: inv.potencia_ac_nominal,
    fabricante_id: inv.fabricante_id,
    eficiencia_max: inv.eficiencia_max,
    tipo: inv.tipo,
    tensao_dc_min: inv.tensao_dc_min,
    tensao_dc_max: inv.tensao_dc_max,
    corrente_dc_max: inv.corrente_dc_max,
    num_mppts: inv.num_mppts,
  }
  editId.value = inv.id
  editando.value = true
}

async function remover(id) {
  if (confirm('Deseja excluir este inversor?')) {
    await store.removerInversor(id)
  }
}

function cancelar() {
  reset()
}
</script>