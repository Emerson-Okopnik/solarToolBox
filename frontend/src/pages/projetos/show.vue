<template>
  <div v-if="loading" class="flex justify-center py-12">
    <LoadingSpinner />
  </div>
  <div v-else-if="projeto" class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <nav class="flex" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-4">
            <li>
              <router-link to="/projetos" class="text-gray-400 hover:text-gray-500">
                Projetos
              </router-link>
            </li>
            <li>
              <ChevronRightIcon class="h-5 w-5 text-gray-400" />
            </li>
            <li>
              <span class="text-gray-900 font-medium">{{ projeto.nome }}</span>
            </li>
          </ol>
        </nav>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ projeto.nome }}</h1>
        <p class="mt-1 text-gray-600">{{ projeto.cliente }}</p>
      </div>
      <div class="flex items-center space-x-3">
        <button
          @click="executarAnalise"
          :disabled="executando"
          class="btn-primary"
        >
          <div v-if="executando" class="loading-spinner mr-2"></div>
          {{ executando ? 'Executando...' : 'Executar Análise' }}
        </button>
        <router-link :to="`/projetos/${projeto.id}/editar`" class="btn-outline">
          Editar
        </router-link>
      </div>
    </div>

    <!-- Project Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-6">
        <!-- Arranjos -->
        <div class="card">
          <div class="card-header">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">Arranjos Fotovoltaicos</h3>
              <button class="btn-outline btn-sm">
                <PlusIcon class="h-4 w-4 mr-1" />
                Adicionar Arranjo
              </button>
            </div>
          </div>
          <div class="card-body">
            <div v-if="projeto.arranjos?.length === 0" class="text-center py-8">
              <CpuChipIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum arranjo</h3>
              <p class="mt-1 text-sm text-gray-500">Adicione arranjos para começar a análise</p>
            </div>
            <div v-else class="space-y-4">
              <div
                v-for="arranjo in projeto.arranjos"
                :key="arranjo.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="font-medium text-gray-900">{{ arranjo.nome }}</h4>
                    <p class="text-sm text-gray-500">
                      {{ arranjo.modulo?.nome }} | {{ arranjo.inversor?.nome }}
                    </p>
                    <p class="text-sm text-gray-500">
                      Azimute: {{ arranjo.azimute }}° | Inclinação: {{ arranjo.inclinacao }}°
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">
                      {{ arranjo.strings?.length || 0 }} strings
                    </p>
                    <span class="badge badge-info">{{ arranjo.status }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Resultados da Análise -->
        <div v-if="ultimaExecucao" class="card">
          <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900">Resultados da Análise</h3>
          </div>
          <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="text-center">
                <div class="text-2xl font-bold text-success-600">
                  {{ ultimaExecucao.checagens_aprovadas || 0 }}
                </div>
                <div class="text-sm text-gray-500">Checagens Aprovadas</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-solar-600">
                  {{ ultimaExecucao.checagens_aviso || 0 }}
                </div>
                <div class="text-sm text-gray-500">Avisos</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-danger-600">
                  {{ ultimaExecucao.checagens_erro || 0 }}
                </div>
                <div class="text-sm text-gray-500">Erros</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Project Details -->
        <div class="card">
          <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900">Detalhes do Projeto</h3>
          </div>
          <div class="card-body space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">Status</dt>
              <dd class="mt-1">
                <span class="badge" :class="getStatusBadgeClass(projeto.status)">
                  {{ getStatusLabel(projeto.status) }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">Cliente</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ projeto.cliente }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">Clima</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ projeto.clima?.nome }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">Criado em</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(projeto.created_at) }}</dd>
            </div>
            <div v-if="projeto.descricao">
              <dt class="text-sm font-medium text-gray-500">Descrição</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ projeto.descricao }}</dd>
            </div>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
          <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900">Estatísticas</h3>
          </div>
          <div class="card-body space-y-3">
            <div class="flex justify-between">
              <span class="text-sm text-gray-500">Arranjos</span>
              <span class="text-sm font-medium text-gray-900">{{ projeto.arranjos?.length || 0 }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-500">Strings</span>
              <span class="text-sm font-medium text-gray-900">{{ totalStrings }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-500">Execuções</span>
              <span class="text-sm font-medium text-gray-900">{{ projeto.execucoes?.length || 0 }}</span>
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
    rascunho: 'badge-info',
    em_analise: 'badge-warning',
    aprovado: 'badge-success',
    rejeitado: 'badge-danger'
  }
  return classes[status] || 'badge-info'
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
