<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Cadastro de Inversores</h1>
        <p class="text-gray-600">Gerencie e cadastre Inversores fotovoltaicos</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
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
            <label class="block text-sm font-medium mb-1">Potência DC Máx (W)</label>
            <input
              v-model.number="form.potencia_dc_max"
              type="number"
              class="w-full border rounded px-3 py-2"
            />
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
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Tensão AC Nominal (V)</label>
              <input
                v-model.number="form.tensao_ac_nominal"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Corrente AC Máx (A)</label>
              <input
                v-model.number="form.corrente_ac_max"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Frequência Nominal (Hz)</label>
              <input
                v-model.number="form.frequencia_nominal"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Operação Min (°C)</label>
              <input
                v-model.number="form.temp_operacao_min"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Temp. Operação Máx (°C)</label>
              <input
                v-model.number="form.temp_operacao_max"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Altitude Máx (m)</label>
              <input
                v-model.number="form.altitude_max"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Umidade Máx (%)</label>
              <input
                v-model.number="form.umidade_max"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Número de MPPTs</label>
              <input
                v-model.number="form.num_mppts"
                type="number"
                class="w-full border rounded px-3 py-2"
              />
            </div>
            <div class="flex items-center mt-6">
              <input v-model="form.ativo" type="checkbox" class="mr-2" />
              <label class="text-sm font-medium">Ativo</label>
            </div>
          </div>
          <div v-for="(mppt, index) in form.mppts" :key="index" class="border p-4 rounded">
            <h3 class="font-medium mb-2">MPPT {{ index + 1 }}</h3>
            <div class="grid grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium mb-1">Tensão MPPT Min (V)</label>
                <input
                  v-model.number="mppt.tensao_mppt_min"
                  type="number"
                  class="w-full border rounded px-3 py-2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Tensão MPPT Máx (V)</label>
                <input
                  v-model.number="mppt.tensao_mppt_max"
                  type="number"
                  class="w-full border rounded px-3 py-2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Corrente Entrada Máx (A)</label>
                <input
                  v-model.number="mppt.corrente_entrada_max"
                  type="number"
                  class="w-full border rounded px-3 py-2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Strings Máx</label>
                <input
                  v-model.number="mppt.strings_max"
                  type="number"
                  class="w-full border rounded px-3 py-2"
                />
              </div>
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
      </div> 

      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full max-w-4xl mx-auto divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modelo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Potência</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="inv in store.inversores" :key="inv.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ inv.modelo }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ inv.potencia_ac_nominal }}W</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  <button @click="editar(inv)" class="btn btn-link btn-sm p-0 me-1">Editar</button>
                  <button @click="remover(inv.id)" class="btn btn-link btn-sm text-danger p-0">Excluir</button>
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
import { ref, onMounted, watch} from 'vue'
import { useCatalogosStore } from '@/stores/catalogos'

const store = useCatalogosStore()

const form = ref({
  modelo: '',
  potencia_ac_nominal: '',
   potencia_dc_max: '',
  fabricante_id: '',
  eficiencia_max: '',
  tipo: 'string',
  tensao_dc_min: '',
  tensao_dc_max: '',
  corrente_dc_max: '',
  tensao_ac_nominal: '',
  corrente_ac_max: '',
  frequencia_nominal: '',
  temp_operacao_min: '',
  temp_operacao_max: '',
  altitude_max: '',
  umidade_max: '',
  num_mppts: 0,
  ativo: true,
  mppts: [],
})
const editando = ref(false)
const editId = ref(null)

onMounted(() => {
  store.carregarInversores()
  store.carregarFabricantes()
})

watch(
  () => form.value.num_mppts,
  novo => {
    const n = Number(novo) || 0
    if (form.value.mppts.length < n) {
      for (let i = form.value.mppts.length; i < n; i++) {
        form.value.mppts.push({
          numero: i + 1,
          tensao_mppt_min: '',
          tensao_mppt_max: '',
          corrente_entrada_max: '',
          strings_max: '',
        })
      }
    } else if (form.value.mppts.length > n) {
      form.value.mppts.splice(n)
    }
  }
)

function reset() {
  form.value = {
    modelo: '',
    potencia_ac_nominal: '',
    potencia_dc_max: '',
    fabricante_id: '',
    eficiencia_max: '',
    tipo: 'string',
    tensao_dc_min: '',
    tensao_dc_max: '',
    corrente_dc_max: '',
    tensao_ac_nominal: '',
    corrente_ac_max: '',
    frequencia_nominal: '',
    temp_operacao_min: '',
    temp_operacao_max: '',
    altitude_max: '',
    umidade_max: '',
    num_mppts: 0,
    ativo: true,
    mppts: [],
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
    potencia_dc_max: inv.potencia_dc_max,
    fabricante_id: inv.fabricante_id,
    eficiencia_max: inv.eficiencia_max,
    tipo: inv.tipo,
    tensao_dc_min: inv.tensao_dc_min,
    tensao_dc_max: inv.tensao_dc_max,
    corrente_dc_max: inv.corrente_dc_max,
    tensao_ac_nominal: inv.tensao_ac_nominal,
    corrente_ac_max: inv.corrente_ac_max,
    frequencia_nominal: inv.frequencia_nominal,
    temp_operacao_min: inv.temp_operacao_min,
    temp_operacao_max: inv.temp_operacao_max,
    altitude_max: inv.altitude_max,
    umidade_max: inv.umidade_max,
    num_mppts: inv.num_mppts,
    ativo: inv.ativo,
    mppts: inv.mppts ? inv.mppts.map(m => ({
      numero: m.numero,
      tensao_mppt_min: m.tensao_mppt_min,
      tensao_mppt_max: m.tensao_mppt_max,
      corrente_entrada_max: m.corrente_entrada_max,
      strings_max: m.strings_max,
    })) : [],
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