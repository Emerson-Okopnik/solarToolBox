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
        <router-link
          :to="`/projetos/${projeto.id}/editar`"
          class="btn btn-outline-secondary"
        >
          Editar
        </router-link>
      </div>
    </div>

    <!-- Conteúdo -->
    <div class="row g-4">
      <div class="col-lg-8 col-xl-9 d-flex flex-column gap-4">
        <!-- Arranjos -->
        <div class="card">
          <div class="card-header d-flex align-items-center justify-content-between section-header">
            <h3 class="h6 mb-0">Arranjos Fotovoltaicos</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
              <PlusIcon style="width:1rem;height:1rem" class="me-1" />
              Adicionar Arranjo
            </button>
          </div>

          <div class="card-body">
            <div v-if="!projeto.arranjos || projeto.arranjos.length === 0" class="empty-state">
              <CpuChipIcon style="width:3rem;height:3rem" class="mb-2 text-muted" />
              <h6 class="mb-1">Nenhum arranjo</h6>
              <p class="text-muted mb-3">Adicione arranjos para começar a análise</p>
              <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
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
                    <div class="text-muted small">
                      {{ arranjo.modulo?.nome }} | {{ arranjo.inversor?.nome }}
                    </div>
                    <div class="text-muted small">
                      Azimute: {{ arranjo.azimute }}° | Inclinação: {{ arranjo.inclinacao }}°
                    </div>
                  </div>
                  <div class="text-end">
                    <div class="small fw-semibold text-dark">
                      {{ arranjo.strings?.length || 0 }} strings
                    </div>
                    <span class="badge text-bg-info">{{ arranjo.status }}</span>
                  </div>
                </div>
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
            <div class="row text-center g-3">
              <div class="col-4">
                <div class="kpi">
                  <div class="value text-success">{{ ultimaExecucao.checagens_aprovadas || 0 }}</div>
                  <div class="label">Checagens Aprovadas</div>
                </div>
              </div>
              <div class="col-4">
                <div class="kpi">
                  <div class="value" style="color:#f59e0b">{{ ultimaExecucao.checagens_aviso || 0 }}</div>
                  <div class="label">Avisos</div>
                </div>
              </div>
              <div class="col-4">
                <div class="kpi">
                  <div class="value text-danger">{{ ultimaExecucao.checagens_erro || 0 }}</div>
                  <div class="label">Erros</div>
                </div>
              </div>
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
  </div>
</template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import {
  ChevronRightIcon,
  PlusIcon,
  CpuChipIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { useProjetosStore } from '@/stores/projetos'
import { useToast } from 'vue-toastification'

const route = useRoute()
const projetosStore = useProjetosStore()
const toast = useToast()

const loading = ref(true)
const executando = ref(false)
const projeto = ref(null)
const ultimaExecucao = ref(null)

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

onMounted(async () => {
  try {
    projeto.value = await projetosStore.buscarProjeto(route.params.id)
  } catch (error) {
    toast.error('Erro ao carregar projeto')
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