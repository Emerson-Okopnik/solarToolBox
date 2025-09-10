<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Módulo</h1>
        <p class="text-gray-600">Gerencie e cadastre os módulos fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
        <form @submit.prevent="salvar" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Modelo</label>
              <input v-model="form.modelo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Tecnologia</label>
              <input v-model="form.tecnologia" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
            <div class="grid grid-cols-2 gap-">
              <div>
                <label class="block text-sm font-medium mb-1">Potência Nominal (W)</label>
                <input v-model.number="form.potencia_nominal" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Coef. Temp Potência (%/°C)</label>
                <input v-model.number="form.coef_temp_potencia" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div> 
            <div>
              <label class="block text-sm font-medium mb-1">Fabricante</label>
              <select v-model="form.fabricante_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option v-for="fab in store.fabricantes" :key="fab.id" :value="fab.id">{{ fab.nome }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-4 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Voc (V)</label>
              <input v-model.number="form.voc" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
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

          <div class="grid grid-cols-4 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Coef. Temp Voc (%/°C)</label>
              <input v-model.number="form.coef_temp_voc" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Coef. Temp Vmp (%/°C)</label>
              <input v-model.number="form.coef_temp_vmp" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Coef. Temp Isc (%/°C)</label>
              <input v-model.number="form.coef_temp_isc" type="number" step="0.001" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Coef. Temp Imp (%/°C)</label>
              <input v-model.number="form.coef_temp_imp" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
          </div>

          <div class="grid grid-cols-4 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Comprimento (mm)</label>
              <input v-model.number="form.comprimento" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Largura (mm)</label>
              <input v-model.number="form.largura" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Espessura (mm)</label>
              <input v-model.number="form.espessura" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Peso (kg)</label>
              <input v-model.number="form.peso" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Operação Mínima (°C)</label>
              <input v-model.number="form.temp_operacao_min" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Operação Máxima (°C)</label>
              <input v-model.number="form.temp_operacao_max" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Tensão Máxima do Sistema (V)</label>
              <input v-model.number="form.tensao_maxima_sistema" type="number" step="0.01" class="w-full border rounded px-3 py-2" />
            </div>
          </div>
          <div class="flex items-center gap-1 mt-3">
            <input id="ativo" v-model="form.ativo" type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
            <label for="ativo" class="ml-2 block text-sm font-medium"> Ativo</label>
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

const form = ref({
  fabricante_id: '',
  modelo: '',
  tecnologia: '',
  potencia_nominal: '',
  voc: '',
  vmp: '',
  isc: '',
  imp: '',
  coef_temp_voc: '',
  coef_temp_vmp: '',
  coef_temp_isc: '',
  coef_temp_imp: '',
  coef_temp_potencia: '',
  comprimento: '',
  largura: '',
  espessura: '',
  peso: '',
  temp_operacao_min: '',
  temp_operacao_max: '',
  tensao_maxima_sistema: '',
  ativo: true,
})
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarModulos()
  store.carregarFabricantes()
})

function reset() {
  form.value = {
    fabricante_id: '',
    modelo: '',
    tecnologia: '',
    potencia_nominal: '',
    voc: '',
    vmp: '',
    isc: '',
    imp: '',
    coef_temp_voc: '',
    coef_temp_vmp: '',
    coef_temp_isc: '',
    coef_temp_imp: '',
    coef_temp_potencia: '',
    comprimento: '',
    largura: '',
    espessura: '',
    peso: '',
    temp_operacao_min: '',
    temp_operacao_max: '',
    tensao_maxima_sistema: '',
    ativo: true,
  }
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
    fabricante_id: modulo.fabricante_id,
    modelo: modulo.modelo,
    tecnologia: modulo.tecnologia,
    potencia_nominal: modulo.potencia_nominal,
    voc: modulo.voc,
    vmp: modulo.vmp,
    isc: modulo.isc,
    imp: modulo.imp,
    coef_temp_voc: modulo.coef_temp_voc,
    coef_temp_vmp: modulo.coef_temp_vmp,
    coef_temp_isc: modulo.coef_temp_isc,
    coef_temp_imp: modulo.coef_temp_imp,
    coef_temp_potencia: modulo.coef_temp_potencia,
    comprimento: modulo.comprimento,
    largura: modulo.largura,
    espessura: modulo.espessura,
    peso: modulo.peso,
    temp_operacao_min: modulo.temp_operacao_min,
    temp_operacao_max: modulo.temp_operacao_max,
    tensao_maxima_sistema: modulo.tensao_maxima_sistema,
    ativo: modulo.ativo,
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