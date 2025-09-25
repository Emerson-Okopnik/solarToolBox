<template>
  <div v-if="loading" class="d-flex justify-content-center py-5">
    <LoadingSpinner />
  </div>

  <div v-else-if="projeto" class="d-flex flex-column gap-4">
    <!-- Header -->
    <div class="d-flex align-items-start justify-content-between">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-2">
            <li class="breadcrumb-item">
              <router-link to="/projetos">Projetos</router-link>
            </li>
            <li class="breadcrumb-item active" aria-current="page">{{ projeto.nome }}</li>
          </ol>
        </nav>
        <h1 class="h3 fw-bold mb-1">{{ projeto.nome }}</h1>
        <p class="text-muted mb-0">{{ projeto.cliente }}</p>
      </div>

      <div class="page-actions">
        <button
          @click="executarAnalise"
          :disabled="executando"
          class="btn btn-primary d-inline-flex align-items-center"
        >
          <span
            v-if="executando"
            class="spinner-border spinner-border-sm me-2"
            role="status" aria-hidden="true"
          ></span>
          {{ executando ? 'Executando...' : 'Executar Análise' }}
        </button>
        <router-link :to="`/projetos/${projeto.id}/editar`" class="btn btn-outline-secondary">Editar</router-link>
      </div>
    </div>

    <!-- Conteúdo -->
    <div class="row g-4">
      <div class="col-lg-8 col-xl-9 d-flex flex-column gap-4">
        <!-- Arranjos -->
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between section-header">
            <h3 class="h6 mb-0">Arranjos Fotovoltaicos</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center" @click="openArranjoModal()">
              <PlusIcon style="width:1rem;height:1rem" class="me-1" />
              Adicionar Arranjo
            </button>
          </div>

          <div class="card-body">
            <div v-if="!projeto.arranjos || projeto.arranjos.length === 0" class="empty-state">
              <h6 class="mb-1">Nenhum arranjo</h6>
              <p class="text-muted mb-3">Adicione arranjos para começar a análise</p>
              <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center" @click="openArranjoModal()">
                <PlusIcon style="width:1rem;height:1rem" class="me-1" /> Adicionar Arranjo
              </button>
            </div>

            <div v-else class="d-flex flex-column gap-3">
              <div
                v-for="arranjo in projeto.arranjos"
                :key="arranjo.id"
                class="arranjo-item"
              >
                <div class="d-flex align-items-start justify-content-between">
                  <div class="me-3">
                    <div class="fw-semibold text-dark">{{ arranjo.nome }}</div>
                    <div class="text-muted small d-flex flex-wrap gap-1">
                      <span>{{ getArranjoModuloLabel(arranjo) }}</span>
                      <span v-if="arranjo.inversor">• {{ arranjo.inversor.modelo }}</span>
                    </div>  
                    <div class="text-muted small">
                      Azimute: {{ arranjo.azimute }}° | Inclinação: {{ arranjo.inclinacao }}°
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="small fw-semibold text-dark">
                      {{ arranjo.strings?.length || 0 }} strings
                    </div>
                    <span class="badge" :class="getStatusBadgeClass(arranjo.status)">{{ getStatusLabel(arranjo.status) }}</span>
                    <div class="mt-1">
                      <button type="button" class="btn btn-link btn-sm p-0 me-1" @click="openArranjoModal(arranjo)">
                        <PencilSquareIcon style="width:1rem;height:1rem" />
                      </button>
                      <button type="button" class="btn btn-link btn-sm text-danger p-0" @click="removerArranjo(arranjo)">
                        <TrashIcon style="width:1rem;height:1rem" />
                      </button>
                    </div>
                  </div>
                </div>
                <div v-if="arranjo.strings && arranjo.strings.length" class="bg-gray-50 mt-2">
                  <div
                    v-for="string in arranjo.strings"
                    :key="string.id"
                    class="d-flex justify-content-between align-items-center small"
                  >
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ getStringModuloLabel(string) }}</span>
                      <span class="text-muted">{{ getStringMpptLabel(string) }} - Série</span>
                      <span class="text-muted">
                        Azimute: {{ formatGraus(string.azimute) }}° | Inclinação: {{ formatGraus(string.inclinacao) }}°
                      </span>
                    </div>
                    <div class="d-flex align-items-center">
                      <span class="me-2">{{ string.total_modulos }} módulos ({{ string.num_modulos_serie }}s × {{ string.num_strings_paralelo }}p)</span>
                      <button type="button" class="btn btn-link btn-sm p-0 me-1" @click="openStringModal(arranjo, string)">
                        <PencilSquareIcon style="width:1rem;height:1rem" />
                      </button>
                      <button type="button" class="btn btn-link btn-sm text-danger p-0" @click="removerString(arranjo, string)">
                        <TrashIcon style="width:1rem;height:1rem" />
                      </button>
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-primary mt-2 d-inline-flex align-items-center"
                  @click="openStringModal(arranjo)"
                >
                  <PlusIcon style="width:1rem;height:1rem" class="me-1" />
                  Nova String
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Resultados da Análise -->
        <div v-if="ultimaExecucao" class="card">
          <div class="card-header section-header">
            <h3 class="h6 mb-0">Resultados da Análise</h3>
          </div>
          <div class="card-body">
            <div class="row text-center g-3 mb-4">
              <div class="col-4">
                <div class="kpi">
                  <div class="value">{{ ultimaExecucao.total_checagens || 0 }}</div>
                  <div class="label">Total</div>
                </div>
              </div>
              <div class="col-4">
                <div class="kpi">
                  <div class="value text-success">{{ ultimaExecucao.checagens_aprovadas || 0 }}</div>
                  <div class="label">Aprovadas</div>
                </div>
              </div>
              <div class="col-4">
                <div class="kpi">
                  <div class="value text-danger">{{ ultimaExecucao.checagens_reprovadas || 0 }}</div>
                  <div class="label">Reprovadas</div>
                </div>
              </div>
            </div>
            <div v-if="ultimaExecucao.checagens && ultimaExecucao.checagens.length">
              <ChecagensList :checagens="ultimaExecucao.checagens" />
            </div>
            <div v-else class="text-center text-muted small">
              Nenhuma checagem disponível
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="col-lg-4 col-xl-3 d-flex flex-column gap-4">
        <div class="card">
          <div class="card-header section-header">
            <h3 class="h6 mb-0">Detalhes do Projeto</h3>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5 text-muted small">Status</dt>
              <dd class="col-7">
                <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                  {{ getStatusLabel(projeto.status) }}
                </span>
              </dd>

              <dt class="col-5 text-muted small">Cliente</dt>
              <dd class="col-7 small">{{ projeto.cliente }}</dd>

              <dt class="col-5 text-muted small">Clima</dt>
              <dd class="col-7 small">{{ projeto.clima?.nome }}</dd>

              <dt class="col-5 text-muted small">Criado em</dt>
              <dd class="col-7 small">{{ formatDate(projeto.created_at) }}</dd>

              <template v-if="projeto.descricao">
                <dt class="col-5 text-muted small">Descrição</dt>
                <dd class="col-7 small">{{ projeto.descricao }}</dd>
              </template>
            </dl>
          </div>
        </div>

        <div class="card">
          <div class="card-header section-header">
            <h3 class="h6 mb-0">Estatísticas</h3>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Arranjos</span>
              <span class="fw-semibold">{{ projeto.arranjos?.length || 0 }}</span>
            </div>
            <div class="d-flex justify-content-between small mb-2">
              <span class="text-muted">Strings</span>
              <span class="fw-semibold">{{ totalStrings }}</span>
            </div>
            <div class="d-flex justify-content-between small">
              <span class="text-muted">Execuções</span>
              <span class="fw-semibold">{{ projeto.execucoes?.length || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Arranjo -->
    <div v-if="showArranjoModal">
      <div class="modal fade show" style="display:block" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingArranjo ? 'Editar Arranjo' : 'Novo Arranjo' }}</h5>
              <button type="button" class="btn-close" @click="closeArranjoModal"></button>
            </div>
            <form @submit.prevent="salvarArranjo">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="arranjo_nome" class="form-label">Nome *</label>
                  <input id="arranjo_nome" v-model="arranjoForm.nome" type="text" required class="form-control" />
                </div>
                <div class="mb-3">
                  <label for="arranjo_inversor" class="form-label">Inversor *</label>
                  <select id="arranjo_inversor" v-model.number="arranjoForm.inversor_id" required class="form-select">
                    <option value="">Selecione</option>
                    <option v-for="inversor in catalogosStore.inversores" :key="inversor.id" :value="inversor.id">
                      {{ inversor.modelo }} - {{ inversor.potencia_nominal }}W
                    </option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="closeArranjoModal">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="salvandoArranjo">
                  <span v-if="salvandoArranjo" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ salvandoArranjo ? 'Salvando...' : (editingArranjo ? 'Salvar Alterações' : 'Criar Arranjo') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>

    <!-- Modal Nova String -->
    <div v-if="showStringModal">
      <div class="modal fade show" style="display:block" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingString ? 'Editar String' : 'Nova String  - em série' }}</h5>
              <button type="button" class="btn-close" @click="closeStringModal"></button>
            </div>
            <form @submit.prevent="salvarString">
              <div class="modal-body">
                <div class="mb-3">
                  <label for="string_nome" class="form-label">Nome *</label>
                  <input id="string_nome" v-model="stringForm.nome" type="text" required class="form-control" />
                </div>
                <div class="mb-3">
                  <label for="string_modulo" class="form-label">Módulo *</label>
                  <select
                    id="string_modulo"
                    v-model.number="stringForm.modulo_id"
                    required
                    class="form-select"
                  >
                    <option value="">Selecione</option>
                    <option
                      v-for="modulo in catalogosStore.modulos"
                      :key="modulo.id"
                      :value="modulo.id"
                    >
                      {{ getModuloOptionLabel(modulo) }}
                    </option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="string_ns" class="form-label">Quantidade de Módulos em Série</label>
                  <input id="string_ns" v-model.number="stringForm.num_modulos_serie" type="number" min="1" required class="form-control" />
                </div>
                <div class="mb-3">
                  <label for="string_mppt" class="form-label">MPPT do Inversor</label>
                  <select
                    id="string_mppt"
                    v-model="selectedMpptOption"
                    class="form-select"
                    :disabled="!mpptStringOptions.length"
                  >
                    <option value="" disabled>
                      {{ mpptStringOptions.length ? 'Selecione MPPT' : 'Nenhum MPPT disponível' }}
                    </option>
                    <option
                      v-for="opcao in mpptStringOptions"
                      :key="opcao.key"
                      :value="opcao.value"
                    >
                      {{ opcao.label }}
                    </option>
                  </select>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="string_azimute" class="form-label">Azimute (°)</label>
                    <input id="string_azimute" v-model.number="stringForm.azimute" type="number" min="0" max="360" required class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label for="string_inclinacao" class="form-label">Inclinação (°)</label>
                    <input id="string_inclinacao" v-model.number="stringForm.inclinacao" type="number" min="0" max="90" required class="form-control" />
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="closeStringModal">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="salvandoString">
                  <span v-if="salvandoString" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ salvandoString ? 'Salvando...' : (editingString ? 'Salvar Alterações' : 'Criar String') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import ChecagensList from '@/components/ChecagensList.vue'
import { useProjetosStore } from '@/stores/projetos'
import { useCatalogosStore } from '@/stores/catalogos'
import { useToast } from 'vue-toastification'
import { catalogosService } from '@/services/catalogos'

const route = useRoute()
const projetosStore = useProjetosStore()
const catalogosStore = useCatalogosStore()
const toast = useToast()

const loading = ref(true)
const executando = ref(false)
const projeto = ref(null)
const ultimaExecucao = ref(null)
const showArranjoModal = ref(false)
const salvandoArranjo = ref(false)
const createDefaultArranjoForm = () => ({
  nome: '',
  inversor_id: '',
  descricao: '',
  fator_sombreamento: 1
})
const arranjoForm = ref(createDefaultArranjoForm())
const editingArranjo = ref(null)

const showStringModal = ref(false)
const salvandoString = ref(false)
// Base única para resetar/gerar o formulário de string
const createDefaultStringForm = () => ({
  nome: '',
  modulo_id: '',
  num_modulos_serie: 1,
  num_strings_paralelo: 1,
  mppt_id: '',
  azimute: 180,
  inclinacao: 20
})
const stringForm = ref(createDefaultStringForm())
const selectedArranjo = ref(null)
const editingString = ref(null)
const mpptsList = ref([])
const selectedMpptOption = ref('')

// Simplifica o mapeamento das opções de MPPT garantindo valor mínimo
const mpptStringOptions = computed(() =>
  mpptsList.value.flatMap((mppt) => {
    if (!mppt) return []

    const corrente = mppt.corrente_entrada_max ?? '--'
    const totalStrings = Math.max(Number(mppt?.strings_max) || 0, 1)

    return Array.from({ length: totalStrings }, (_, index) => {
      const stringIndex = index + 1

      return {
        key: `${mppt.id}-${stringIndex}`,
        value: `${mppt.id}:${stringIndex}`,
        label:
          totalStrings === 1
            ? `MPPT ${mppt.numero}`
            : `MPPT ${mppt.numero} - string ${stringIndex} - ${corrente} (A)`,
        mpptId: mppt.id,
        stringIndex
      }
    })
  })
)

watch(selectedMpptOption, (newValue) => {
  if (!newValue) {
    stringForm.value.mppt_id = ''
    return
  }

  const [rawMpptId] = newValue.split(':')
  const parsedId = Number(rawMpptId)

  stringForm.value.mppt_id = Number.isNaN(parsedId) ? rawMpptId : parsedId
})

const normalizeId = (value) => (value === null || value === undefined ? '' : String(value))

const findMpptOptionValue = (mpptId, preferredIndex = null) => {
  const normalizedId = normalizeId(mpptId)

  if (!normalizedId) {
    return ''
  }

  const optionsForMppt = mpptStringOptions.value.filter(
    (option) => normalizeId(option.mpptId) === normalizedId
  )

  if (!optionsForMppt.length) {
    return ''
  }

  if (preferredIndex !== null && preferredIndex !== undefined) {
    const preferred = optionsForMppt.find((option) => option.stringIndex === preferredIndex)
    if (preferred) {
      return preferred.value
    }
  }

  return optionsForMppt[0].value
}

watch(
  () => mpptStringOptions.value,
  (options) => {
    if (!selectedMpptOption.value) {
      return
    }

    const stillExists = options.some((option) => option.value === selectedMpptOption.value)

    if (!stillExists) {
      selectedMpptOption.value = findMpptOptionValue(stringForm.value.mppt_id)
    }
  }
)

const formatModuloDescricao = (modulo) => {
  if (!modulo) {
    return 'Módulo não definido'
  }

  const fabricante = modulo.fabricante?.nome
  const modelo = modulo.modelo
  const potencia = modulo.potencia_nominal ?? modulo.potencia

  const partes = [fabricante, modelo, potencia ? `${potencia}W` : null].filter(Boolean)

  return partes.join(' ') || 'Módulo não definido'
}

const getModuloOptionLabel = (modulo) => formatModuloDescricao(modulo)

const getArranjoModuloLabel = (arranjo) => {
  const modulos = arranjo?.strings
    ?.map((string) => string.modulo)
    .filter(Boolean)

  if (!modulos?.length) {
    return 'Sem módulo definido'
  }

  const unicos = []
  const vistos = new Set()

  modulos.forEach((modulo) => {
    if (modulo?.id && !vistos.has(modulo.id)) {
      vistos.add(modulo.id)
      unicos.push(modulo)
    }
  })

  if (!unicos.length) {
    return 'Sem módulo definido'
  }

  return unicos.map((modulo) => formatModuloDescricao(modulo)).join(', ')
}

const getStringModuloLabel = (string) => formatModuloDescricao(string?.modulo)

const getStringMpptLabel = (string) => {
  if (string?.mppt?.numero) {
    return `MPPT ${string.mppt.numero}`
  }

  if (string?.mppt_id) {
    return `MPPT ${string.mppt_id}`
  }

  return 'MPPT -'
}

const totalStrings = computed(() => {
  return projeto.value?.arranjos?.reduce((total, arranjo) => {
    return total + (arranjo.strings?.length || 0)
  }, 0) || 0
})

const formatDate = (date) => {
  if (!date) return ''
  const parsed = new Date(date)
  return isNaN(parsed.getTime())
    ? ''
    : format(parsed, 'dd/MM/yyyy', { locale: ptBR })
}

const getStatusLabel = (status) => {
  const labels = {
    rascunho: 'Rascunho',
    em_analise: 'Em Análise',
    aprovado: 'Aprovado',
    rejeitado: 'Rejeitado'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    rascunho: 'text-bg-info',
    em_analise: 'text-bg-warning',
    aprovado: 'text-bg-success',
    rejeitado: 'text-bg-danger'
  }
  return classes[status] || 'text-bg-secondary'
}

const formatGraus = (valor) => {
  if (valor === null || valor === undefined) {
    return '--'
  }

  const numero = Number(valor)

  if (Number.isNaN(numero)) {
    return '--'
  }

  return numero.toLocaleString('pt-BR', {
    minimumFractionDigits: Number.isInteger(numero) ? 0 : 2,
    maximumFractionDigits: 2
  })
}

const executarAnalise = async () => {
  executando.value = true
  try {
    const resultado = await projetosStore.executarAnalise(projeto.value.id)
    ultimaExecucao.value = resultado
    toast.success('Análise executada com sucesso!')
  } catch (error) {
    toast.error('Erro ao executar análise')
  } finally {
    executando.value = false
  }
}

const openArranjoModal = (arranjo = null) => {
  editingArranjo.value = arranjo
  if (arranjo) {
    arranjoForm.value = {
      nome: arranjo.nome || '',
      inversor_id: arranjo.inversor_id || arranjo.inversor?.id || '',
      descricao: arranjo.descricao || '',
      fator_sombreamento: arranjo.fator_sombreamento ?? 1
    }
  } else {
    arranjoForm.value = createDefaultArranjoForm()
  }
  showArranjoModal.value = true
}

const closeArranjoModal = () => {
  showArranjoModal.value = false
  editingArranjo.value = null
  arranjoForm.value = createDefaultArranjoForm()
}

const salvarArranjo = async () => {
  salvandoArranjo.value = true
  try {
    if (editingArranjo.value) {
      await projetosStore.atualizarArranjo(editingArranjo.value.id, arranjoForm.value)
      toast.success('Arranjo atualizado com sucesso!')
    } else {
      await projetosStore.criarArranjo(projeto.value.id, arranjoForm.value)
      toast.success('Arranjo criado com sucesso!')
    }
    closeArranjoModal()
  } catch (error) {
    const msg = editingArranjo.value ? 'Erro ao atualizar arranjo' : 'Erro ao criar arranjo'
    toast.error(projetosStore.error || msg)
  } finally {
    salvandoArranjo.value = false
  }
}

const removerArranjo = async (arranjo) => {
  if (!confirm('Deseja excluir este arranjo?')) return
  try {
    await projetosStore.deletarArranjo(arranjo.id)
    toast.success('Arranjo removido com sucesso!')
  } catch (error) {
    toast.error(projetosStore.error || 'Erro ao remover arranjo')
  }
}

const openStringModal = async (arranjo, string = null) => {
  selectedArranjo.value = arranjo
  editingString.value = string
  if (arranjo.inversor?.mppts?.length) {
    mpptsList.value = arranjo.inversor.mppts
  } else {
    try {
      const inversorId = arranjo.inversor_id || arranjo.inversor?.id
      mpptsList.value = inversorId ? await catalogosService.listarMppts(inversorId) : []
    } catch (error) {
      mpptsList.value = []
    }
  }
  if (string) {
    stringForm.value = {
      ...createDefaultStringForm(),
      nome: string.nome || '',
      modulo_id: string.modulo_id || string.modulo?.id || '',
      num_modulos_serie: string.num_modulos_serie || 1,
      num_strings_paralelo: string.num_strings_paralelo || 1,
      mppt_id: string.mppt_id || '',
      azimute: Number(string.azimute ?? 0),
      inclinacao: Number(string.inclinacao ?? 0)
    }
    const preferredIndex =
      string?.mppt_string_index ?? string?.mppt_string ?? string?.string_index ?? null
    selectedMpptOption.value = findMpptOptionValue(stringForm.value.mppt_id, preferredIndex)
  } else {
    stringForm.value = createDefaultStringForm()
    selectedMpptOption.value = ''
  }
  showStringModal.value = true
}

const closeStringModal = () => {
  showStringModal.value = false
  selectedArranjo.value = null
  editingString.value = null
  stringForm.value = createDefaultStringForm()
  selectedMpptOption.value = ''
  mpptsList.value = []
}

const salvarString = async () => {
  if (!selectedArranjo.value) return
  salvandoString.value = true
  try {
    const payload = {
      ...stringForm.value,
      tipo_conexao: 'serie'
    }
    if (editingString.value) {
      await projetosStore.atualizarString(selectedArranjo.value.id, editingString.value.id, payload)
      toast.success('String atualizada com sucesso!')
    } else {
      await projetosStore.criarString(selectedArranjo.value.id, payload)
      toast.success('String criada com sucesso!')
    }
    closeStringModal()
  } catch (error) {
    const msg = editingString.value ? 'Erro ao atualizar string' : 'Erro ao criar string'
    toast.error(projetosStore.error || msg)
  } finally {
    salvandoString.value = false
  }
}

const removerString = async (arranjo, string) => {
  if (!confirm('Deseja excluir esta string?')) return
  try {
    await projetosStore.deletarString(arranjo.id, string.id)
    toast.success('String removida com sucesso!')
  } catch (error) {
    toast.error(projetosStore.error || 'Erro ao remover string')
  }
}

onMounted(async () => {
  try {
    const [proj] = await Promise.all([
      projetosStore.buscarProjeto(route.params.id),
      catalogosStore.carregarModulos(),
      catalogosStore.carregarInversores()
    ])
    projeto.value = proj
    ultimaExecucao.value = proj.execucoes?.[0] || null
  } catch (error) {
    toast.error('Erro ao carregar dados')
  } finally {
    loading.value = false
  }
})
</script>
<style scoped>
/* Cabeçalhos dos cards */
.section-header {
  background: #f8fbff;
  border-bottom: 1px solid #e9eef6;
}

/* Ações no topo (mantém alinhadas e sem sobrepor breadcrumb) */
.page-actions {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: nowrap;
}

/* Estado vazio do bloco de arranjos */
.empty-state {
  text-align: center;
  padding: 2rem 1rem;
}

/* Item de arranjo */
.arranjo-item {
  border: 1px solid #e9ecef;
  border-radius: .5rem;
  padding: .875rem;
}

/* KPIs */
.kpi .value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
}
.kpi .label {
  font-size: .875rem;
  color: #6c757d;
}

/* Breadcrumb sutil */
.breadcrumb {
  --bs-breadcrumb-divider: "›";
  margin-bottom: .25rem;
}

</style>