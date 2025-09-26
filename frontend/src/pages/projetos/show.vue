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
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="arranjo_modulos" class="form-label">Quantidade de Módulos *</label>
                    <input
                      id="arranjo_modulos"
                      v-model.number="arranjoForm.quantidade_modulos"
                      type="number"
                      min="1"
                      required
                      class="form-control"
                    />
                  </div>
                  <div class="col-md-6">
                    <label for="arranjo_modulo_catalogo" class="form-label">Módulo *</label>
                    <select
                      id="arranjo_modulo_catalogo"
                      v-model="arranjoForm.modulo_id"
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
                    <div class="form-text" v-if="potenciaModuloSelecionado">
                      Potência nominal do módulo: {{ formatNumero(potenciaModuloSelecionado, 2) }} W
                    </div>
                    <div class="form-text" v-if="potenciaTotalEstimado">
                      Potência total estimada: {{ formatNumero(potenciaTotalEstimado, 2) }} W
                    </div>
                  </div>
                </div>
                <div class="mb-3 mt-3">
                  <label for="arranjo_orientacoes" class="form-label">Orientações das Strings *</label>
                  <textarea
                    id="arranjo_orientacoes"
                    v-model="arranjoForm.orientacoes_texto"
                    rows="3"
                    required
                    class="form-control"
                    placeholder="Informe uma orientação por linha (ex: Az180_Inc20)"
                  ></textarea>
                  <div class="form-text">Cada linha representa uma string. Utilize azimute e inclinação no formato desejado.</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm"
                    @click="buscarInversoresCompatíveis"
                    :disabled="!podeBuscarSugestoes || buscandoSugestoes"
                  >
                    <span
                      v-if="buscandoSugestoes"
                      class="spinner-border spinner-border-sm me-2"
                      role="status"
                      aria-hidden="true"
                    ></span>
                    {{ buscandoSugestoes ? 'Buscando...' : 'Buscar inversores compatíveis' }}
                  </button>
                  <span v-if="!podeBuscarSugestoes" class="text-muted small">
                    Informe a quantidade de módulos, selecione um módulo e descreva as orientações para gerar recomendações.
                  </span
                  >
                </div>
                <div v-if="erroSugestoes" class="alert alert-danger py-2">{{ erroSugestoes }}</div>
                <div v-if="resumoRecomendacao" class="alert alert-info py-2">
                  <div class="small fw-semibold mb-1">Resumo das strings identificadas</div>
                  <div class="d-flex flex-wrap gap-3 small">
                    <span><strong>{{ resumoRecomendacao.total_strings }}</strong> strings</span>
                    <span>{{ resumoRecomendacao.modulos_por_string }} módulos/string</span>
                    <span>{{ formatNumero(resumoRecomendacao.potencia_por_string) }} W/string</span>
                    <span>{{ formatNumero(resumoRecomendacao.potencia_por_modulo) }} W/módulo</span>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="arranjo_inversor" class="form-label">Inversor *</label>
                  <select id="arranjo_inversor" v-model.number="arranjoForm.inversor_id" required class="form-select">
                    <option value="">Selecione</option>
                    <option v-for="opcao in inversorOptions" :key="opcao.id" :value="opcao.id">
                      {{ opcao.label }}
                    </option>
                  </select>
                  <div v-if="!sugestoesInversores.length" class="form-text">
                    Sem recomendações automáticas no momento. Utilize o catálogo completo para selecionar o inversor.
                  </div>
                </div>
                <div v-if="sugestoesInversores.length" class="mb-3">
                  <div class="fw-semibold mb-2">Inversores compatíveis sugeridos</div>
                  <div
                    v-for="sugestao in sugestoesInversores"
                    :key="sugestao.inversor.id"
                    class="border rounded p-2 mb-2 bg-light"
                  >
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fw-semibold">{{ sugestao.inversor.modelo }}</div>
                        <div v-if="sugestao.inversor.fabricante" class="text-muted small">
                          {{ sugestao.inversor.fabricante }}
                        </div>
                      </div>
                      <span
                        v-if="arranjoForm.inversor_id === sugestao.inversor.id"
                        class="badge bg-primary"
                        >Selecionado</span
                      >
                    </div>
                    <div class="small mt-2">
                      <span class="badge bg-success me-1">Oversizing {{ formatNumero(sugestao.metricas.fator_oversizing) }}%</span>
                      <span class="badge bg-secondary">Margem DC {{ formatNumero(sugestao.metricas.margem_potencia_dc) }} W</span>
                    </div>
                    <ul class="small mb-0 mt-2 ps-3">
                      <li v-for="mppt in sugestao.distribuicao" :key="mppt.mppt">
                        MPPT {{ mppt.mppt }} — {{ mppt.strings_alocadas }}/{{ mppt.capacidade_strings }} strings
                        <span v-if="mppt.orientacoes.length">
                          •
                          <span
                            v-for="orientacao in mppt.orientacoes"
                            :key="`${mppt.mppt}-${orientacao.identificador}`"
                            class="me-1"
                          >
                            {{ orientacao.strings }}×{{ orientacao.identificador }}
                          </span>
                        </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <div v-else-if="resumoRecomendacao && !buscandoSugestoes" class="alert alert-warning py-2">
                  Nenhum inversor compatível foi encontrado. Ajuste os parâmetros informados e tente novamente.
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
import { inversoresService } from '@/services/inversores'

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
  fator_sombreamento: 1,
  quantidade_modulos: null,
  modulo_id: '',
  orientacoes_texto: ''
})
const arranjoForm = ref(createDefaultArranjoForm())
const editingArranjo = ref(null)

const sugestoesInversores = ref([])
const resumoRecomendacao = ref(null)
const erroSugestoes = ref('')
const buscandoSugestoes = ref(false)

const formatNumero = (valor, fractionDigits = 2) => {
  const numero = Number(valor)

  if (!Number.isFinite(numero)) {
    return '--'
  }

  return numero.toLocaleString('pt-BR', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits
  })
}

const orientacoesList = computed(() =>
  (arranjoForm.value.orientacoes_texto || '')
    .split(/\r?\n/)
    .map((texto) => texto.trim())
    .filter(Boolean)
)

const moduloSelecionado = computed(() => {
  const moduloId = arranjoForm.value.modulo_id

  if (!moduloId) {
    return null
  }

  const idNormalizado = Number(moduloId)

  return (
    catalogosStore.modulos.find((modulo) => {
      const idModulo = Number(modulo?.id)

      return Number.isNaN(idModulo) ? modulo?.id === moduloId : idModulo === idNormalizado
    }) || null
  )
})

const potenciaModuloSelecionado = computed(() => {
  const modulo = moduloSelecionado.value

  if (!modulo) {
    return 0
  }

  const potencia = Number(modulo?.potencia_nominal ?? modulo?.potencia)

  return Number.isFinite(potencia) && potencia > 0 ? potencia : 0
})

const potenciaTotalEstimado = computed(() => {
  const quantidade = Number(arranjoForm.value.quantidade_modulos)
  const potenciaModulo = potenciaModuloSelecionado.value

  if (quantidade > 0 && potenciaModulo > 0) {
    return quantidade * potenciaModulo
  }

  return 0
})

const podeBuscarSugestoes = computed(() => {
  const quantidade = Number(arranjoForm.value.quantidade_modulos)
  const potencia = Number(potenciaTotalEstimado.value)
  const moduloId = arranjoForm.value.modulo_id

  return (
    quantidade > 0 &&
    Boolean(moduloId) &&
    Number.isFinite(potencia) &&
    potencia > 0 &&
    orientacoesList.value.length > 0
  )
})

const inversorOptions = computed(() => {
  const recomendados = sugestoesInversores.value.map((item) => {
    const potenciaAc = formatNumero(item.inversor.potencia_ac_nominal, 0)
    const oversizing = formatNumero(item.metricas?.fator_oversizing, 2)
    const fabricante = item.inversor.fabricante ? ` • ${item.inversor.fabricante}` : ''

    return {
      id: item.inversor.id,
      label: `${item.inversor.modelo}${fabricante} • ${potenciaAc} W • Oversizing ${oversizing}%`
    }
  })

  const catalogo = (catalogosStore.inversores || []).map((inversor) => {
    const potencia = formatNumero(inversor.potencia_ac_nominal ?? inversor.potencia_nominal, 0)
    const fabricante = inversor.fabricante?.nome ? ` • ${inversor.fabricante.nome}` : ''

    return {
      id: inversor.id,
      label: `${inversor.modelo}${fabricante} • ${potencia} W`
    }
  })

  const unicos = []
  const vistos = new Set()

  const adicionar = (opcao) => {
    if (!vistos.has(opcao.id)) {
      vistos.add(opcao.id)
      unicos.push(opcao)
    }
  }

  recomendados.forEach(adicionar)
  catalogo.forEach(adicionar)

  return unicos
})

const resetarSugestoes = () => {
  sugestoesInversores.value = []
  resumoRecomendacao.value = null
  erroSugestoes.value = ''
}

const formatarOrientacaoString = (string) => {
  const formatValor = (valor) => {
    const numero = Number(valor)
    if (!Number.isFinite(numero)) {
      return '0.00'
    }

    return numero.toFixed(2)
  }

  return `Az${formatValor(string?.azimute)}_Inc${formatValor(string?.inclinacao)}`
}

const calcularTotalModulos = (strings = []) =>
  strings.reduce((total, item) => {
    const totalInformado = Number(item?.total_modulos)

    if (Number.isFinite(totalInformado) && totalInformado > 0) {
      return total + totalInformado
    }

    const serie = Number(item?.num_modulos_serie ?? 0)
    const paralelo = Number(item?.num_strings_paralelo ?? 1)

    if (serie > 0) {
      const paraleloValido = Number.isFinite(paralelo) && paralelo > 0 ? paralelo : 1
      return total + serie * paraleloValido
    }

    return total
  }, 0)

const calcularPotenciaStrings = (strings = []) =>
  strings.reduce((total, item) => {
    const potencia = Number(item?.potencia_total)

    if (Number.isFinite(potencia)) {
      return total + potencia
    }

    return total
  }, 0)

const inferModuloId = (strings = []) => {
  const ids = strings
    .map((item) => item?.modulo_id ?? item?.modulo?.id)
    .filter((id) => id !== null && id !== undefined && id !== '')

  if (ids.length) {
    const primeiro = ids[0]
    const numero = Number(primeiro)

    return Number.isNaN(numero) ? primeiro : numero
  }

  const totalModulos = calcularTotalModulos(strings)
  const potenciaTotal = calcularPotenciaStrings(strings)

  if (totalModulos > 0 && potenciaTotal > 0) {
    const potenciaModulo = potenciaTotal / totalModulos

    const moduloEncontrado = catalogosStore.modulos.find((modulo) => {
      const potenciaCatalogo = Number(modulo?.potencia_nominal ?? modulo?.potencia)

      return Number.isFinite(potenciaCatalogo) && Math.abs(potenciaCatalogo - potenciaModulo) < 0.51
    })

    if (moduloEncontrado) {
      const numeroId = Number(moduloEncontrado.id)
      return Number.isNaN(numeroId) ? moduloEncontrado.id : numeroId
    }
  }

  return ''
}
watch(
  () => [
    arranjoForm.value.quantidade_modulos,
    arranjoForm.value.modulo_id,
    arranjoForm.value.orientacoes_texto
  ],
  () => {
    if (!buscandoSugestoes.value) {
      resetarSugestoes()
    }
  }
)

const buscarInversoresCompatíveis = async () => {
  if (!podeBuscarSugestoes.value) {
    erroSugestoes.value = 'Preencha a quantidade de módulos, selecione um módulo do catálogo e informe as orientações das strings.'
    return
  }

  buscandoSugestoes.value = true
  erroSugestoes.value = ''
  resumoRecomendacao.value = null
  sugestoesInversores.value = []

  try {
    const potenciaTotal = Number(potenciaTotalEstimado.value)

    if (!Number.isFinite(potenciaTotal) || potenciaTotal <= 0) {
      erroSugestoes.value =
        'Não foi possível calcular a potência total estimada. Verifique a quantidade informada e o módulo selecionado.'
      return
    }

    const payload = {
      quantidade_modulos: Number(arranjoForm.value.quantidade_modulos),
      potencia_total: potenciaTotal,
      orientacoes: orientacoesList.value
    }

    const response = await inversoresService.recomendar(payload)

    if (!response?.success) {
      erroSugestoes.value = response?.message || 'Não foi possível calcular recomendações.'
      return
    }

    const dados = response.data ?? {}

    const potenciaPorModuloResposta = Number(dados.potencia_por_modulo ?? 0)
    const potenciaModulo = potenciaModuloSelecionado.value

    resumoRecomendacao.value = {
      total_strings: dados.total_strings ?? 0,
      modulos_por_string: dados.modulos_por_string ?? 0,
      potencia_por_string: dados.potencia_por_string ?? 0,
      potencia_por_modulo:
        Number.isFinite(potenciaPorModuloResposta) && potenciaPorModuloResposta > 0
          ? potenciaPorModuloResposta
          : potenciaModulo
    }

    sugestoesInversores.value = dados.inversores ?? []

    if (sugestoesInversores.value.length) {
      const selecionado = sugestoesInversores.value.find(
        (item) => item.inversor.id === arranjoForm.value.inversor_id
      )

      if (!selecionado) {
        arranjoForm.value.inversor_id = sugestoesInversores.value[0].inversor.id
      }
    } else if (!catalogosStore.inversores.some((inv) => inv.id === arranjoForm.value.inversor_id)) {
      arranjoForm.value.inversor_id = ''
    }
  } catch (error) {
    erroSugestoes.value =
      error.response?.data?.message || 'Erro ao buscar inversores compatíveis.'
  } finally {
    buscandoSugestoes.value = false
  }
}

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

const normalizeId = (value) => (value === null || value === undefined ? '' : String(value))

// Simplifica o mapeamento das opções de MPPT garantindo valor mínimo
const mpptStringOptions = computed(() =>
  mpptsList.value
    .filter(Boolean)
    .map((mppt) => {
      const totalStrings = Math.max(Number(mppt?.strings_max) || 0, 1)
      const numero = mppt?.numero ?? '--'
      const normalizedId = normalizeId(mppt?.id ?? numero)

      return {
        key: normalizedId || numero,
        value: normalizedId,
        label: `MPPT ${numero} - string${totalStrings > 1 ? 's' : ''} máx: ${totalStrings}`,
        mpptId: normalizedId
      }
    })
)

watch(selectedMpptOption, (newValue) => {
  if (!newValue) {
    stringForm.value.mppt_id = ''
    return
  }

  const parsedId = Number(newValue)
  stringForm.value.mppt_id = Number.isNaN(parsedId) ? newValue : parsedId
})

const findMpptOptionValue = (mpptId) => {
  const normalizedId = normalizeId(mpptId)

  if (!normalizedId) {
    return ''
  }

  const option = mpptStringOptions.value.find((item) => item.mpptId === normalizedId)

  return option ? option.value : ''
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
  resetarSugestoes()
  erroSugestoes.value = ''

  if (arranjo) {
    const strings = arranjo.strings || []
    const totalModulos = calcularTotalModulos(strings)
    const moduloInferido = inferModuloId(strings)
    const orientacoesTexto = strings.length
      ? strings.map((string) => formatarOrientacaoString(string)).join('\n')
      : ''

    arranjoForm.value = {
      ...createDefaultArranjoForm(),
      nome: arranjo.nome || '',
      inversor_id: arranjo.inversor_id || arranjo.inversor?.id || '',
      descricao: arranjo.descricao || '',
      fator_sombreamento: arranjo.fator_sombreamento ?? 1,
      quantidade_modulos: totalModulos || null,
      modulo_id: moduloInferido,
      orientacoes_texto: orientacoesTexto
    }
  } else {
    arranjoForm.value = createDefaultArranjoForm()
  }
  showArranjoModal.value = true
}

const closeArranjoModal = () => {
  showArranjoModal.value = false
  editingArranjo.value = null
  resetarSugestoes()
  erroSugestoes.value = ''
  buscandoSugestoes.value = false
  arranjoForm.value = createDefaultArranjoForm()
}

const salvarArranjo = async () => {
  if (!arranjoForm.value.inversor_id) {
    toast.error('Selecione um inversor compatível antes de salvar o arranjo.')
    return
  }

  salvandoArranjo.value = true

  const payload = {
    nome: arranjoForm.value.nome,
    inversor_id: arranjoForm.value.inversor_id,
    descricao: arranjoForm.value.descricao,
    fator_sombreamento: arranjoForm.value.fator_sombreamento ?? 1
  }
  try {
    if (editingArranjo.value) {
      await projetosStore.atualizarArranjo(editingArranjo.value.id, payload)
      toast.success('Arranjo atualizado com sucesso!')
    } else {
      await projetosStore.criarArranjo(projeto.value.id, payload)
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
    selectedMpptOption.value = findMpptOptionValue(stringForm.value.mppt_id)
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